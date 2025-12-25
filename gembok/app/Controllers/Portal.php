<?php
namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\PackageModel;
use App\Services\GenieacsService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Portal Controller - Customer Self-Service
 * 
 * Features:
 * - Login dengan nomor telepon
 * - Dashboard: Status paket, pembayaran, ONU
 * - Edit WiFi: SSID & Password (separate buttons)
 */
class Portal extends BaseController
{
    protected $customerModel;
    protected $invoiceModel;
    protected $packageModel;
    protected $genieacs;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->invoiceModel  = new InvoiceModel();
        $this->packageModel  = model('App\Models\PackageModel');
        $this->genieacs      = new GenieacsService();
    }

    /**
     * Dashboard - All-in-One Customer Portal
     * Shows: Package info, Payment status, ONU status, WiFi settings
     */
    public function index()
    {
        $session = session();
        $phone = $session->get('customer_phone') ?? $this->request->getGet('phone');
        
        if (!$phone) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        // Get customer data
        $customer = $this->customerModel->where('phone', $phone)->first();
        if (!$customer) {
            return redirect()->to('/login')->with('error', 'Data pelanggan tidak ditemukan');
        }
        
        // Save to session if not yet
        if (!$session->get('customer_phone')) {
            $session->set([
                'customer_id' => $customer['id'],
                'customer_phone' => $customer['phone'],
                'customer_name' => $customer['name'],
            ]);
        }
        
        // Get package info
        $package = null;
        if ($customer['package_id']) {
            $package = $this->packageModel->find($customer['package_id']);
        }
        
        // Get current month invoice
        $currentMonth = date('Y-m');
        $currentInvoice = $this->invoiceModel
            ->where('customer_id', $customer['id'])
            ->like('created_at', $currentMonth, 'after')
            ->orderBy('created_at', 'DESC')
            ->first();
        
        // Get all invoices (last 6 months)
        $invoices = $this->invoiceModel
            ->where('customer_id', $customer['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(6)
            ->findAll();
        
        // Get ONU data from GenieACS using PPPoE username
        $onuData = null;
        $onuSerial = null;
        
        if (!empty($customer['pppoe_username'])) {
            try {
                // Clear cache untuk memastikan data fresh
                $this->genieacs->clearCache();
                
                // Log untuk debugging
                log_message('info', 'Portal: Looking for device with PPPoE username: ' . $customer['pppoe_username']);
                
                // Use dedicated method untuk find by PPPoE username (without cache)
                $device = $this->genieacs->getDeviceByPppoeUsername($customer['pppoe_username']);
                
                if ($device) {
                    log_message('info', 'Portal: Device found for PPPoE: ' . $customer['pppoe_username']);
                    
                    // Use flattened parameters for easier access
                    $params = $device['flatParams'] ?? [];
                    $deviceId = $device['_deviceId'] ?? [];
                    
                    // Extract ONU information
                    $onuSerial = $deviceId['_SerialNumber'] ?? ($params['InternetGatewayDevice.DeviceInfo.SerialNumber'] ?? '');
                    
                    $onuData = [
                        'serial' => $onuSerial,
                        'model' => $params['InternetGatewayDevice.DeviceInfo.ModelName'] ?? 'N/A',
                        'manufacturer' => $params['InternetGatewayDevice.DeviceInfo.Manufacturer'] ?? 'N/A',
                        'lastInform' => $device['_lastInform'] ?? null,
                        'online' => !empty($device['_lastInform']),
                        // WiFi Info (SSID 1)
                        'ssid' => $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID'] ?? '',
                        'wifiPassword' => $params['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase'] ?? '',
                        // Signal strength
                        'rxPower' => $params['VirtualParameters.RXPower'] ?? 'N/A',
                        // PPPoE Info
                        'pppoeUsername' => $params['VirtualParameters.pppoeUsername'] ?? $params['VirtualParameters.pppoeUsername2'] ?? '',
                        'pppoeIP' => $params['VirtualParameters.pppoeIP'] ?? '',
                    ];
                } else {
                    log_message('warning', 'Portal: Device NOT found for PPPoE: ' . $customer['pppoe_username']);
                }
            } catch (\Exception $e) {
                log_message('error', 'GenieACS error in Portal for user ' . $customer['pppoe_username'] . ': ' . $e->getMessage());
            }
        } else {
            log_message('warning', 'Portal: Customer ' . $customer['name'] . ' has no PPPoE username');
        }
        
        return view('portal/dashboard', [
            'customer' => $customer,
            'package' => $package,
            'currentInvoice' => $currentInvoice,
            'invoices' => $invoices,
            'onuData' => $onuData,
        ]);
    }

    /**
     * Update WiFi SSID (AJAX)
     */
    public function updateSsid()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        $session = session();
        $customerId = $session->get('customer_id');
        
        if (!$customerId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Session expired. Silakan login kembali.']);
        }
        
        $newSsid = $this->request->getPost('ssid');
        
        if (empty($newSsid) || strlen($newSsid) < 3) {
            return $this->response->setJSON(['success' => false, 'message' => 'SSID minimal 3 karakter']);
        }
        
        // Get customer
        $customer = $this->customerModel->find($customerId);
        if (!$customer || empty($customer['pppoe_username'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data customer tidak lengkap']);
        }
        
        // Find ONU by PPPoE username
        try {
            log_message('info', "updateSsid: Looking for device with PPPoE: {$customer['pppoe_username']}");
            
            $device = $this->genieacs->getDeviceByPppoeUsername($customer['pppoe_username']);
            
            if (!$device) {
                log_message('warning', "updateSsid: Device not found for PPPoE: {$customer['pppoe_username']}");
                return $this->response->setJSON(['success' => false, 'message' => 'Perangkat ONU tidak ditemukan di GenieACS']);
            }
            
            // Get serial number from device ID
            $serial = $device['_id'] ?? '';
            
            log_message('info', "updateSsid: Device found, ID: {$serial}");
            
            if (empty($serial)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Serial number tidak valid']);
            }
            
            // Update SSID via GenieACS
            $result = $this->genieacs->setParameter($serial, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', $newSsid);
            
            log_message('info', "updateSsid: GenieACS response code: {$result['code']}");
            
            if ($result['code'] === 200 || $result['code'] === 202) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'SSID berhasil diubah menjadi: ' . $newSsid . '. Perubahan akan diterapkan dalam beberapa saat.'
                ]);
            } else {
                $errorMsg = $result['body']['message'] ?? $result['error'] ?? 'Unknown error';
                log_message('error', "updateSsid: Failed with code {$result['code']}: {$errorMsg}");
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Gagal mengubah SSID. Error: ' . $errorMsg
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Portal updateSsid exception: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update WiFi Password (AJAX)
     */
    public function updatePassword()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        $session = session();
        $customerId = $session->get('customer_id');
        
        if (!$customerId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Session expired. Silakan login kembali.']);
        }
        
        $newPassword = $this->request->getPost('password');
        
        if (empty($newPassword) || strlen($newPassword) < 8) {
            return $this->response->setJSON(['success' => false, 'message' => 'Password minimal 8 karakter']);
        }
        
        // Get customer
        $customer = $this->customerModel->find($customerId);
        if (!$customer || empty($customer['pppoe_username'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data customer tidak lengkap']);
        }
        
        // Find ONU by PPPoE username
        try {
            $device = $this->genieacs->getDeviceByPppoeUsername($customer['pppoe_username']);
            
            if (!$device) {
                return $this->response->setJSON(['success' => false, 'message' => 'Perangkat ONU tidak ditemukan di GenieACS']);
            }
            
            // Get serial number from device ID
            $serial = $device['_id'] ?? '';
            
            if (empty($serial)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Serial number tidak valid']);
            }
            
            // Update Password via GenieACS
            $result = $this->genieacs->setParameter($serial, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase', $newPassword);
            
            if ($result['code'] === 200 || $result['code'] === 202) {
                return $this->response->setJSON(['success' => true, 'message' => 'Password WiFi berhasil diubah']);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengubah password. Coba lagi nanti. (Code: ' . $result['code'] . ')']);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Portal updatePassword error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Change Portal Login Password
     */
    public function changePortalPassword()
    {
        if (!$this->request->isAJAX()) {
             return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        $session = session();
        $customerId = $session->get('customer_id');
        $newPass = $this->request->getPost('portal_password');
        
        if (empty($newPass)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Password tidak boleh kosong']);
        }
        
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        
        $this->customerModel->update($customerId, ['portal_password' => $hashed]);
        
        return $this->response->setJSON(['success' => true, 'message' => 'Password Login Portal berhasil diperbarui.']);
    }

    // -------------------------------------------------------------------------
    // Payment Logic
    // -------------------------------------------------------------------------

    public function payment($invoiceId)
    {
        $phone = session()->get('customer_phone');
        if (!$phone) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        $db = \Config\Database::connect();
        $invoice = $db->table('invoices')->where('id', $invoiceId)->get()->getRowArray();
        if (!$invoice) return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
        if ($invoice['status'] === 'paid') return redirect()->back()->with('success', 'Invoice ini sudah lunas.');
        
        $tripay = new \App\Services\TripayService();
        $channelsResponse = $tripay->getChannels();
        $channels = $channelsResponse['data'] ?? [];
        $groupedChannels = [];
        foreach ($channels as $chn) { if ($chn['active']) $groupedChannels[$chn['group']][] = $chn; }
        return view('portal/payment', ['invoice' => $invoice, 'groupedChannels' => $groupedChannels]);
    }

    public function processPayment()
    {
        $phone = session()->get('customer_phone');
        if (!$phone) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        $invoiceId = $this->request->getPost('invoice_id');
        $method = $this->request->getPost('method');
        if (!$invoiceId || !$method) return redirect()->back()->with('error', 'Metode pembayaran tidak valid.');

        $db = \Config\Database::connect();
        $invoice = $db->table('invoices')->select('invoices.*, customers.name, customers.email, packages.name as pkg_name')->join('customers', 'customers.id = invoices.customer_id')->join('packages', 'packages.id = customers.package_id', 'left')->where('invoices.id', $invoiceId)->get()->getRowArray();
        if (!$invoice) return redirect()->back()->with('error', 'Invoice gagal diproses.');
        
        $tripay = new \App\Services\TripayService();
        $orderItems = [['sku' => 'PKG-' . $invoice['id'], 'name' => 'Tagihan Internet ' . ($invoice['pkg_name'] ?? 'Paket Internet'), 'price' => (int)$invoice['amount'], 'quantity' => 1]];
        $payload = ['method' => $method, 'merchant_ref' => $invoice['invoice_number'], 'amount' => (int)$invoice['amount'], 'customer_name' => $invoice['name'], 'customer_email' => $invoice['email'] ?? 'customer@gembok.net', 'customer_phone' => $phone, 'order_items' => $orderItems, 'return_url' => base_url('portal')];
        
        $response = $tripay->createTransaction($payload);
        if ($response['success']) return redirect()->to($response['data']['checkout_url']);
        else return redirect()->back()->with('error', 'Tripay Error: ' . ($response['message'] ?? 'Gagal'));
    }


    /**
     * Invoices Page - Daftar Tagihan
     */
    public function invoices()
    {
        $session = session();
        $phone = $session->get('customer_phone');
        
        if (!$phone) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        // Get customer data
        $customer = $this->customerModel->where('phone', $phone)->first();
        if (!$customer) {
            return redirect()->to('/login')->with('error', 'Data pelanggan tidak ditemukan');
        }
        
        // Get customer invoices
        $invoices = $this->invoiceModel
            ->where('customer_id', $customer['id'])
            ->orderBy('due_date', 'DESC')
            ->findAll();
        
        return view('portal/invoices', [
            'customer' => $customer,
            'invoices' => $invoices
        ]);
    }

    /**
     * Syarat & Ketentuan Page
     */
    public function tos()
    {
        return view('portal/tos');
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda telah logout');
    }
}
