<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\GenieacsService;

class Api extends BaseController
{
    /**
     * Get ONU locations from database
     */
    public function onuLocations()
    {
        $db = \Config\Database::connect();
        
        try {
            // Select serial_number as serial for frontend compatibility
            $rawLocations = $db->table('onu_locations')
                            ->select('id, name, serial_number as serial, lat, lng')
                            ->get()
                            ->getResultArray();
            
            // CRITICAL: Manually convert each field to correct type
            // This ensures JavaScript receives actual numbers, not strings
            $locations = [];
            foreach ($rawLocations as $loc) {
                $locations[] = [
                    'id' => (int)$loc['id'],
                    'name' => (string)$loc['name'],
                    'serial' => (string)$loc['serial'],
                    'lat' => (double)$loc['lat'],    // Use double for precision
                    'lng' => (double)$loc['lng']     // Use double for precision
                ];
            }
            
            // Log for debugging
            log_message('info', 'API onuLocations: Returning ' . count($locations) . ' locations');
            
        } catch (\Exception $e) {
            log_message('error', 'API onuLocations error: ' . $e->getMessage());
            $locations = [];
        }
        
        // Use json_encode directly with flags
        $json = json_encode($locations, JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION);
        
        return $this->response
                    ->setContentType('application/json')
                    ->setBody($json);
    }

    /**
     * Get single ONU detail from GenieACS
     */
    public function onuDetail()
    {
        $serial = $this->request->getGet('serial');
        if (!$serial) return $this->response->setJSON([]);

        try {
            $genie = new GenieacsService();
            $device = $genie->getDevice($serial);
            return $this->response->setJSON($device);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Add new ONU location
     */
    public function addOnu()
    {
        $json = $this->request->getJSON(true);
        
        $name = $json['name'] ?? '';
        $serial = $json['serial'] ?? '';
        $lat = $json['lat'] ?? 0;
        $lng = $json['lng'] ?? 0;
        
        if (empty($serial) || empty($lat) || empty($lng)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Serial, Latitude dan Longitude wajib diisi'
            ]);
        }
        
        $db = \Config\Database::connect();
        
        try {
            $db->table('onu_locations')->insert([
                'name' => $name,
                'serial_number' => $serial, // Fix: mapping to serial_number
                'lat' => $lat,
                'lng' => $lng,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'ONU berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete ONU location
     */
    public function deleteOnu()
    {
        $json = $this->request->getJSON(true);
        $serial = $json['serial'] ?? '';
        
        if (empty($serial)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Serial number wajib diisi'
            ]);
        }
        
        $db = \Config\Database::connect();
        
        try {
            $deleted = $db->table('onu_locations')
                         ->where('serial_number', $serial)
                         ->delete();
            
            if ($deleted) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'Lokasi ONU berhasil dihapus'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'ONU tidak ditemukan'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update ONU WiFi settings via GenieACS
     */
    public function updateWifi()
    {
        $json = $this->request->getJSON(true);
        
        $serial = $json['serial'] ?? '';
        $ssid = $json['ssid'] ?? '';
        $password = $json['password'] ?? '';
        
        // Serial wajib, tapi SSID atau Password boleh salah satu
        if (empty($serial)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Serial number wajib diisi'
            ]);
        }
        
        // Minimal salah satu harus diisi (SSID atau Password)
        if (empty($ssid) && empty($password)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'SSID atau Password harus diisi (minimal salah satu)'
            ]);
        }
        
        // Validasi password jika diisi
        if (!empty($password) && strlen($password) < 8) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Password minimal 8 karakter'
            ]);
        }
        
        try {
            $genie = new GenieacsService();
            
            // Log request
            log_message('info', "API updateWifi: serial={$serial}, ssid={$ssid}, password=" . (empty($password) ? 'empty' : 'set'));
            
            $result = $genie->setWifi($serial, $ssid, $password);
            
            // Log response
            log_message('info', "GenieACS setWifi response: " . json_encode($result));
            
            $code = $result['code'] ?? 0;
            $error = $result['error'] ?? '';
            $body = $result['body'] ?? [];
            
            if ($code === 200 || $code === 202) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'WiFi berhasil diperbarui'
                ]);
            } else {
                // More detailed error message
                $errorMsg = 'Unknown error';
                
                if (!empty($error)) {
                    $errorMsg = $error;
                } elseif (is_array($body) && isset($body['message'])) {
                    $errorMsg = $body['message'];
                } elseif (is_array($body) && isset($body['error'])) {
                    $errorMsg = $body['error'];
                } elseif ($code === 404) {
                    $errorMsg = "Device not found (serial: {$serial})";
                } elseif ($code === 0) {
                    $errorMsg = "Cannot connect to GenieACS server";
                }
                
                log_message('error', "GenieACS setWifi failed: code={$code}, error={$errorMsg}");
                
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => "GenieACS error: {$errorMsg} (code: {$code})"
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', "API updateWifi exception: " . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * WhatsApp Webhook
     */
    public function whatsappWebhook()
    {
        // TODO: Implement WhatsApp webhook handler
        return $this->response->setJSON(['status' => 'ok']);
    }
}
?>
