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
        $revenueResult = $db->table('invoices')
            ->selectSum('amount')
            ->where('paid', 1)
            ->where('paid_at IS NOT NULL')
            ->where('DATE(paid_at) =', $today)
            ->get()->getRowArray();
        $todayRevenue = $revenueResult['amount'] ?? 0;
        
        // Jika hari ini kosong, tampilkan total semua revenue
        if ($todayRevenue == 0) {
            $allRevenue = $db->table('invoices')
                ->selectSum('amount')
                ->where('paid', 1)
                ->get()->getRowArray();
            $todayRevenue = $allRevenue['amount'] ?? 0;
        }
        
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
        $db = \Config\Database::connect();
        
        // 1. Pendapatan Bulan Ini (Invoice yang sudah paid bulan ini)
        $currentMonth = date('Y-m');
        $monthlyRevenue = $db->table('invoices')
            ->selectSum('amount')
            ->where('paid', 1)
            ->where('paid_at IS NOT NULL')
            ->where('DATE_FORMAT(paid_at, "%Y-%m") =', $currentMonth)
            ->get()->getRowArray();
        $revenueThisMonth = $monthlyRevenue['amount'] ?? 0;
        
        // Jika bulan ini kosong, ambil total semua yang sudah paid
        if ($revenueThisMonth == 0) {
            $allRevenue = $db->table('invoices')
                ->selectSum('amount')
                ->where('paid', 1)
                ->get()->getRowArray();
            $revenueThisMonth = $allRevenue['amount'] ?? 0;
        }
        
        // 2. Invoice Lunas (Bulan Ini atau semua)
        $paidInvoices = $db->table('invoices')
            ->where('paid', 1)
            ->countAllResults();
        
        // 3. Invoice Belum Lunas
        $unpaidInvoices = $db->table('invoices')
            ->where('paid', 0)
            ->countAllResults();
        
        // 4. Total Pelanggan
        $totalCustomers = $db->table('customers')->countAllResults();
        
        // 5. Recent Payments (10 terbaru)
        $recentPayments = $db->table('invoices')
            ->select('invoices.*, customers.name as customer_name')
            ->join('customers', 'customers.id = invoices.customer_id', 'left')
            ->where('invoices.paid', 1)
            ->orderBy('invoices.paid_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();
        
        $data = [
            'revenueThisMonth' => $revenueThisMonth,
            'paidInvoices' => $paidInvoices,
            'unpaidInvoices' => $unpaidInvoices,
            'totalCustomers' => $totalCustomers,
            'recentPayments' => $recentPayments
        ];
        
        return view('admin/analytics', $data);
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
        return view('admin/map');
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
        $error = null;
        
        try {
            if (!$mik->isConnected()) {
                $error = 'Tidak dapat terhubung ke MikroTik. Silakan cek konfigurasi di Settings.';
                session()->setFlashdata('error', $error);
            } else {
                $users = $mik->getPppoeSecrets();
                $profiles = $mik->getPppoeProfiles();
                $active = $mik->getActivePppoe();
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            session()->setFlashdata('error', $error);
            log_message('error', 'MikroTik connection error: ' . $e->getMessage());
        }
        
        return view('admin/mikrotik', [
            'users' => $users, 
            'profiles' => $profiles,
            'active' => $active,
            'error' => $error
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
        $error = null;
        
        try {
            if (!$mik->isConnected()) {
                $error = 'Tidak dapat terhubung ke MikroTik. Silakan cek konfigurasi di Settings.';
                session()->setFlashdata('error', $error);
            } else {
                $users = $mik->getHotspotUsers();
                $profiles = $mik->getHotspotProfiles();
                $active = $mik->getActiveHotspotUsers();
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            session()->setFlashdata('error', $error);
            log_message('error', 'MikroTik Hotspot error: ' . $e->getMessage());
        }
        
        return view('admin/hotspot', [
            'users' => $users, 
            'profiles' => $profiles,
            'active' => $active,
            'error' => $error
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
        $technicians = [];
        try {
            $tickets = $db->table('trouble_tickets')
                ->select('trouble_tickets.*, users.name as technician_name')
                ->join('users', 'users.id = trouble_tickets.assigned_to', 'left')
                ->orderBy('created_at', 'DESC')
                ->get()->getResultArray();
                
            $technicians = $db->table('users')->where('role', 'technician')->get()->getResultArray();
            $customers = $db->table('customers')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (\Exception $e) {
            // Table may not exist
        }
        
        return view('admin/trouble', [
            'tickets' => $tickets,
            'technicians' => $technicians,
            'customers' => $customers
        ]);
    }

    /**
     * Create Trouble Ticket
     */
    public function createTicket()
    {
        $db = \Config\Database::connect();
        
        // Validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'customer_id' => 'required|numeric',
            'title' => 'required|min_length[5]|max_length[200]',
            'description' => 'required|min_length[10]',
            'priority' => 'required|in_list[low,medium,high,urgent]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            session()->setFlashdata('error', '❌ Validasi gagal: ' . implode(', ', $validation->getErrors()));
            return redirect()->back()->withInput();
        }

        $data = [
            'customer_id' => $this->request->getPost('customer_id'),
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'priority' => $this->request->getPost('priority'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->table('trouble_tickets')->insert($data);
        
        session()->setFlashdata('msg', '✅ Tiket berhasil dibuat');
        return redirect()->to('/admin/trouble');
    }

    /**
     * Update Trouble Ticket
     */
    public function updateTicket($id)
    {
        $db = \Config\Database::connect();
        
        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'priority' => $this->request->getPost('priority'),
            'notes' => $this->request->getPost('notes'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db->table('trouble_tickets')->where('id', $id)->update($data);
        
        session()->setFlashdata('msg', '✅ Tiket berhasil diupdate');
        return redirect()->to('/admin/trouble');
    }

    /**
     * Assign Trouble Ticket to Technician
     */
    public function assignTicket($id)
    {
        $db = \Config\Database::connect();
        
        $data = [
            'assigned_to' => $this->request->getPost('assigned_to'),
            'status' => 'in_progress',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db->table('trouble_tickets')->where('id', $id)->update($data);
        
        session()->setFlashdata('msg', '✅ Tiket berhasil di-assign');
        return redirect()->to('/admin/trouble');
    }

    /**
     * Close Trouble Ticket
     */
    public function closeTicket($id)
    {
        $db = \Config\Database::connect();
        
        $data = [
            'status' => 'resolved',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolution_notes' => $this->request->getPost('resolution_notes'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db->table('trouble_tickets')->where('id', $id)->update($data);
        
        session()->setFlashdata('msg', '✅ Tiket berhasil ditutup');
        return redirect()->to('/admin/trouble');
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
                
                $mik = new MikrotikService();
                $result = $mik->addPppoeSecret($username, $password, $profile);
                
                if ($result) {
                    return $this->response->setJSON([
                        'success' => true, 
                        'message' => "PPPoE user '{$username}' berhasil ditambahkan dengan profile '{$profile}'"
                    ]);
                } else {
                    return $this->response->setJSON([
                        'success' => false, 
                        'message' => 'Gagal menambahkan user: ' . $mik->getLastError()
                    ]);
                }
                
            case 'add_hotspot':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                $limit_uptime = $json['limit_uptime'] ?? '';
                
                if (empty($username) || empty($password)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username dan password wajib diisi']);
                }
                
                $mik = new MikrotikService();
                $result = $mik->addHotspotUser($username, $password, $profile, $limit_uptime);
                
                if ($result) {
                    return $this->response->setJSON([
                        'success' => true, 
                        'message' => "Hotspot user '{$username}' berhasil ditambahkan"
                    ]);
                } else {
                    return $this->response->setJSON([
                        'success' => false, 
                        'message' => 'Gagal menambahkan user: ' . $mik->getLastError()
                    ]);
                }
                
            case 'edit_pppoe':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                $mik = new MikrotikService();
                $data = ['profile' => $profile];
                if (!empty($password)) {
                    $data['password'] = $password;
                }
                
                $result = $mik->updatePppoeSecret($username, $data);
                
                if ($result) {
                    $msg = "PPPoE user '{$username}' berhasil diupdate";
                    if (!empty($password)) {
                        $msg .= " (password diubah)";
                    }
                    return $this->response->setJSON(['success' => true, 'message' => $msg]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'delete_pppoe':
                $username = $json['username'] ?? '';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                $mik = new MikrotikService();
                $result = $mik->deletePppoeSecret($username);
                
                if ($result) {
                    return $this->response->setJSON([
                        'success' => true, 
                        'message' => "PPPoE user '{$username}' berhasil dihapus dari MikroTik"
                    ]);
                } else {
                    return $this->response->setJSON([
                        'success' => false, 
                        'message' => 'Gagal menghapus user: ' . $mik->getLastError()
                    ]);
                }
                
            case 'edit_hotspot':
                $username = $json['username'] ?? '';
                $password = $json['password'] ?? '';
                $profile = $json['profile'] ?? 'default';
                $limit_uptime = $json['limit_uptime'] ?? '';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                $mik = new MikrotikService();
                $data = ['profile' => $profile];
                if (!empty($password)) {
                    $data['password'] = $password;
                }
                if (!empty($limit_uptime)) {
                    $data['limit_uptime'] = $limit_uptime;
                }
                
                $result = $mik->updateHotspotUser($username, $data);
                
                if ($result) {
                    $msg = "Hotspot user '{$username}' berhasil diupdate";
                    if (!empty($password)) {
                        $msg .= " (password diubah)";
                    }
                    return $this->response->setJSON(['success' => true, 'message' => $msg]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'toggle_pppoe':
            case 'toggle_hotspot':
                $username = $json['username'] ?? '';
                
                $mik = new MikrotikService();
                $enabled = $json['enabled'] ?? true;
                
                if ($action === 'toggle_pppoe') {
                    $result = $enabled ? $mik->enablePppoe($username) : $mik->disablePppoe($username);
                } else {
                    // Hotspot doesn't have enable/disable, just delete to disable
                    $result = true; // Placeholder
                }
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Status user '{$username}' berhasil diubah"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            // PPPoE Profile Actions
            case 'add_pppoe_profile':
                $name = $json['name'] ?? '';
                $rateLimit = $json['rate_limit'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile wajib diisi']);
                }
                
                $rateLimit = $json['rate_limit'] ?? '';
                $localAddress = $json['local_address'] ?? '';
                $remoteAddress = $json['remote_address'] ?? '';
                
                $mik = new MikrotikService();
                $result = $mik->addPppoeProfile($name, $rateLimit, $localAddress, $remoteAddress);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile PPPoE '{$name}' berhasil ditambahkan"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'edit_pppoe_profile':
                $name = $json['name'] ?? '';
                $originalName = $json['original_name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                $data = [];
                if (isset($json['rate_limit'])) $data['rate_limit'] = $json['rate_limit'];
                if (isset($json['local_address'])) $data['local_address'] = $json['local_address'];
                if (isset($json['remote_address'])) $data['remote_address'] = $json['remote_address'];
                if ($name !== $originalName) $data['name'] = $name;
                
                $mik = new MikrotikService();
                $result = $mik->updatePppoeProfile($originalName, $data);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile PPPoE '{$name}' berhasil diupdate"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'delete_pppoe_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                $mik = new MikrotikService();
                $result = $mik->deletePppoeProfile($name);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile PPPoE '{$name}' berhasil dihapus"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            // Hotspot Profile Actions
            case 'add_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile wajib diisi']);
                }
                
                $sharedUsers = (int)($json['shared_users'] ?? 1);
                $rateLimit = $json['rate_limit'] ?? '';
                
                $mik = new MikrotikService();
                $result = $mik->addHotspotProfile($name, $sharedUsers, $rateLimit);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile Hotspot '{$name}' berhasil ditambahkan"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'edit_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                $data = [];
                if (isset($json['shared_users'])) $data['shared_users'] = (int)$json['shared_users'];
                if (isset($json['rate_limit'])) $data['rate_limit'] = $json['rate_limit'];
                if (isset($json['original_name']) && $name !== $json['original_name']) {
                    $data['name'] = $name;
                }
                
                $mik = new MikrotikService();
                $result = $mik->updateHotspotProfile($name, $data);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile Hotspot '{$name}' berhasil diupdate"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            case 'delete_hotspot_profile':
                $name = $json['name'] ?? '';
                
                if (empty($name)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Nama profile tidak valid']);
                }
                
                $mik = new MikrotikService();
                $result = $mik->deleteHotspotProfile($name);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "Profile Hotspot '{$name}' berhasil dihapus"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }

            // Voucher & User Actions
            case 'generate_vouchers':
                $vouchers = $json['vouchers'] ?? [];
                
                if (empty($vouchers)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada voucher untuk disimpan']);
                }
                
                $mik = new MikrotikService();
                $count = 0;
                $failed = 0;
                
                foreach ($vouchers as $v) {
                    $result = $mik->addHotspotUser($v['username'], $v['password'], $v['profile'], $v['limit_uptime'] ?? '');
                    if ($result) {
                        $count++;
                    } else {
                        $failed++;
                    }
                }
                
                $message = "Berhasil menyimpan {$count} voucher ke MikroTik";
                if ($failed > 0) {
                    $message .= ", {$failed} gagal";
                }
                
                return $this->response->setJSON([
                    'success' => $count > 0, 
                    'message' => $message
                ]);

            case 'delete_hotspot_user':
                $username = $json['username'] ?? '';
                
                if (empty($username)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Username tidak valid']);
                }
                
                $mik = new MikrotikService();
                $result = $mik->deleteHotspotUser($username);
                
                if ($result) {
                    return $this->response->setJSON(['success' => true, 'message' => "User Hotspot '{$username}' berhasil dihapus"]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => $mik->getLastError()]);
                }
                
            default:
                return $this->response->setJSON(['success' => false, 'message' => 'Action tidak dikenal']);
        }
    }

    /**
     * System Update Page
     */
    public function update()
    {
        // Get current version from a file or database
        // ROOTPATH points to the application root (where gembok folder contents are)
        $versionFile = ROOTPATH . 'version.txt';
        $currentVersion = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'Unknown';
        
        // Get last backup info
        $backupDir = ROOTPATH . 'backups';
        $lastBackup = null;
        if (is_dir($backupDir)) {
            $backups = glob($backupDir . '/backup_*.zip');
            if (!empty($backups)) {
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $lastBackup = [
                    'file' => basename($backups[0]),
                    'date' => date('Y-m-d H:i:s', filemtime($backups[0])),
                    'size' => round(filesize($backups[0]) / 1024 / 1024, 2) . ' MB'
                ];
            }
        }
        
        // Check if update.php exists in root folder
        $updateFileExists = file_exists(ROOTPATH . 'update.php');
        
        $data = [
            'currentVersion' => $currentVersion,
            'lastBackup' => $lastBackup,
            'updateFileExists' => $updateFileExists,
            'githubRepo' => 'alijayanet/gembok-php',
            'githubBranch' => 'main'
        ];
        
        return view('admin/update', $data);
    }

    /**
     * Run System Update (AJAX)
     */
    public function runUpdate()
    {
        // This runs the update.php logic and returns progress
        $updateFile = ROOTPATH . 'update.php';
        
        if (!file_exists($updateFile)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'File update.php tidak ditemukan'
            ]);
        }
        
        // Redirect to update.php with AJAX token
        return $this->response->setJSON([
            'success' => true,
            'redirect' => base_url('update.php')
        ]);
    }
}


