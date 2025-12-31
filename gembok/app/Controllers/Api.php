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
     * Add or Update ONU location (Upsert)
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
            // Check if ONU already exists
            $existing = $db->table('onu_locations')
                          ->where('serial_number', $serial)
                          ->get()
                          ->getRowArray();
            
            if ($existing) {
                // Update existing record
                $db->table('onu_locations')
                   ->where('serial_number', $serial)
                   ->update([
                       'name' => $name,
                       'lat' => $lat,
                       'lng' => $lng,
                       'updated_at' => date('Y-m-d H:i:s')
                   ]);
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'Lokasi ONU berhasil diperbarui'
                ]);
            } else {
                // Insert new record
                $db->table('onu_locations')->insert([
                    'name' => $name,
                    'serial_number' => $serial,
                    'lat' => $lat,
                    'lng' => $lng,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'Lokasi ONU berhasil ditambahkan'
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
     * Realtime Dashboard Stats
     * GET /api/dashboard/stats
     */
    public function dashboardStats()
    {
        $db = \Config\Database::connect();
        
        try {
            // Today's revenue
            $today = date('Y-m-d');
            $revenueResult = $db->table('invoices')
                ->selectSum('amount')
                ->where('paid', 1)
                ->where('paid_at IS NOT NULL')
                ->where('DATE(paid_at) =', $today)
                ->get()->getRowArray();
            $todayRevenue = $revenueResult['amount'] ?? 0;
            
            // If today empty, show total
            if ($todayRevenue == 0) {
                $allRevenue = $db->table('invoices')
                    ->selectSum('amount')
                    ->where('paid', 1)
                    ->get()->getRowArray();
                $todayRevenue = $allRevenue['amount'] ?? 0;
            }
            
            // Online PPPoE (from MikroTik if available)
            $onlinePppoe = $db->table('customers')->where('status', 'active')->countAllResults();
            
            // Pending invoices
            $pendingInvoices = $db->table('invoices')->where('status', 'pending')->countAllResults();
            
            // Total devices
            $totalDevices = $db->table('onu_locations')->countAllResults();
            
            // Pending tickets
            $pendingTickets = $db->table('trouble_tickets')
                ->whereIn('status', ['pending', 'in_progress'])
                ->countAllResults();
            
            $stats = [
                'todayRevenue' => (int)$todayRevenue,
                'onlinePppoe' => (int)$onlinePppoe,
                'pendingInvoices' => (int)$pendingInvoices,
                'totalDevices' => (int)$totalDevices,
                'pendingTickets' => (int)$pendingTickets,
                'timestamp' => time()
            ];
            
            return $this->response->setJSON(['success' => true, 'stats' => $stats]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Realtime Analytics Summary
     * GET /api/analytics/summary
     */
    public function analyticsSummary()
    {
        $db = \Config\Database::connect();
        
        try {
            // Revenue this month
            $currentMonth = date('Y-m');
            $monthlyRevenue = $db->table('invoices')
                ->selectSum('amount')
                ->where('paid', 1)
                ->where('paid_at IS NOT NULL')
                ->where('DATE_FORMAT(paid_at, "%Y-%m") =', $currentMonth)
                ->get()->getRowArray();
            $revenueThisMonth = $monthlyRevenue['amount'] ?? 0;
            
            // If empty, show all time
            if ($revenueThisMonth == 0) {
                $allRevenue = $db->table('invoices')
                    ->selectSum('amount')
                    ->where('paid', 1)
                    ->get()->getRowArray();
                $revenueThisMonth = $allRevenue['amount'] ?? 0;
            }
            
            // Paid invoices
            $paidInvoices = $db->table('invoices')->where('paid', 1)->countAllResults();
            
            // Unpaid invoices
            $unpaidInvoices = $db->table('invoices')->where('paid', 0)->countAllResults();
            
            // Total customers
            $totalCustomers = $db->table('customers')->countAllResults();
            
            $summary = [
                'revenueThisMonth' => (int)$revenueThisMonth,
                'paidInvoices' => (int)$paidInvoices,
                'unpaidInvoices' => (int)$unpaidInvoices,
                'totalCustomers' => (int)$totalCustomers,
                'timestamp' => time()
            ];
            
            return $this->response->setJSON(['success' => true, 'summary' => $summary]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Realtime Recent Invoices
     * GET /api/invoices/recent
     */
    public function recentInvoices()
    {
        $db = \Config\Database::connect();
        
        try {
            $limit = $this->request->getGet('limit') ?? 10;
            
            $invoices = $db->table('invoices')
                ->select('invoices.*, customers.name as customer_name')
                ->join('customers', 'customers.id = invoices.customer_id', 'left')
                ->orderBy('invoices.created_at', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            
            return $this->response->setJSON([
                'success' => true, 
                'invoices' => $invoices,
                'count' => count($invoices),
                'timestamp' => time()
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Customers List with Pagination
     * GET /api/customers/list?page=1&per_page=50&search=
     */
    public function customersList()
    {
        $db = \Config\Database::connect();
        
        try {
            $page = max(1, (int)($this->request->getGet('page') ?? 1));
            $perPage = max(10, min(100, (int)($this->request->getGet('per_page') ?? 50)));
            $search = $this->request->getGet('search') ?? '';
            
            $builder = $db->table('customers')
                ->select('customers.*, packages.name as package_name, packages.price as package_price')
                ->join('packages', 'packages.id = customers.package_id', 'left');
            
            // Search
            if (!empty($search)) {
                $builder->groupStart()
                    ->like('customers.name', $search)
                    ->orLike('customers.phone', $search)
                    ->orLike('customers.pppoe_username', $search)
                    ->groupEnd();
            }
            
            // Count total
            $total = $builder->countAllResults(false);
            $totalPages = ceil($total / $perPage);
            
            // Get data
            $offset = ($page - 1) * $perPage;
            $customers = $builder->orderBy('customers.id', 'DESC')
                ->limit($perPage, $offset)
                ->get()->getResultArray();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $customers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Invoices List with Pagination
     * GET /api/invoices/list?page=1&per_page=50&search=
     */
    public function invoicesList()
    {
        $db = \Config\Database::connect();
        
        try {
            $page = max(1, (int)($this->request->getGet('page') ?? 1));
            $perPage = max(10, min(100, (int)($this->request->getGet('per_page') ?? 50)));
            $search = $this->request->getGet('search') ?? '';
            
            $builder = $db->table('invoices')
                ->select('invoices.*, customers.name as customer_name, customers.pppoe_username')
                ->join('customers', 'customers.id = invoices.customer_id', 'left');
            
            // Search
            if (!empty($search)) {
                $builder->groupStart()
                    ->like('invoices.invoice_number', $search)
                    ->orLike('customers.name', $search)
                    ->orLike('customers.pppoe_username', $search)
                    ->groupEnd();
            }
            
            // Count total
            $total = $builder->countAllResults(false);
            $totalPages = ceil($total / $perPage);
            
            // Get data
            $offset = ($page - 1) * $perPage;
            $invoices = $builder->orderBy('invoices.created_at', 'DESC')
                ->limit($perPage, $offset)
                ->get()->getResultArray();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $invoices,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * MikroTik Users with Pagination
     * GET /api/mikrotik/users?page=1&per_page=50
     */
    public function mikrotikUsers()
    {
        try {
            $page = max(1, (int)($this->request->getGet('page') ?? 1));
            $perPage = max(10, min(100, (int)($this->request->getGet('per_page') ?? 50)));
            
            $mikrotik = new \App\Services\MikrotikService();
            
            if (!$mikrotik->isConnected()) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Cannot connect to MikroTik'
                ]);
            }
            
            // Get all users
            $allUsers = $mikrotik->getPppoeSecrets();
            $total = count($allUsers);
            $totalPages = ceil($total / $perPage);
            
            // Paginate
            $offset = ($page - 1) * $perPage;
            $users = array_slice($allUsers, $offset, $perPage);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
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
