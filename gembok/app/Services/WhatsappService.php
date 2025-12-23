<?php
namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class WhatsappService
{
    private $db;
    private $apiUrl;
    private $token;
    private $enabled = false;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $settings = $this->db->table('settings')->get()->getResultArray();
        $config = [];
        foreach ($settings as $s) {
            $config[$s['key']] = $s['value'];
        }

        $this->apiUrl = $config['WHATSAPP_API_URL'] ?? '';
        $this->token = $config['WHATSAPP_TOKEN'] ?? '';
        
        // Enable only if URL is set
        if (!empty($this->apiUrl)) {
            $this->enabled = true;
        }
    }

    /**
     * Send generic text message
     */
    public function sendMessage($phone, $message)
    {
        if (!$this->enabled || empty($phone)) {
            return false;
        }

        // Format phone number (ID specific)
        // Convert 08xxx to 628xxx
        if (substr($phone, 0, 2) === '08') {
            $phone = '62' . substr($phone, 1);
        }

        // Prepare Payload (Standard JSON keys, adjust as needed for specific provider)
        $data = [
            'phone' => $phone,
            'message' => $message,
            'token' => $this->token, // Some APIs pass token in body
        ];

        // Init CURL
        $ch = curl_init($this->apiUrl . '/send-message'); // Adjust endpoint path
        
        // Jika URL sudah full path endpoint, gunakan langsung
        // if (strpos($this->apiUrl, 'http') === 0 && strpos($this->apiUrl, 'send') !== false) {
             $ch = curl_init($this->apiUrl);
        // }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $this->token // Some APIs use Header Auth
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Send New Invoice Notification
     */
    public function sendInvoice($customer, $invoice)
    {
        if (empty($customer['phone'])) return false;

        $month = date('F Y', strtotime($invoice['created_at']));
        $amount = number_format($invoice['amount'], 0, ',', '.');
        $dueDate = date('d M Y', strtotime($invoice['due_date']));
        $invId = str_pad($invoice['id'], 6, '0', STR_PAD_LEFT);

        $msg = "*TAGIHAN INTERNET BARU*\n\n";
        $msg .= "Yth. {$customer['name']},\n";
        $msg .= "Tagihan internet Anda untuk bulan *{$month}* telah terbit.\n\n";
        $msg .= "--------------------------------\n";
        $msg .= "No. Invoice : #{$invId}\n";
        $msg .= "Paket : {$invoice['package_name']}\n";
        $msg .= "Jumlah : *Rp {$amount}*\n";
        $msg .= "Jatuh Tempo : {$dueDate}\n";
        $msg .= "--------------------------------\n\n";
        $msg .= "Mohon segera lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari isolir otomatis.\n";
        $msg .= "Terima kasih.";

        return $this->sendMessage($customer['phone'], $msg);
    }

    /**
     * Send Payment Success Notification
     */
    public function sendPaymentSuccess($customer, $invoice)
    {
        if (empty($customer['phone'])) return false;

        $amount = number_format($invoice['amount'], 0, ',', '.');
        $date = date('d M Y H:i');
        $invId = str_pad($invoice['id'], 6, '0', STR_PAD_LEFT);

        $msg = "*PEMBAYARAN DITERIMA*\n\n";
        $msg .= "Yth. {$customer['name']},\n";
        $msg .= "Terima kasih, pembayaran tagihan Anda telah kami terima.\n\n";
        $msg .= "--------------------------------\n";
        $msg .= "No. Invoice : #{$invId}\n";
        $msg .= "Status : *LUNAS*\n";
        $msg .= "Jumlah : Rp {$amount}\n";
        $msg .= "Waktu : {$date}\n";
        $msg .= "--------------------------------\n\n";
        $msg .= "Layanan internet Anda aktif kembali. Terima kasih telah berlangganan.";

        return $this->sendMessage($customer['phone'], $msg);
    }
    
    /**
     * Send Isolation Notification
     */
    public function sendIsolation($customer)
    {
         if (empty($customer['phone'])) return false;
         
         $msg = "*PEMBERITAHUAN ISOLIR*\n\n";
         $msg .= "Yth. {$customer['name']},\n";
         $msg .= "Layanan internet Anda saat ini Terisolir (Non-Aktif) karena melewati batas waktu pembayaran.\n\n";
         $msg .= "Mohon segera lunasi tagihan Anda agar internet dapat digunakan kembali.\n";
         $msg .= "Abaikan pesan ini jika sudah membayar.";
         
         return $this->sendMessage($customer['phone'], $msg);
    }
}
