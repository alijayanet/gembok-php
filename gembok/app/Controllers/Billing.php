<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\MikrotikService;
use App\Services\WhatsappService; // Import Class
use CodeIgniter\Database\Exceptions\DatabaseException;

class Billing extends BaseController
{
    protected $db;
    protected $mikrotik;
    protected $wa;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->mikrotik = new MikrotikService();
        $this->wa = new WhatsappService();
    }

    public function index()
    {
        return $this->invoices();
    }

    /**
     * Packages Management
     */
    public function packages()
    {
        $packages = $this->db->table('packages')
            ->select('packages.*, COUNT(customers.id) as customer_count')
            ->join('customers', 'customers.package_id = packages.id', 'left')
            ->groupBy('packages.id')
            ->get()->getResultArray();
            
        // Get Profiles from Mikrotik
        $profiles = [];
        $mikrotikConnected = false;
        $mikrotikError = '';
        
        if ($this->mikrotik->isConnected()) {
            $mikrotikConnected = true;
            $profiles = $this->mikrotik->getPppoeProfiles();
        } else {
            $mikrotikError = $this->mikrotik->getLastError() ?: 'Tidak dapat terhubung ke MikroTik';
        }
        
        // If not connected or empty, provide some dummy data so UI doesn't break
        if (empty($profiles)) {
            $profiles = [
                ['name' => 'default'],
                ['name' => 'up-10Mbps'],
                ['name' => 'up-20Mbps'],
                ['name' => 'up-50Mbps'],
                ['name' => 'isolir'],
            ];
            if (!$mikrotikConnected) {
                session()->setFlashdata('error', 'Tidak dapat terhubung ke MikroTik: ' . $mikrotikError . '. Menampilkan profile dummy.');
            }
        }

        $data = [
            'packages' => $packages,
            'mikrotik_profiles' => $profiles,
            'mikrotik_connected' => $mikrotikConnected
        ];

        return view('admin/billing/packages', $data);
    }

    public function addPackage()
    {
        $data = [
            'name' => $this->request->getPost('name'),
            'price' => $this->request->getPost('price'),
            'profile_normal' => $this->request->getPost('profile_normal'),
            'profile_isolir' => $this->request->getPost('profile_isolir'),
            'description' => $this->request->getPost('description'),
        ];

        $this->db->table('packages')->insert($data);
        
        session()->setFlashdata('msg', '✅ Paket berhasil ditambahkan');
        return redirect()->to('/admin/billing/packages');
    }

    public function updatePackage($id)
    {
        $data = [
            'name' => $this->request->getPost('name'),
            'price' => $this->request->getPost('price'),
            'profile_normal' => $this->request->getPost('profile_normal'),
            'profile_isolir' => $this->request->getPost('profile_isolir'),
            'description' => $this->request->getPost('description'),
        ];

        $this->db->table('packages')->where('id', $id)->update($data);
        
        session()->setFlashdata('msg', '✅ Paket berhasil diperbarui');
        return redirect()->to('/admin/billing/packages');
    }

    public function deletePackage($id)
    {
        // Check if package has customers
        $customerCount = $this->db->table('customers')->where('package_id', $id)->countAllResults();
        
        if ($customerCount > 0) {
            session()->setFlashdata('error', '❌ Tidak dapat menghapus paket yang masih memiliki ' . $customerCount . ' pelanggan');
            return redirect()->to('/admin/billing/packages');
        }
        
        $this->db->table('packages')->where('id', $id)->delete();
        
        session()->setFlashdata('msg', '✅ Paket berhasil dihapus');
        return redirect()->to('/admin/billing/packages');
    }

    /**
     * Customers Management
     */
    public function customers()
    {
        $customers = $this->db->table('customers')
            ->select('customers.*, packages.name as package_name, packages.price as package_price')
            ->join('packages', 'packages.id = customers.package_id', 'left')
            ->orderBy('customers.id', 'DESC')
            ->get()->getResultArray();
            
        $packages = $this->db->table('packages')->get()->getResultArray();

        $data = [
            'customers' => $customers,
            'packages' => $packages
        ];

        return view('admin/billing/customers', $data);
    }

    public function addCustomer()
    {
        $data = [
            'name' => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
            'pppoe_username' => $this->request->getPost('pppoe_username'),
            'package_id' => $this->request->getPost('package_id'),
            'isolation_date' => $this->request->getPost('isolation_date'),
            'lat' => $this->request->getPost('lat'),
            'lng' => $this->request->getPost('lng'),
            'address' => $this->request->getPost('address'),
            'status' => 'active' 
        ];

        $this->db->table('customers')->insert($data);
        
        // If coordinates provided, also add to onu_locations map
        if (!empty($data['lat']) && !empty($data['lng'])) {
            $this->db->table('onu_locations')->insert([
                'name' => $data['name'],
                'serial_number' => $data['pppoe_username'] . '-ONU', // Placeholder serial
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'customer_id' => $this->db->insertID()
            ]);
        }
        
        session()->setFlashdata('msg', 'Pelanggan berhasil ditambahkan');
        return redirect()->to('/admin/billing/customers');
    }

    /**
     * Invoices Management
     */
    public function invoices()
    {
        $invoices = $this->db->table('invoices')
            ->select('invoices.*, customers.name as customer_name, customers.pppoe_username, customers.isolation_date')
            ->join('customers', 'customers.id = invoices.customer_id', 'left')
            ->orderBy('invoices.created_at', 'DESC')
            ->get()->getResultArray();

        $data = [
            'invoices' => $invoices
        ];

        return view('admin/billing/invoices', $data);
    }
    
    /**
     * Generate Invoices for all active customers
     * Can be called manually or via Cron
     */
    public function generateInvoices()
    {
        // Get all active customers that don't have invoice for this month
        $customers = $this->db->table('customers')
            ->select('customers.*, packages.name as package_name, packages.price')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('customers.status', 'active')
            ->get()->getResultArray();
            
        $currentMonth = date('Y-m');
        $count = 0;
        
        foreach ($customers as $c) {
            // Check if invoice exists for this month
            // We can check by created_at like '2023-10-%'
            $exists = $this->db->table('invoices')
                ->where('customer_id', $c['id'])
                ->where("created_at LIKE '{$currentMonth}%'")
                ->countAllResults();
                
            // Also check if invoice exists for CURRENT month specifically (to be safe)
            // Or usually billing is generated on 1st of month.
            
            if ($exists == 0) {
                // Determine due date based on isolation_date (e.g., current month + isolation date)
                $isoDay = $c['isolation_date'] ?? 20;
                $dueDate = date('Y-m-') . str_pad($isoDay, 2, '0', STR_PAD_LEFT);
                
                $data = [
                    'customer_id' => $c['id'],
                    'amount' => $c['price'],
                    'description' => 'Tagihan Internet Bulan ' . date('F Y'),
                    'due_date' => $dueDate,
                    'paid' => 0,
                    'status' => 'pending'
                ];

                $this->db->table('invoices')->insert($data);
                $newId = $this->db->insertID();
                $count++;
                
                // Send WA Notification
                $invoiceData = $data;
                $invoiceData['id'] = $newId;
                $invoiceData['package_name'] = $c['package_name'];
                $invoiceData['created_at'] = date('Y-m-d H:i:s');
                
                $this->wa->sendInvoice($c, $invoiceData);
            }
        }
        
        session()->setFlashdata('msg', "Berhasil generate {$count} invoice baru untuk bulan ini");
        return redirect()->to('/admin/billing/invoices');
    }
    
    /**
     * Process Invoice Payment
     */
    public function payInvoice($id)
    {
        // Get invoice details
        $invoice = $this->db->table('invoices')->where('id', $id)->get()->getRowArray();
        
        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan');
        }
        
        // Update invoice to paid
        $this->db->table('invoices')->where('id', $id)->update([
            'paid' => 1,
            'status' => 'paid',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Restore service if customer was isolated
        $customer = $this->db->table('customers')
            ->select('customers.*, packages.profile_normal')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('customers.id', $invoice['customer_id'])
            ->get()->getRowArray();
            
        if ($customer) {
            $this->_unisolateCustomer($customer);
            // Send WA Notification
            $this->wa->sendPaymentSuccess($customer, $invoice);
        }
        
        session()->setFlashdata('msg', 'Pembayaran berhasil dikonfirmasi. Layanan pelangan telah diaktifkan kembali.');
        return redirect()->to('/admin/billing/invoices');
    }

    /**
     * Unisolate Customer WITHOUT Marking Invoice as Paid
     * Use Case: Customer promises to pay later / request extension
     */
    public function unisolateOnly($invoiceId)
    {
        // Get invoice detail to find customer
        $invoice = $this->db->table('invoices')->where('id', $invoiceId)->get()->getRowArray();
        
        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan');
        }
        
        // Restore service
        $customer = $this->db->table('customers')
            ->select('customers.*, packages.profile_normal')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('customers.id', $invoice['customer_id'])
            ->get()->getRowArray();
            
        if ($customer) {
            $this->_unisolateCustomer($customer);
            session()->setFlashdata('msg', 'Layanan PELANGGAN TELAH DIBUKA, namun Invoice tetap BELUM LUNAS.');
        } else {
            session()->setFlashdata('error', 'Gagal menemukan data pelanggan.');
        }
        
        return redirect()->to('/admin/billing/invoices');
    }
    
    /**
     * Check for overdue invoices and isolate customers
     * Cron Job function
     */
    public function checkIsolation()
    {
        $today = date('Y-m-d');
        
        // Find customers with UNPAID invoices past due date
        // AND currently status is 'active'
        $overdue = $this->db->table('invoices')
            ->select('invoices.id as invoice_id, customers.*, packages.profile_isolir')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('invoices.paid', 0)
            ->where('invoices.due_date <', $today)
            ->where('customers.status', 'active')
            ->groupBy('customers.id') // Group by customer to avoid multiple isolates
            ->get()->getResultArray();
            
        $count = 0;
        foreach ($overdue as $bs) {
            $this->_isolateCustomer($bs);
            // Send WA Notification
            $this->wa->sendIsolation($bs);
            $count++;
        }
        
        session()->setFlashdata('msg', "Proses isolir selesai. {$count} pelanggan diisolir.");
        return redirect()->to('/admin/billing/customers');
    }
    
    /**
     * Helper: Unisolate Customer (Restore Service)
     */
    private function _unisolateCustomer($customer)
    {
        // Update DB
        $this->db->table('customers')->where('id', $customer['id'])->update(['status' => 'active']);
        
        // Enable in MikroTik
        if (!empty($customer['pppoe_username']) && !empty($customer['profile_normal'])) {
            try {
                // Change profile back to normal
                $this->mikrotik->setPppoeUserProfile($customer['pppoe_username'], $customer['profile_normal']);
                // Ensure enabled
                $this->mikrotik->enablePppoeSecret($customer['pppoe_username']);
            } catch (\Exception $e) {
                // Log error
            }
        }
    }
    
    /**
     * Helper: Isolate Customer
     */
    private function _isolateCustomer($customer)
    {
        // Update DB
        $this->db->table('customers')->where('id', $customer['id'])->update(['status' => 'isolated']);
        
        // Isolate in MikroTik
        if (!empty($customer['pppoe_username'])) {
            try {
                if (!empty($customer['profile_isolir'])) {
                    // Change profile to isolir
                    $this->mikrotik->setPppoeUserProfile($customer['pppoe_username'], $customer['profile_isolir']);
                } else {
                    // If no isolir profile, just disable
                    $this->mikrotik->disablePppoeSecret($customer['pppoe_username']);
                }
            } catch (\Exception $e) {
                // Log error
            }
        }
    }
    
    public function unisolateManual($id)
    {
        $customer = $this->db->table('customers')
            ->select('customers.*, packages.profile_normal')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('customers.id', $id)
            ->get()->getRowArray();
            
        if ($customer) {
            $this->_unisolateCustomer($customer);
            session()->setFlashdata('msg', 'Pelanggan berhasil dibuka isolirnya.');
        }
        
        return redirect()->to('/admin/billing/customers');
    }
    
    /**
     * Print Invoice View
     */
    public function printInvoice($id)
    {
        $invoice = $this->db->table('invoices')
            ->select('invoices.*, customers.name as customer_name, customers.address, customers.pppoe_username, packages.name as package_name')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->join('packages', 'packages.id = customers.package_id')
            ->where('invoices.id', $id)
            ->get()->getRowArray();

        if (!$invoice) {
            return "Invoice not found";
        }
        
        // Get settings for company info
        $settings = $this->db->table('settings')->get()->getResultArray();
        $company = [];
        foreach ($settings as $s) {
            $company[$s['key']] = $s['value'];
        }
        
        return view('admin/billing/invoice_print', [
            'invoice' => $invoice,
            'company' => $company
        ]);
    }

    /**
     * Cron Handler - Bypass Auth with Secret
     * Access: /cron/run/isolir?key=YOUR_SECRET_KEY
     */
    public function cronHandler($action)
    {
        // Simple security check
        // You should define CRON_SECRET in .env
        $secret = $_GET['key'] ?? '';
        $envSecret = getenv('CRON_SECRET') ?: 'gembok_secret_cron_123';
        
        if ($secret !== $envSecret) {
            return $this->response->setStatusCode(403)->setBody('Forbidden: Invalid Cron Key');
        }

        switch ($action) {
            case 'isolir':
                $this->checkIsolation();
                return "Isolation Check Completed";
                
            case 'invoice':
                $this->generateInvoices();
                return "Invoice Generation Completed";
                
            default:
                return "Unknown action";
        }
    }
}
