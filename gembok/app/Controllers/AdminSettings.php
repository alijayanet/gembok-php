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
        $role = session()->get('admin_role');
        
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
            'TRIPAY_MERCHANT_CODE',
            'TRIPAY_API_KEY',
            'TRIPAY_PRIVATE_KEY',
            'TRIPAY_MODE',
            'MIDTRANS_SERVER_KEY',
            'MIDTRANS_CLIENT_KEY',
            'MIDTRANS_MODE',
            'TELEGRAM_BOT_TOKEN',
            'TELEGRAM_ADMIN_CHAT_IDS',
        ];
        
        // Get current base URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$protocol}://{$host}";
        
        $data = $this->config->getAll($keys);
        $data['webhookUrls'] = [
            'whatsapp' => "{$baseUrl}/webhook/whatsapp",
            'payment' => "{$baseUrl}/webhook/payment",
            'midtrans' => "{$baseUrl}/webhook/midtrans",
            'telegram' => "{$baseUrl}/webhook/telegram",
        ];
        $data['baseUrl'] = $baseUrl;
        
        // Get current admin info
        $db = \Config\Database::connect();
        $adminId = session()->get('admin_id');
        $data['adminUser'] = $db->table('users')->where('id', $adminId)->get()->getRowArray();
        $data['admin_role'] = $role;
        
        // Fetch Webhook Logs (Only for Admin)
        $data['webhookLogs'] = [];
        if ($role === 'admin') {
            try {
                 $data['webhookLogs'] = $db->table('webhook_logs')
                    ->orderBy('created_at', 'DESC')
                    ->limit(50)
                    ->get()
                    ->getResultArray();
            } catch (\Exception $e) {}
        }
        
        return view('admin/settings', $data);
    }

    /** Save settings */
    public function save()
    {
        if (session()->get('admin_role') !== 'admin') {
            return $this->response->setStatusCode(403);
        }
        $allowed = [
            'WHATSAPP_API_URL','WHATSAPP_TOKEN','WHATSAPP_VERIFY_TOKEN',
            'GENIEACS_URL','GENIEACS_USERNAME','GENIEACS_PASSWORD','GENIEACS_TOKEN',
            'MIKROTIK_HOST','MIKROTIK_PORT','MIKROTIK_USER','MIKROTIK_PASS',
            'TRIPAY_MERCHANT_CODE','TRIPAY_API_KEY','TRIPAY_PRIVATE_KEY','TRIPAY_MODE',
            'MIDTRANS_SERVER_KEY','MIDTRANS_CLIENT_KEY','MIDTRANS_MODE',
            'TELEGRAM_BOT_TOKEN','TELEGRAM_ADMIN_CHAT_IDS',
        ];
        foreach ($this->request->getPost() as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $this->config->set($key, $value);
            }
        }
        session()->setFlashdata('msg', '✅ Pengaturan berhasil disimpan dan langsung aktif!');
        return redirect()->to('/admin/settings');
    }
    
    /**
     * Update Admin Profile (Username, Name, Email)
     */
    public function updateProfile()
    {
        $db = \Config\Database::connect();
        $adminId = session()->get('admin_id');
        
        $username = trim($this->request->getPost('username'));
        $name = trim($this->request->getPost('name'));
        $email = trim($this->request->getPost('email'));
        $phone = trim($this->request->getPost('phone'));
        
        // Validate inputs
        if (empty($username) || empty($name)) {
            session()->setFlashdata('error', '❌ Username dan Nama wajib diisi.');
            return redirect()->to('/admin/settings');
        }
        
        // Check if username already exists (excluding current user)
        $existingUser = $db->table('users')
            ->where('username', $username)
            ->where('id !=', $adminId)
            ->get()->getRowArray();
            
        if ($existingUser) {
            session()->setFlashdata('error', '❌ Username sudah digunakan oleh user lain.');
            return redirect()->to('/admin/settings');
        }
        
        // Update user profile
        $db->table('users')->where('id', $adminId)->update([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update session
        session()->set([
            'admin_username' => $username,
            'admin_name' => $name
        ]);
        
        session()->setFlashdata('msg', '✅ Profil berhasil diperbarui!');
        return redirect()->to('/admin/settings');
    }
    
    /**
     * Change Admin Password
     */
    public function changePassword()
    {
        $db = \Config\Database::connect();
        $adminId = session()->get('admin_id');
        
        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            session()->setFlashdata('error', '❌ Semua field password wajib diisi.');
            return redirect()->to('/admin/settings');
        }
        
        // Check password length
        if (strlen($newPassword) < 6) {
            session()->setFlashdata('error', '❌ Password baru minimal 6 karakter.');
            return redirect()->to('/admin/settings');
        }
        
        // Check passwords match
        if ($newPassword !== $confirmPassword) {
            session()->setFlashdata('error', '❌ Password baru dan konfirmasi tidak cocok.');
            return redirect()->to('/admin/settings');
        }
        
        // Get current user
        $user = $db->table('users')->where('id', $adminId)->get()->getRowArray();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            session()->setFlashdata('error', '❌ Password saat ini salah.');
            return redirect()->to('/admin/settings');
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->table('users')->where('id', $adminId)->update([
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        session()->setFlashdata('msg', '✅ Password berhasil diubah!');
        return redirect()->to('/admin/settings');
    }
    
    /**
     * Set Telegram Webhook
     */
    public function setTelegramWebhook()
    {
        // Get bot token from settings
        $botToken = $this->config->get('TELEGRAM_BOT_TOKEN');
        
        if (empty($botToken)) {
            session()->setFlashdata('error', '❌ Bot Token belum dikonfigurasi. Silakan isi Bot Token terlebih dahulu.');
            return redirect()->to('/admin/settings');
        }
        
        // Get webhook URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $webhookUrl = "{$protocol}://{$host}/webhook/telegram";
        
        // Use TelegramService to set webhook
        try {
            $telegram = new \App\Services\TelegramService();
            $result = $telegram->setWebhook($webhookUrl);
            
            if ($result && isset($result['ok']) && $result['ok'] === true) {
                session()->setFlashdata('msg', '✅ Telegram Webhook berhasil diset ke: ' . $webhookUrl);
            } else {
                $errorMsg = $result['description'] ?? 'Unknown error';
                session()->setFlashdata('error', '❌ Gagal set webhook: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            session()->setFlashdata('error', '❌ Error: ' . $e->getMessage());
        }
        
        return redirect()->to('/admin/settings');
    }
    
    /**
     * Delete Telegram Webhook
     */
    public function deleteTelegramWebhook()
    {
        // Get bot token from settings
        $botToken = $this->config->get('TELEGRAM_BOT_TOKEN');
        
        if (empty($botToken)) {
            session()->setFlashdata('error', '❌ Bot Token belum dikonfigurasi.');
            return redirect()->to('/admin/settings');
        }
        
        // Use TelegramService to delete webhook
        try {
            $telegram = new \App\Services\TelegramService();
            $result = $telegram->deleteWebhook();
            
            if ($result && isset($result['ok']) && $result['ok'] === true) {
                session()->setFlashdata('msg', '✅ Telegram Webhook berhasil dihapus. Bot sekarang menggunakan polling mode.');
            } else {
                $errorMsg = $result['description'] ?? 'Unknown error';
                session()->setFlashdata('error', '❌ Gagal hapus webhook: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            session()->setFlashdata('error', '❌ Error: ' . $e->getMessage());
        }
        
        return redirect()->to('/admin/settings');
    }
}
