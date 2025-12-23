<?php
namespace App\Controllers;

use App\Services\ConfigService;
use CodeIgniter\Controller;

class AdminSettings extends BaseController
{
    protected $config;

    public function __construct()
    {
        $this->config = new ConfigService();
    }

    /** Show settings form */
    public function index()
    {
        $keys = [
            'WHATSAPP_API_URL',
            'WHATSAPP_TOKEN',
            'WHATSAPP_VERIFY_TOKEN',
            'GENIEACS_URL',
            'GENIEACS_USERNAME',
            'GENIEACS_PASSWORD',
            'GENIEACS_TOKEN',
            'MIKROTIK_HOST',
            'MIKROTIK_PORT',
            'MIKROTIK_USER',
            'MIKROTIK_PASS',
            // Tripay Payment Gateway
            'TRIPAY_MERCHANT_CODE',
            'TRIPAY_API_KEY',
            'TRIPAY_PRIVATE_KEY',
            'TRIPAY_MODE', // sandbox or production
            // Midtrans Payment Gateway
            'MIDTRANS_SERVER_KEY',
            'MIDTRANS_CLIENT_KEY',
            'MIDTRANS_MODE', // sandbox or production
        ];
        
        // Get current base URL for webhook URLs
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";
        
        $data = $this->config->getAll($keys);
        $data['webhookUrls'] = [
            'whatsapp' => "{$baseUrl}/webhook/whatsapp",
            'payment' => "{$baseUrl}/webhook/payment",
            'midtrans' => "{$baseUrl}/webhook/midtrans",
        ];
        $data['baseUrl'] = $baseUrl;
        
        return view('admin/settings', $data);
    }

    /** Save settings */
    public function save()
    {
        $allowed = [
            'WHATSAPP_API_URL','WHATSAPP_TOKEN','WHATSAPP_VERIFY_TOKEN',
            'GENIEACS_URL','GENIEACS_USERNAME','GENIEACS_PASSWORD','GENIEACS_TOKEN',
            'MIKROTIK_HOST','MIKROTIK_PORT','MIKROTIK_USER','MIKROTIK_PASS',
            'TRIPAY_MERCHANT_CODE','TRIPAY_API_KEY','TRIPAY_PRIVATE_KEY','TRIPAY_MODE',
            'MIDTRANS_SERVER_KEY','MIDTRANS_CLIENT_KEY','MIDTRANS_MODE',
        ];
        foreach ($this->request->getPost() as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $this->config->set($key, $value);
            }
        }
        session()->setFlashdata('msg', '✅ Pengaturan berhasil disimpan dan langsung aktif!');
        return redirect()->to('/admin/settings');
    }
}
