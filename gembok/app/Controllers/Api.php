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
            $locations = $db->table('onu_locations')
                            ->select('id, name, serial_number as serial, lat, lng')
                            ->get()
                            ->getResultArray();
        } catch (\Exception $e) {
            $locations = [];
        }
        
        return $this->response->setJSON($locations);
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
     * Update ONU WiFi settings via GenieACS
     */
    public function updateWifi()
    {
        $json = $this->request->getJSON(true);
        
        $serial = $json['serial'] ?? '';
        $ssid = $json['ssid'] ?? '';
        $password = $json['password'] ?? '';
        
        if (empty($serial) || empty($ssid) || empty($password)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Serial, SSID dan Password wajib diisi'
            ]);
        }
        
        if (strlen($password) < 8) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Password minimal 8 karakter'
            ]);
        }
        
        try {
            $genie = new GenieacsService();
            $result = $genie->setWifi($serial, $ssid, $password);
            
            if (($result['code'] ?? 0) === 200 || ($result['code'] ?? 0) === 202) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'WiFi berhasil diperbarui'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'GenieACS error: ' . ($result['body']['error'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
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
