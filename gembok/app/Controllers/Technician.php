<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\WhatsappService;
use App\Services\GenieacsService;

class Technician extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Technician Dashboard - showing assigned tickets
     */
    public function index()
    {
        $session = session();
        if ($session->get('admin_role') !== 'technician' && $session->get('admin_role') !== 'admin') {
            return redirect()->to('/admin/login')->with('error', 'Akses ditolak. Anda bukan teknisi.');
        }

        $userId = $session->get('admin_id');
        
        // Get assigned tickets
        $tickets = $this->db->table('trouble_tickets')
            ->select('trouble_tickets.*, customers.name as customer_name, customers.phone as customer_phone, customers.address as customer_address, customers.pppoe_username')
            ->join('customers', 'customers.id = trouble_tickets.customer_id', 'left')
            ->where('assigned_to', $userId)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/technician/dashboard', ['tickets' => $tickets]);
    }

    /**
     * GenieACS - Device management for technicians
     */
    public function genieacs()
    {
        $session = session();
        if ($session->get('admin_role') !== 'technician' && $session->get('admin_role') !== 'admin') {
            return redirect()->to('/admin/login');
        }

        $genie = new GenieacsService();
        $result = $genie->getDevices();
        $devices = $result['body'] ?? [];
        
        return view('admin/genieacs', ['devices' => $devices]);
    }

    /**
     * Map - ONU Location monitoring for technicians
     */
    public function map()
    {
        $session = session();
        if ($session->get('admin_role') !== 'technician' && $session->get('admin_role') !== 'admin') {
            return redirect()->to('/admin/login');
        }

        return view('admin/map');
    }

    /**
     * Get ONU Data for specific PPPoE username (AJAX)
     */
    public function getOnuData()
    {
        $pppoe = $this->request->getGet('pppoe');
        if (empty($pppoe)) {
            return $this->response->setJSON(['success' => false, 'message' => 'PPPoE username empty']);
        }

        try {
            $genie = new GenieacsService();
            $device = $genie->getDeviceByPppoeUsername($pppoe);
            
            if ($device) {
                $params = $device['flatParams'] ?? [];
                
                // Extract relevant info
                $data = [
                    'success' => true,
                    'serial' => $device['_deviceId']['_SerialNumber'] ?? '',
                    'rxPower' => $params['VirtualParameters.RXPower'] ?? 'N/A',
                    'ssid' => $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID'] ?? 'N/A',
                    'online' => !empty($device['_lastInform']) && (time() - strtotime($device['_lastInform']) < 300)
                ];
                return $this->response->setJSON($data);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Device not found for ' . $pppoe]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update ticket status (AJAX)
     */
    public function updateStatus()
    {
        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');
        $notes = $this->request->getPost('notes');
        
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle File Upload
        $file = $this->request->getFile('attachment');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/tickets', $newName);
            $data['attachment'] = $newName;
        }
        
        if ($status === 'resolved') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
            $data['resolution_notes'] = $notes;
        } else {
            $data['notes'] = $notes;
        }

        $this->db->table('trouble_tickets')->where('id', $id)->update($data);

        // Notify Customer & Admin if Resolved
        if ($status === 'resolved') {
            try {
                $ticket = $this->db->table('trouble_tickets')
                    ->select('trouble_tickets.*, customers.name as customer_name, customers.phone as customer_phone')
                    ->join('customers', 'customers.id = trouble_tickets.customer_id', 'left')
                    ->where('trouble_tickets.id', $id)
                    ->get()->getRowArray();
                
                $ws = new \App\Services\WhatsappService();
                $photoLink = isset($data['attachment']) ? base_url('uploads/tickets/' . $data['attachment']) : '';
                
                // Notify Customer
                if (!empty($ticket['customer_phone'])) {
                    $msg = "*LAPORAN SELESAI*\n\n";
                    $msg .= "Yth. {$ticket['customer_name']},\n";
                    $msg .= "Laporan gangguan Anda dengan ID #{$id} telah ditandai sebagai *SELESAI* oleh teknisi kami.\n\n";
                    $msg .= "--------------------------------\n";
                    $msg .= "Keterangan: {$notes}\n";
                    if ($photoLink) {
                        $msg .= "Bukti Foto: {$photoLink}\n";
                    }
                    $msg .= "--------------------------------\n\n";
                    $msg .= "Mohon hubungi kami kembali jika masih ada kendala. Terima kasih.";
                    
                    $ws->sendMessage($ticket['customer_phone'], $msg);
                }

            } catch (\Exception $e) {
                log_message('error', 'Failed to send resolution notify: ' . $e->getMessage());
            }
        }
        
        return $this->response->setJSON(['success' => true, 'message' => 'Status berhasil diupdate']);
    }

    /**
     * List all technicians (Admin view)
     */
    public function list()
    {
        if (session()->get('admin_role') !== 'admin') {
            return redirect()->to('/admin')->with('error', 'Hanya admin yang bisa akses menu ini.');
        }

        $technicians = $this->db->table('users')
            ->where('role', 'technician')
            ->get()->getResultArray();

        return view('admin/technician/list', ['technicians' => $technicians]);
    }

    /**
     * Add new technician
     */
    public function add()
    {
        if (session()->get('admin_role') !== 'admin') {
            return $this->response->setStatusCode(403);
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'name' => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
            'role' => 'technician',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('users')->insert($data);
        return redirect()->to('/admin/technicians')->with('msg', 'Teknisi berhasil ditambahkan');
    }

    /**
     * Update technician data
     */
    public function update($id)
    {
        if (session()->get('admin_role') !== 'admin') {
            return $this->response->setStatusCode(403);
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'name' => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        // Only update password if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->db->table('users')->where('id', $id)->where('role', 'technician')->update($data);
        return redirect()->to('/admin/technicians')->with('msg', 'Data teknisi berhasil diperbarui');
    }

    /**
     * Delete technician
     */
    public function delete($id)
    {
        if (session()->get('admin_role') !== 'admin') {
            return $this->response->setStatusCode(403);
        }

        $this->db->table('users')->where('id', $id)->where('role', 'technician')->delete();
        return redirect()->to('/admin/technicians')->with('msg', 'Teknisi berhasil dihapus');
    }
}
