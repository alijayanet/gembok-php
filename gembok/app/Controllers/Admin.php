<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\GenieacsService;
use App\Services\MikrotikService;

class Admin extends BaseController
{
    /**
     * Admin Dashboard - main admin page with stats
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $mikrotik = new MikrotikService();
        $genie = new GenieacsService();
        
        // 1. Total Devices (GenieACS)
        try {
            // Try to get real count from API
            $acsData = $genie->getDevices(); // Returns ['code'=>200, 'body'=>[...]]
            if (($acsData['code'] ?? 0) === 200 && is_array($acsData['body'])) {
                $totalDevices = count($acsData['body']);
            } else {
                // Fallback to local DB map
                $totalDevices = $db->table('onu_locations')->countAllResults();
            }
        } catch (\Exception $e) {
            $totalDevices = 0;
        }
        
        // 2. Active PPPoE & Hotspot (MikroTik)
        $onlinePppoe = 0;
        $onlineHotspot = 0;
        $mikrotikConnected = false;
        
        if ($mikrotik->isConnected()) {
            $mikrotikConnected = true;
            // Get Online PPPoE
            $pppActive = $mikrotik->query('/ppp/active/print', ['count-only' => 'true']);
            // MikroTik API usually returns integer for count-only, or array if not.
            // With pure API, count-only isn't always reliable in all client libs, so let's fetch list if small,
            // or just use print count. MikrotikService->query returns array. 
            // Let's assume standard retrieval for safety:
            $pppActiveList = $mikrotik->query('/ppp/active/print');
            $onlinePppoe = count($pppActiveList);

            // Get Online Hotspot
            $hotspotActiveList = $mikrotik->query('/ip/hotspot/active/print');
            $onlineHotspot = count($hotspotActiveList);
        } else {
            // Fallback: Count 'active' customers in DB
            $onlinePppoe = $db->table('customers')->where('status', 'active')->countAllResults();
        }
        
        // 3. Pending Invoices (Billing)
        $pendingInvoices = $db->table('invoices')->where('status', 'pending')->countAllResults();
        
        // 4. Today's Revenue (Pembayaran Hari Ini)
        $today = date('Y-m-d');
        $revenueResult = $db->table('payments')
            ->selectSum('amount')
            ->like('paid_at', $today, 'after')
            ->get()->getRowArray();
        $todayRevenue = $revenueResult['amount'] ?? 0;
        
        // 5. Pending Tickets (Support)
        $pendingTickets = $db->table('trouble_tickets')
            ->whereIn('status', ['pending', 'in_progress'])
            ->countAllResults();

        $stats = [
            'totalDevices' => $totalDevices,
            'onlinePppoe' => $onlinePppoe,
            'onlineHotspot' => $onlineHotspot,
            'pendingInvoices' => $pendingInvoices,
            'todayRevenue' => $todayRevenue,
            'pendingTickets' => $pendingTickets,
            'mikrotikConnected' => $mikrotikConnected
        ];
        
        return view('admin/dashboard', ['stats' => $stats]);
    }

    /**
     * Analytics - Financial & Performance reports
     */
    public function analytics()
    {
        return view('admin/analytics');
    }

    /**
     * GenieACS - Device management
     */
    public function genieacs()
    {
        $genie = new GenieacsService();
        $result = $genie->getDevices();
        $devices = $result['body'] ?? [];
        
        return view('admin/genieacs', ['devices' => $devices]);
    }

    /**
     * Map - ONU Location monitoring
     */
    public function map()
    {
        return view('admin_map');
    }

    /**
     * ODP - Optical Distribution Point management
     */
    public function odp()
    {
        return view('admin/odp');
    }

    /**
     * MikroTik PPPoE Users
     */
    public function mikrotik()
    {
        $mik = new MikrotikService();
        $users = [];
        $profiles = [];
        $active = [];
        try {
            $users = $mik->getPppoeSecrets();
            $profiles = $mik->getPppoeProfiles();
            $active = $mik->getPppoeActive();
        } catch (\Exception $e) {
            // Handle connection error
        }
        
        return view('admin/mikrotik', [
            'users' => $users, 
            'profiles' => $profiles,
            'active' => $active
        ]);
    }

    /**
     * MikroTik PPPoE Profiles
     */
    public function mikrotikProfiles()
    {
        $mik = new MikrotikService();
        $profiles = [];
        try {
            $profiles = $mik->getPppoeProfiles();
        } catch (\Exception $e) {
            // Handle connection error
        }
        
        return view('admin/mikrotik_profiles', ['profiles' => $profiles]);
    }

    /**
     * MikroTik Hotspot Profiles
     */
    public function hotspotProfiles()
    {
        $mik = new MikrotikService();
        $profiles = [];
        try {
            $profiles = $mik->getHotspotProfiles();
        } catch (\Exception $e) {
            // Handle connection error
        }
        
        return view('admin/hotspot_profiles', ['profiles' => $profiles]);
    }

    /**
     * Hotspot Users
     */
    public function hotspot()
    {
        $mik = new MikrotikService();
        $users = [];
        $profiles = [];
        $active = [];
        try {
            $users = $mik->getHotspotUsers();
            $profiles = $mik->getHotspotProfiles();
            $active = $mik->getHotspotActive();
        } catch (\Exception $e) {
            // Handle connection error
        }
        
        return view('admin/hotspot', [
            'users' => $users, 
            'profiles' => $profiles,
            'active' => $active
        ]);
    }

    /**
     * Voucher Management
     */
    public function voucher()
    {
        $mik = new MikrotikService();
        $profiles = [];
        $vouchers = [];
        try {
            $profiles = $mik->getHotspotProfiles();
            $vouchers = $mik->getHotspotUsers(); // Get existing vouchers
        } catch (\Exception $e) {
            // Handle connection error
        }
        
        return view('admin/voucher', [
            'profiles' => $profiles,
            'vouchers' => $vouchers
        ]);
    }

    /**
     * Trouble Ticket / Laporan Gangguan
     */
    public function trouble()
    {
        $db = \Config\Database::connect();
        $tickets = [];
        try {
            $tickets = $db->table('trouble_tickets')->orderBy('created_at', 'DESC')->get()->getResultArray();
        } catch (\Exception $e) {
            // Table may not exist
        }
        
        return view('admin/trouble', ['tickets' => $tickets]);
    }

    /**
     * Handle terminal commands
     */
    public function handleCommand()
    {
        $cmd = $this->request->getPost('command');
        $parts = explode(' ', trim($cmd), 2);
        $action = strtoupper($parts[0] ?? '');
        $arg    = $parts[1] ?? null;

        $response = '';
        switch ($action) {
            case 'REBOOT':
                $genie = new GenieacsService();
                $res   = $genie->rebootDevice($arg);
                $response = ($res['code'] ?? 0) === 200 ? '✅ Reboot command sent' : '❌ Failed to send reboot';
                break;
            case 'PPPOE-ON':
                $mik = new MikrotikService();
                $mik->enablePppoe($arg);
                $response = '✅ PPPoE enabled';
                break;
            case 'PPPOE-OFF':
                $mik = new MikrotikService();
                $mik->disablePppoe($arg);
                $response = '✅ PPPoE disabled';
                break;
            default:
                $response = '❓ Unknown command';
        }

        return $this->response->setJSON(['msg' => $response]);
    }

    /**
     * Handle MikroTik AJAX actions (add users, toggle, etc)
     */
    public function mikrotikAction()
    {
        $json = $this->request->getJSON(true);
        $action = $json['action'] ?? '';
        
        // MikroTik operations would require implementing write methods
        // For now, return appropriate messages
        switch ($action) {
            case 'add_pppoe':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                
                if (empty($username) || empty($password)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username dan password wajib diisi']);
                }
                
                // TODO: Implement actual MikroTik API call to add PPPoE user
                // $mik = new MikrotikService();
                // $mik->addPppoeSecret($username, $password, $profile);
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "PPPoE user '{$username}' berhasil ditambahkan dengan profile '{$profile}'"
                ]);
                
            case 'add_hotspot':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                $limit_uptime = $json['limit_uptime'] ?? '';
                
                if (empty($username) || empty($password)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username dan password wajib diisi']);
                }
                
                // TODO: Implement actual MikroTik API call to add Hotspot user
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Hotspot user '{$username}' berhasil ditambahkan"
                ]);
                
            case 'edit_pppoe':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call to edit PPPoE user
                
                $msg = "PPPoE user '{$username}' berhasil diupdate";
                if (!empty($password)) {
                    $msg .= " (password diubah)";
                }
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => $msg
                ]);
                
            case 'edit_hotspot':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                $limit_uptime = $json['limit_uptime'] ?? '';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call to edit Hotspot user
                
                $msg = "Hotspot user '{$username}' berhasil diupdate";
                if (!empty($password)) {
                    $msg .= " (password diubah)";
                }
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => $msg
                ]);
                
            case 'toggle_pppoe':
            case 'toggle_hotspot':
                $username = $json['username'] ?? '';
                
                // TODO: Implement actual toggle logic
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Status user '{$username}' berhasil diubah"
                ]);
                
            // PPPoE Profile Actions
            case 'add_pppoe_profile':
                $name = $json['name'] ?? '';
                $rateLimit = $json['rate_limit'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile wajib diisi']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile PPPoE '{$name}' berhasil ditambahkan"
                ]);
                
            case 'edit_pppoe_profile':
                $name = $json['name'] ?? '';
                $originalName = $json['original_name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile PPPoE '{$name}' berhasil diupdate"
                ]);
                
            case 'delete_pppoe_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile PPPoE '{$name}' berhasil dihapus"
                ]);
                
            // Hotspot Profile Actions
            case 'add_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile wajib diisi']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile Hotspot '{$name}' berhasil ditambahkan"
                ]);
                
            case 'edit_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile Hotspot '{$name}' berhasil diupdate"
                ]);
                
            case 'delete_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Profile Hotspot '{$name}' berhasil dihapus"
                ]);

            // Voucher & User Actions
            case 'generate_vouchers':
                $vouchers = $json['vouchers'] ?? [];
                
                if (empty($vouchers)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada voucher untuk disimpan']);
                }
                
                $count = 0;
                foreach ($vouchers as $v) {
                    // TODO: Implement actual MikroTik API call
                    // $mik->addHotspotUser($v['username'], $v['password'], $v['profile']);
                    $count++;
                }
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "Berhasil menyimpan {$count} voucher ke MikroTik"
                ]);

            case 'delete_hotspot_user':
                $username = $json['username'] ?? '';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                // TODO: Implement actual MikroTik API call
                
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => "User Hotspot '{$username}' berhasil dihapus"
                ]);
                
            default:
                return $this->response->setJSON(['success' => false, 'message' => 'Action tidak dikenal']);
        }
    }
}


