<?php
namespace App\Controllers;

use App\Services\TripayService;
use App\Services\MikrotikService;
use App\Services\TelegramService;

class Webhook extends BaseController
{
    public function payment()
    {
        $json = file_get_contents('php://input');
        $callbackSignature = $this->request->getHeaderLine('X-Callback-Signature');
        
        log_message('info', 'Tripay Webhook Received: ' . $json);

        $tripay = new TripayService();
        if (!$tripay->validateCallback($json, $callbackSignature)) {
            $this->logToDb('tripay', $json, 401, 'Invalid signature');
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Invalid signature']);
        }

        $data = json_decode($json, true);
        if (!$data) {
            $this->logToDb('tripay', $json, 400, 'Invalid JSON');
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid JSON']);
        }

        $merchantRef = $data['merchant_ref'];
        $status = $data['status'];

        if ($status === 'PAID') {
            $this->handlePaidInvoice($merchantRef, $data);
        } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
            $this->handleFailedInvoice($merchantRef, $status);
        }

        $this->logToDb('tripay', $json, 200, "Processed: $status");
        return $this->response->setJSON(['success' => true]);
    }

    private function handlePaidInvoice($invoiceNumber, $paymentData)
    {
        $db = \Config\Database::connect();
        
        // 1. Update Invoice Status
        $invoice = $db->table('invoices')->getWhere(['invoice_number' => $invoiceNumber])->getRowArray();
        
        if (!$invoice) {
            log_message('error', "Invoice not found: {$invoiceNumber}");
            return;
        }

        // Update invoice
        $db->table('invoices')->where('id', $invoice['id'])->update([
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'payment_method' => $paymentData['payment_method'] ?? 'Tripay',
            'payment_ref' => $paymentData['reference'] ?? '',
        ]);
        
        log_message('info', "Invoice {$invoiceNumber} marked as PAID.");

        // 2. Auto-Activate Service Logic (Un-isolir)
        // Check if all due invoices for this customer are paid
        $customerId = $invoice['customer_id'];
        
        // Get Customer
        $customer = $db->table('customers')->where('id', $customerId)->get()->getRowArray();
        
        if ($customer && $customer['status'] === 'isolated') {
            // Check any other unpaid overdue invoices
            $unpaidCount = $db->table('invoices')
                ->where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->where('due_date <', date('Y-m-d'))
                ->countAllResults();
                
            if ($unpaidCount === 0) {
                // Restore Connection
                $this->unisolateCustomer($customer);
            }
        }
    }
    
    private function handleFailedInvoice($invoiceNumber, $status)
    {
        $db = \Config\Database::connect();
        // Maybe log or update status if you track cancelled invoices
        // Usually we keep them as 'unpaid' until regenerate or just mark logs
        log_message('info', "Invoice {$invoiceNumber} status: {$status}");
    }

    private function unisolateCustomer($customer)
    {
        $db = \Config\Database::connect();
        $mikrotik = new MikrotikService();
        
        // Get package for normal profile
        $package = $db->table('packages')->where('id', $customer['package_id'])->get()->getRowArray();
        
        if ($package && !empty($package['profile']) && !empty($customer['pppoe_username'])) {
            // Restore MikroTik Profile
            $result = $mikrotik->setPppoeUserProfile($customer['pppoe_username'], $package['profile']);
            
            if ($result) {
                // Update DB Status
                $db->table('customers')->where('id', $customer['id'])->update([
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_message('info', "Customer UN-ISOLATED: {$customer['name']} ({$customer['pppoe_username']})");
            } else {
                log_message('error', "Failed to un-isolate customer Mikrotik: {$customer['pppoe_username']}");
            }
        }
    }
    
    /**
     * Telegram Bot Webhook Handler
     * Handle incoming Telegram messages and callbacks
     */
    public function telegram()
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) {
            return $this->response->setJSON(['ok' => false]);
        }
        
        $telegram = new TelegramService();
        $mikrotik = new MikrotikService();
        
        // Handle regular messages
        if (isset($update['message'])) {
            $message = $update['message'];
            // Only respond to private chats (ignore groups / supergroups)
            if (($message['chat']['type'] ?? '') !== 'private') {
                // Silently ignore group messages
                return;
            }
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            
            $this->processTelegramCommand($chatId, $text, $telegram, $mikrotik);
        }
        
        // Handle callback queries (inline button clicks)
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $chatId = $callback['message']['chat']['id'];
            $messageId = $callback['message']['message_id'];
            $data = $callback['data'];
            $callbackId = $callback['id'];
            
            $this->processTelegramCallback($chatId, $messageId, $data, $callbackId, $telegram, $mikrotik);
        }
        
        return $this->response->setJSON(['ok' => true]);
    }
    
    /**
     * Process Telegram text commands
     */
    private function processTelegramCommand($chatId, $text, $telegram, $mikrotik)
    {
        $text = trim($text);
        $command = strtoupper($text);
        
        // Check admin (simple check - can be enhanced)
        $isAdmin = $this->isTelegramAdmin($chatId);
        
        // === START COMMAND ===
        if ($command === '/START') {
            // Check if admin for conditional menu
            $isAdmin = $this->isTelegramAdmin($chatId);
            
            if ($isAdmin) {
                // Admin menu with quick actions
                $keyboard = $telegram->inlineKeyboard([
                    [
                        $telegram->inlineButton('📊 INFO', 'cmd:INFO'),
                        $telegram->inlineButton('📋 LAPORAN', 'cmd:LAPORAN')
                    ],
                    [
                        $telegram->inlineButton('🎫 Voucher', 'menu:voucher'),
                        $telegram->inlineButton('💰 Billing', 'menu:billing')
                    ],
                    [
                        $telegram->inlineButton('⚙️ MikroTik', 'menu:mikrotik'),
                        $telegram->inlineButton('❓ Help', 'menu:help')
                    ]
                ]);
            } else {
                // User menu (simplified)
                $keyboard = $telegram->inlineKeyboard([
                    [
                        $telegram->inlineButton('🎫 Generate Voucher', 'menu:voucher'),
                        $telegram->inlineButton('📋 Harga Paket', 'menu:harga')
                    ],
                    [
                        $telegram->inlineButton('⚙️ MikroTik Menu', 'menu:mikrotik'),
                        $telegram->inlineButton('❓ Help', 'menu:help')
                    ]
                ]);
            }
            
            $msg = "🤖 *GEMBOK BOT*\n\n";
            $msg .= "Selamat datang di Bot Admin Gembok!\n\n";
            
            if ($isAdmin) {
                $msg .= "🔐 *Admin Mode Active*\n\n";
                $msg .= "Quick Actions:\n";
                $msg .= "• INFO - System statistics\n";
                $msg .= "• LAPORAN - Daily report\n\n";
            }
            
            $msg .= "Pilih menu atau ketik perintah:\n";
            $msg .= "• /help - Lihat bantuan\n";
            $msg .= "• PING - Test MikroTik\n";
            $msg .= "• STATUS - Cek status";
            
            $telegram->sendMessage($chatId, $msg, 'Markdown', $keyboard);
            return;
        }
        
        // === HELP COMMAND ===
        if ($command === '/HELP' || $command === 'HELP') {
            $this->sendTelegramHelp($chatId, $telegram, $isAdmin);
            return;
        }
        
        // === PING - Test MikroTik Connection ===
        if ($command === 'PING') {
            if ($mikrotik->isConnected()) {
                $telegram->sendMessage($chatId, "✅ *MIKROTIK ONLINE*\n\nKoneksi ke MikroTik berhasil!");
            } else {
                $telegram->sendMessage($chatId, "❌ *MIKROTIK OFFLINE*\n\nGagal koneksi: " . $mikrotik->getLastError());
            }
            return;
        }
        
        // === STATUS - Check MikroTik Status ===
        if ($command === 'STATUS') {
            if (!$mikrotik->isConnected()) {
                $telegram->sendMessage($chatId, "❌ *MIKROTIK OFFLINE*");
                return;
            }
            
            try {
                $activePppoe = count($mikrotik->getActivePppoe());
                $activeHotspot = count($mikrotik->getActiveHotspotUsers());
                
                $msg = "📊 *STATUS MIKROTIK*\n\n";
                $msg .= "🌐 PPPoE Aktif: *{$activePppoe}*\n";
                $msg .= "📡 Hotspot Aktif: *{$activeHotspot}*\n";
                $msg .= "✅ Status: *ONLINE*";
                
                $telegram->sendMessage($chatId, $msg);
            } catch (\Exception $e) {
                $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
            }
            return;
        }

// The following block was removed because it prevented admin commands (e.g., EDIT) from being processed.
// $simpleCommands = ['/START', '/HELP', 'HELP', 'PING', 'STATUS'];
// if (!in_array($command, $simpleCommands)) {
//     // No response for unknown text messages
//     return;
// }
        
        // === Admin Commands ===
        if (!$isAdmin) {
            $telegram->sendMessage($chatId, "⛔ *ACCESS DENIED*\n\nAnda tidak memiliki akses admin.\n\nKetik /help untuk melihat perintah yang tersedia.");
            return;
        }
        
        // === TAMBAH - Add PPPoE Secret ===
        if (preg_match('/^TAMBAH\s+(\S+)\s+(\S+)\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            $password = $matches[2];
            $profile = $matches[3];
            
            $result = $mikrotik->addPppoeSecret($username, $password, $profile);
            
            if ($result) {
                $telegram->sendMessage($chatId, "✅ *BERHASIL MENAMBAH USER*\n\nUsername: `{$username}`\nPassword: `{$password}`\nProfile: `{$profile}`");
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === EDIT - Update PPPoE Profile  ===
        if (preg_match('/^EDIT\s+(\S+)\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            $newProfile = $matches[2];
            
            $result = $mikrotik->updatePppoeSecret($username, ['profile' => $newProfile]);
            
            if ($result) {
                $telegram->sendMessage($chatId, "✅ *BERHASIL UPDATE*\n\nUsername: `{$username}`\nProfile Baru: `{$newProfile}`");
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === HAPUS - Delete PPPoE Secret ===
        if (preg_match('/^HAPUS\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            
            $result = $mikrotik->deletePppoeSecret($username);
            
            if ($result) {
                $telegram->sendMessage($chatId, "✅ *BERHASIL HAPUS*\n\nUsername: `{$username}` telah dihapus.");
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === BILLING COMMANDS (Admin Only) ===
        
        // === CUSTOMER - Search Customer Info ===
        if (preg_match('/^CUSTOMER\s+(.+)$/i', $text, $matches)) {
            $search = trim($matches[1]);
            $db = \Config\Database::connect();
            
            // Search by name or phone
            $customer = $db->table('customers')
                ->select('customers.*, packages.name as package_name, packages.price as package_price')
                ->join('packages', 'packages.id = customers.package_id', 'left')
                ->groupStart()
                    ->like('customers.name', $search)
                    ->orLike('customers.phone', $search)
                    ->orWhere('customers.pppoe_username', $search)
                ->groupEnd()
                ->get()->getRowArray();
            
            if ($customer) {
                $msg = "👤 *DATA CUSTOMER*\n\n";
                $msg .= "Nama: *{$customer['name']}*\n";
                $msg .= "Phone: `{$customer['phone']}`\n";
                $msg .= "PPPoE: `{$customer['pppoe_username']}`\n";
                $msg .= "Paket: {$customer['package_name']}\n";
                $msg .= "Harga: Rp " . number_format($customer['package_price'], 0, ',', '.') . "\n";
                $msg .= "Status: " . ($customer['status'] === 'active' ? '✅ Aktif' : '❌ Isolir') . "\n";
                $msg .= "Tgl Isolir: {$customer['isolation_date']}\n";
                
                // Check unpaid invoices
                $unpaid = $db->table('invoices')
                    ->where('customer_id', $customer['id'])
                    ->where('paid', 0)
                    ->countAllResults();
                    
                $msg .= "\n💰 Tagihan Unpaid: *{$unpaid}*";
                
                $telegram->sendMessage($chatId, $msg);
            } else {
                $telegram->sendMessage($chatId, "❌ *CUSTOMER TIDAK DITEMUKAN*\n\nCoba search dengan:\n• Nama\n• Nomor HP\n• PPPoE Username");
            }
            return;
        }
        
        // === ISOLIR - Isolate Customer ===
        if (preg_match('/^ISOLIR\s+(.+)$/i', $text, $matches)) {
            $search = trim($matches[1]);
            $db = \Config\Database::connect();
            
            $customer = $db->table('customers')
                ->select('customers.*, packages.profile_isolir')
                ->join('packages', 'packages.id = customers.package_id', 'left')
                ->groupStart()
                    ->like('customers.name', $search)
                    ->orLike('customers.phone', $search)
                    ->orWhere('customers.pppoe_username', $search)
                ->groupEnd()
                ->get()->getRowArray();
                
            if (!$customer) {
                $telegram->sendMessage($chatId, "❌ *CUSTOMER TIDAK DITEMUKAN*");
                return;
            }
            
            if ($customer['status'] === 'isolated') {
                $telegram->sendMessage($chatId, "⚠️ *SUDAH TERISOLIR*\n\nCustomer `{$customer['name']}` sudah dalam status isolir.");
                return;
            }
            
            // Isolate in DB
            $db->table('customers')->where('id', $customer['id'])->update(['status' => 'isolated']);
            
            // Isolate in MikroTik
            if (!empty($customer['pppoe_username'])) {
                if (!empty($customer['profile_isolir'])) {
                    $mikrotik->setPppoeUserProfile($customer['pppoe_username'], $customer['profile_isolir']);
                } else {
                    $mikrotik->disablePppoeSecret($customer['pppoe_username']);
                }
            }
            
            $telegram->sendMessage($chatId, "✅ *CUSTOMER DIISOLIR*\n\nNama: {$customer['name']}\nPPPoE: `{$customer['pppoe_username']}`\n\n⛔ Layanan telah diisolir.");
            return;
        }
        
        // === UNISOLIR - Activate Customer ===
        if (preg_match('/^UNISOLIR\s+(.+)$/i', $text, $matches)) {
            $search = trim($matches[1]);
            $db = \Config\Database::connect();
            
            $customer = $db->table('customers')
                ->select('customers.*, packages.profile_normal')
                ->join('packages', 'packages.id = customers.package_id', 'left')
                ->groupStart()
                    ->like('customers.name', $search)
                    ->orLike('customers.phone', $search)
                    ->orWhere('customers.pppoe_username', $search)
                ->groupEnd()
                ->get()->getRowArray();
                
            if (!$customer) {
                $telegram->sendMessage($chatId, "❌ *CUSTOMER TIDAK DITEMUKAN*");
                return;
            }
            
            if ($customer['status'] === 'active') {
                $telegram->sendMessage($chatId, "⚠️ *SUDAH AKTIF*\n\nCustomer `{$customer['name']}` sudah dalam status aktif.");
                return;
            }
            
            // Activate in DB
            $db->table('customers')->where('id', $customer['id'])->update(['status' => 'active']);
            
            // Activate in MikroTik
            if (!empty($customer['pppoe_username']) && !empty($customer['profile_normal'])) {
                $mikrotik->setPppoeUserProfile($customer['pppoe_username'], $customer['profile_normal']);
                $mikrotik->enablePppoeSecret($customer['pppoe_username']);
            }
            
            $telegram->sendMessage($chatId, "✅ *CUSTOMER DIAKTIFKAN*\n\nNama: {$customer['name']}\nPPPoE: `{$customer['pppoe_username']}`\n\n✅ Layanan telah diaktifkan kembali.");
            return;
        }
        
        // === BAYAR - Mark Invoice as Paid ===
        if (preg_match('/^BAYAR\s+(.+)$/i', $text, $matches)) {
            $search = trim($matches[1]);
            $db = \Config\Database::connect();
            
            $customer = $db->table('customers')
                ->groupStart()
                    ->like('name', $search)
                    ->orLike('phone', $search)
                ->groupEnd()
                ->get()->getRowArray();
                
            if (!$customer) {
                $telegram->sendMessage($chatId, "❌ *CUSTOMER TIDAK DITEMUKAN*");
                return;
            }
            
            // Get unpaid invoices
            $invoices = $db->table('invoices')
                ->where('customer_id', $customer['id'])
                ->where('paid', 0)
                ->orderBy('due_date', 'ASC')
                ->get()->getResultArray();
                
            if (empty($invoices)) {
                $telegram->sendMessage($chatId, "✅ *TIDAK ADA TAGIHAN*\n\nCustomer `{$customer['name']}` tidak memiliki tagihan yang belum dibayar.");
                return;
            }
            
            // Mark all as paid
            $paidCount = 0;
            foreach ($invoices as $inv) {
                $db->table('invoices')->where('id', $inv['id'])->update([
                    'paid' => 1,
                    'status' => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'payment_method' => 'Telegram Bot'
                ]);
                $paidCount++;
            }
            
            // Unisolate customer in database
            $db->table('customers')->where('id', $customer['id'])->update([
                'status' => 'active',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Activate in MikroTik
            $mikrotikSuccess = false;
            $packageData = $db->table('packages')->where('id', $customer['package_id'])->get()->getRowArray();
            if ($packageData && !empty($customer['pppoe_username'])) {
                $mikrotik->setPppoeUserProfile($customer['pppoe_username'], $packageData['profile_normal']);
                $mikrotik->enablePppoeSecret($customer['pppoe_username']);
                $mikrotikSuccess = true;
            }
            
            $totalPaid = array_sum(array_column($invoices, 'amount'));
            
            $msg = "✅ *PEMBAYARAN BERHASIL*\n\n";
            $msg .= "👤 Customer: {$customer['name']}\n";
            $msg .= "📞 Phone: {$customer['phone']}\n";
            $msg .= "📄 Jumlah Invoice: {$paidCount}\n";
            $msg .= "💰 Total: Rp " . number_format($totalPaid, 0, ',', '.') . "\n\n";
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "✅ Database: Updated\n";
            $msg .= "✅ Status: Active\n";
            if ($mikrotikSuccess) {
                $msg .= "✅ MikroTik: Activated\n";
            }
            $msg .= "\n🎉 Layanan telah diaktifkan!";
            
            $telegram->sendMessage($chatId, $msg);
            return;
        }
        
        // === TAGIHAN - List Unpaid Invoices ===
        if (preg_match('/^TAGIHAN$/i', $text)) {
            $db = \Config\Database::connect();
            
            $invoices = $db->table('invoices')
                ->select('invoices.*, customers.name as customer_name, customers.phone')
                ->join('customers', 'customers.id = invoices.customer_id')
                ->where('invoices.paid', 0)
                ->where('invoices.due_date <', date('Y-m-d'))
                ->orderBy('invoices.due_date', 'ASC')
                ->limit(10)
                ->get()->getResultArray();
                
            if (empty($invoices)) {
                $telegram->sendMessage($chatId, "✅ *TIDAK ADA TAGIHAN OVERDUE*\n\nSemua invoice sudah dibayar!");
                return;
            }
            
            $msg = "💰 *TAGIHAN OVERDUE*\n\n";
            $msg .= "Total: " . count($invoices) . " invoice\n\n";
            
            foreach ($invoices as $inv) {
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "📄 {$inv['invoice_number']}\n";
                $msg .= "👤 {$inv['customer_name']}\n";
                $msg .= "📞 {$inv['phone']}\n";
                $msg .= "💵 Rp " . number_format($inv['amount'], 0, ',', '.') . "\n";
                $msg .= "📅 Jatuh Tempo: {$inv['due_date']}\n";
            }
            
            $msg .= "\n_Showing top 10 overdue invoices_";
            
            $telegram->sendMessage($chatId, $msg);
            return;
        }
        
        // === INVOICE - Generate Monthly Invoices ===
        if (preg_match('/^INVOICE$/i', $text)) {
            $db = \Config\Database::connect();
            
            // Get active customers without invoice this month
            $currentMonth = date('Y-m');
            $customers = $db->table('customers')
                ->select('customers.*, packages.name as package_name, packages.price')
                ->join('packages', 'packages.id = customers.package_id')
                ->where('customers.status', 'active')
                ->get()->getResultArray();
                
            $count = 0;
            foreach ($customers as $c) {
                $exists = $db->table('invoices')
                    ->where('customer_id', $c['id'])
                    ->where("created_at LIKE '{$currentMonth}%'")
                    ->countAllResults();
                    
                if ($exists == 0) {
                    $isoDay = $c['isolation_date'] ?? 20;
                    $dueDate = date('Y-m-') . str_pad($isoDay, 2, '0', STR_PAD_LEFT);
                    $invNumber = 'INV-' . date('Ym') . '-' . $c['id'];
                    
                    $db->table('invoices')->insert([
                        'customer_id' => $c['id'],
                        'invoice_number' => $invNumber,
                        'amount' => $c['price'],
                        'description' => 'Tagihan Bulan ' . date('F Y'),
                        'due_date' => $dueDate,
                        'paid' => 0,
                        'status' => 'pending'
                    ]);
                    $count++;
                }
            }
            
            $telegram->sendMessage($chatId, "✅ *INVOICE GENERATED*\n\n📄 {$count} invoice baru dibuat untuk bulan ini.");
            return;
        }
        
        // === MEMBER - Generate Permanent Hotspot User (Custom Username & Password) ===
        if (preg_match('/^MEMBER\s+(\S+)\s+(\S+)\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            $password = $matches[2];
            $profile = $matches[3];
            
            // Add permanent user (no limit-uptime = empty string)
            $result = $mikrotik->addHotspotUser($username, $password, $profile, '');
            
            if ($result) {
                $msg = "👤 *MEMBER CREATED*\n\n";
                $msg .= "👤 Username: `{$username}`\n";
                $msg .= "🔑 Password: `{$password}`\n";
                $msg .= "📦 Profile: `{$profile}`\n";
                $msg .= "♾️ Type: Permanent User\n\n";
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "✅ Member siap digunakan!";
                
                $telegram->sendMessage($chatId, $msg);
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL CREATE MEMBER*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === VCR - Voucher Custom (Manual Username, Auto Password) ===
        if (preg_match('/^VCR\s+(\S+)\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            $profile = $matches[2];
            
            // Auto-generate password: gabungan username + profile (unique & memorable)
            $password = $username . $profile;
            
            // Add voucher with limit-uptime
            $result = $mikrotik->addHotspotUser($username, $password, $profile, '24h');
            
            if ($result) {
                $msg = "🎫 *VOUCHER CREATED (CUSTOM)*\n\n";
                $msg .= "👤 Username: `{$username}`\n";
                $msg .= "🔑 Password: `{$password}`\n";
                $msg .= "📦 Profile: `{$profile}`\n";
                $msg .= "⏱️ Limit: 24 jam\n\n";
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "✅ Voucher siap digunakan!";
                
                $telegram->sendMessage($chatId, $msg);
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL GENERATE*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === VOUCHER - Generate Hotspot Voucher ===
        if (preg_match('/^VOUCHER\s+(\S+)$/i', $text, $matches)) {
            $profile = $matches[1];
            
            // Fetch profile info from MikroTik
            $profileInfo = $mikrotik->getHotspotProfileInfo($profile);
            
            // Auto-generate username: 5 digit angka (10000-99999)
            $username = (string)rand(10000, 99999);
            $password = $username; // Same as username
            $comment = 'vc-gembok-tele';
            
            $result = $mikrotik->addHotspotUser($username, $password, $profile, '24h', $comment);
            
            if ($result) {
                $msg = "🎫 *VOUCHER CREATED*\n\n";
                $msg .= "👤 Username: `{$username}`\n";
                $msg .= "🔑 Password: `{$password}`\n";
                $msg .= "📦 Profile: `{$profile}`\n";
                
                // Show price and duration from profile if available
                if ($profileInfo) {
                    if (!empty($profileInfo['price'])) {
                        $msg .= "💰 Harga: Rp " . number_format($profileInfo['price'], 0, ',', '.') . "\n";
                    }
                    if (!empty($profileInfo['duration'])) {
                        $msg .= "⏱️ Durasi: {$profileInfo['duration']}\n";
                    }
                }
                
                $msg .= "\n━━━━━━━━━━━━━━\n";
                $msg .= "✅ Voucher siap digunakan!";
                
                $telegram->sendMessage($chatId, $msg);
            } else {
                $telegram->sendMessage($chatId, "❌ *GAGAL GENERATE*\n\n" . $mikrotik->getLastError());
            }
            return;
        }
        
        // === INFO - System Information (Admin Only) ===
        if ($command === 'INFO') {
            $db = \Config\Database::connect();
            
            try {
                // Get statistics
                $totalCustomers = $db->table('customers')->countAllResults();
                $activeCustomers = $db->table('customers')->where('status', 'active')->countAllResults();
                $isolatedCustomers = $db->table('customers')->where('status', 'isolated')->countAllResults();
                
                $unpaidInvoices = $db->table('invoices')->where('paid', 0)->countAllResults();
                $paidThisMonth = $db->table('invoices')
                    ->where('paid', 1)
                    ->where("DATE_FORMAT(paid_at, '%Y-%m') =", date('Y-m'))
                    ->countAllResults();
                
                $revenueThisMonth = $db->table('invoices')
                    ->selectSum('amount')
                    ->where('paid', 1)
                    ->where("DATE_FORMAT(paid_at, '%Y-%m') =", date('Y-m'))
                    ->get()->getRowArray()['amount'] ?? 0;
                
                // MikroTik stats
                $activePppoe = 0;
                $activeHotspot = 0;
                if ($mikrotik->isConnected()) {
                    $activePppoe = count($mikrotik->getActivePppoe());
                    $activeHotspot = count($mikrotik->getActiveHotspotUsers());
                }
                
                $msg = "📊 *INFO SISTEM*\n\n";
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "*👥 CUSTOMER:*\n";
                $msg .= "• Total: *{$totalCustomers}*\n";
                $msg .= "• Aktif: *{$activeCustomers}* ✅\n";
                $msg .= "• Isolir: *{$isolatedCustomers}* ⛔\n\n";
                
                $msg .= "*💰 BILLING:*\n";
                $msg .= "• Unpaid: *{$unpaidInvoices}* invoice\n";
                $msg .= "• Paid (bulan ini): *{$paidThisMonth}*\n";
                $msg .= "• Revenue: *Rp " . number_format($revenueThisMonth, 0, ',', '.') . "*\n\n";
                
                $msg .= "*⚙️ MIKROTIK:*\n";
                $msg .= "• PPPoE: *{$activePppoe}* online\n";
                $msg .= "• Hotspot: *{$activeHotspot}* online\n\n";
                
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "📅 " . date('d M Y H:i');
                
                $telegram->sendMessage($chatId, $msg);
            } catch (\Exception $e) {
                $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
            }
            return;
        }
        
        // === KICK - Kick Active User (Admin Only) ===
        if (preg_match('/^KICK\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            
            try {
                $mikrotik->kickPppoeUser($username);
                $telegram->sendMessage($chatId, "✅ *USER DIKICK*\n\nUsername: `{$username}`\n\n⚡ Koneksi PPPoE telah diputus.");
            } catch (\Exception $e) {
                $telegram->sendMessage($chatId, "❌ *GAGAL KICK*\n\n" . $e->getMessage());
            }
            return;
        }
        
        // === CARI - Search PPPoE User in MikroTik (Admin Only) ===
        if (preg_match('/^CARI\s+(\S+)$/i', $text, $matches)) {
            $username = $matches[1];
            
            try {
                $users = $mikrotik->query('/ppp/secret/print', ['?name' => $username]);
                
                if (empty($users)) {
                    $telegram->sendMessage($chatId, "❌ *USER TIDAK DITEMUKAN*\n\nUsername: `{$username}` tidak ada di MikroTik.");
                    return;
                }
                
                $user = $users[0];
                $msg = "🔍 *DATA PPPOE USER*\n\n";
                $msg .= "👤 Username: `{$user['name']}`\n";
                $msg .= "🔑 Password: `{$user['password']}`\n";
                $msg .= "📦 Profile: `{$user['profile']}`\n";
                $msg .= "🌐 Service: `{$user['service']}`\n";
                $msg .= "📡 Status: " . ($user['disabled'] === 'true' ? '❌ Disabled' : '✅ Enabled') . "\n";
                
                // Check if online
                $active = $mikrotik->query('/ppp/active/print', ['?name' => $username]);
                if (!empty($active)) {
                    $msg .= "\n*🟢 ONLINE*\n";
                    $msg .= "IP: `{$active[0]['address']}`\n";
                    $msg .= "Uptime: `{$active[0]['uptime']}`\n";
                } else {
                    $msg .= "\n*⚪ OFFLINE*";
                }
                
                $telegram->sendMessage($chatId, $msg);
            } catch (\Exception $e) {
                $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
            }
            return;
        }
        
        // === AKTIF - List Active Customers (Admin Only) ===
        if ($command === 'AKTIF') {
            $db = \Config\Database::connect();
            
            $customers = $db->table('customers')
                ->select('customers.name, customers.phone, customers.pppoe_username, packages.name as package_name')
                ->join('packages', 'packages.id = customers.package_id', 'left')
                ->where('customers.status', 'active')
                ->orderBy('customers.name', 'ASC')
                ->limit(15)
                ->get()->getResultArray();
            
            if (empty($customers)) {
                $telegram->sendMessage($chatId, "ℹ️ *TIDAK ADA CUSTOMER AKTIF*");
                return;
            }
            
            $msg = "✅ *CUSTOMER AKTIF*\n\n";
            $msg .= "Total: " . count($customers) . " customer\n\n";
            
            foreach ($customers as $c) {
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "👤 {$c['name']}\n";
                $msg .= "📞 {$c['phone']}\n";
                $msg .= "🌐 `{$c['pppoe_username']}`\n";
                $msg .= "📦 {$c['package_name']}\n";
            }
            
            $msg .= "\n_Showing top 15 active customers_";
            $telegram->sendMessage($chatId, $msg);
            return;
        }
        
        // === NONAKTIF - List Isolated Customers (Admin Only) ===
        if ($command === 'NONAKTIF') {
            $db = \Config\Database::connect();
            
            $customers = $db->table('customers')
                ->select('customers.name, customers.phone, customers.pppoe_username, packages.name as package_name')
                ->join('packages', 'packages.id = customers.package_id', 'left')
                ->where('customers.status', 'isolated')
                ->orderBy('customers.name', 'ASC')
                ->limit(15)
                ->get()->getResultArray();
            
            if (empty($customers)) {
                $telegram->sendMessage($chatId, "✅ *TIDAK ADA CUSTOMER ISOLIR*\n\nSemua customer aktif!");
                return;
            }
            
            $msg = "⛔ *CUSTOMER ISOLIR*\n\n";
            $msg .= "Total: " . count($customers) . " customer\n\n";
            
            foreach ($customers as $c) {
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "👤 {$c['name']}\n";
                $msg .= "📞 {$c['phone']}\n";
                $msg .= "🌐 `{$c['pppoe_username']}`\n";
                $msg .= "📦 {$c['package_name']}\n";
            }
            
            $msg .= "\n_Showing top 15 isolated customers_";
            $telegram->sendMessage($chatId, $msg);
            return;
        }
        
        // === LAPORAN - Daily Report (Admin Only) ===
        if ($command === 'LAPORAN') {
            $db = \Config\Database::connect();
            
            try {
                $today = date('Y-m-d');
                
                // Payments today
                $paymentsToday = $db->table('invoices')
                    ->where('paid', 1)
                    ->where("DATE(paid_at) =", $today)
                    ->countAllResults();
                
                $revenueToday = $db->table('invoices')
                    ->selectSum('amount')
                    ->where('paid', 1)
                    ->where("DATE(paid_at) =", $today)
                    ->get()->getRowArray()['amount'] ?? 0;
                
                // New customers today
                $newCustomers = $db->table('customers')
                    ->where("DATE(created_at) =", $today)
                    ->countAllResults();
                
                // Isolated today
                $isolatedToday = $db->table('customers')
                    ->where('status', 'isolated')
                    ->where("DATE(updated_at) =", $today)
                    ->countAllResults();
                
                // Overdue invoices
                $overdueCount = $db->table('invoices')
                    ->where('paid', 0)
                    ->where('due_date <', $today)
                    ->countAllResults();
                
                $msg = "📊 *LAPORAN HARIAN*\n\n";
                $msg .= "📅 " . date('d F Y') . "\n\n";
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "*💰 PEMBAYARAN HARI INI:*\n";
                $msg .= "• Jumlah: *{$paymentsToday}* invoice\n";
                $msg .= "• Total: *Rp " . number_format($revenueToday, 0, ',', '.') . "*\n\n";
                
                $msg .= "*👥 CUSTOMER:*\n";
                $msg .= "• Baru: *{$newCustomers}* customer\n";
                $msg .= "• Isolir: *{$isolatedToday}* customer\n\n";
                
                $msg .= "*⚠️ PERHATIAN:*\n";
                $msg .= "• Tagihan Overdue: *{$overdueCount}*\n\n";
                
                $msg .= "━━━━━━━━━━━━━━\n";
                $msg .= "🕐 " . date('H:i');
                
                $telegram->sendMessage($chatId, $msg);
            } catch (\Exception $e) {
                $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
            }
            return;
        }
        
        // === Default - Unknown Command ===
        // $telegram->sendMessage($chatId, "❓ *Perintah tidak dikenali*\n\nKetik /help untuk melihat daftar perintah.");
        // Silently ignore unknown commands
        return;
    }
    
    /**
     * Process callback query (inline button clicks)
     */
    private function processTelegramCallback($chatId, $messageId, $data, $callbackId, $telegram, $mikrotik)
    {
        // Answer callback immediately to remove loading state (FAST RESPONSE)
        $telegram->answerCallback($callbackId);
        
        list($action, $param) = explode(':', $data . ':');
        
        switch ($action) {
            case 'cmd':
                // Quick command execution
                $this->processTelegramCommand($chatId, $param, $telegram, $mikrotik);
                break;
                
            case 'menu':
                $this->showTelegramMenu($chatId, $messageId, $param, $telegram, $mikrotik);
                break;
                
            case 'voucher':
                $this->showVoucherProfiles($chatId, $messageId, $telegram, $mikrotik);
                break;
                
            case 'gen_voucher':
                $this->generateVoucherInteractive($chatId, $param, $telegram, $mikrotik);
                break;
                
            case 'mik':
                $this->handleMikrotikAction($chatId, $messageId, $param, $telegram, $mikrotik);
                break;
                
            case 'back':
                // Go back to main menu
                $this->showMainMenu($chatId, $messageId, $telegram);
                break;
        }
    }
    
    /**
     * Handle MikroTik interactive menu actions
     */
    private function handleMikrotikAction($chatId, $messageId, $action, $telegram, $mikrotik)
    {
        try {
            switch ($action) {
                case 'ping':
                    if ($mikrotik->isConnected()) {
                        $msg = "✅ *MIKROTIK ONLINE*\n\nKoneksi berhasil!";
                    } else {
                        $msg = "❌ *MIKROTIK OFFLINE*\n\nError: " . $mikrotik->getLastError();
                    }
                    break;
                    
                case 'status':
                    if (!$mikrotik->isConnected()) {
                        $msg = "❌ *MIKROTIK OFFLINE*";
                    } else {
                        $pppoe = count($mikrotik->getActivePppoe());
                        $hotspot = count($mikrotik->getActiveHotspotUsers());
                        
                        $msg = "📊 *STATUS MIKROTIK*\n\n";
                        $msg .= "🌐 PPPoE Aktif: *{$pppoe}*\n";
                        $msg .= "📡 Hotspot Aktif: *{$hotspot}*\n";
                        $msg .= "✅ Status: *ONLINE*";
                    }
                    break;
                    
                case 'pppoe':
                    if (!$mikrotik->isConnected()) {
                        $msg = "❌ *MIKROTIK OFFLINE*";
                    } else {
                        $users = $mikrotik->getActivePppoe();
                        $count = count($users);
                        $msg = "🌐 *PPPoE ACTIVE ({$count})*\n\n";
                        
                        // Show top 20
                        foreach (array_slice($users, 0, 20) as $u) {
                            $name = $u['name'] ?? '?';
                            $ip = $u['address'] ?? '?';
                            $msg .= "• `{$name}` ({$ip})\n";
                        }
                        
                        if ($count > 20) $msg .= "\n_...and " . ($count - 20) . " more_";
                    }
                    break;
                    
                case 'hotspot':
                    if (!$mikrotik->isConnected()) {
                        $msg = "❌ *MIKROTIK OFFLINE*";
                    } else {
                        $users = $mikrotik->getActiveHotspotUsers();
                        $count = count($users);
                        $msg = "📡 *HOTSPOT ACTIVE ({$count})*\n\n";
                        
                        // Show top 20
                        foreach (array_slice($users, 0, 20) as $u) {
                            $name = $u['user'] ?? '?';
                            $ip = $u['address'] ?? '?';
                            $msg .= "• `{$name}` ({$ip})\n";
                        }
                        
                        if ($count > 20) $msg .= "\n_...and " . ($count - 20) . " more_";
                    }
                    break;
                    
                default:
                    $msg = "❓ Unknown action: {$action}";
            }
        } catch (\Exception $e) {
            $msg = "❌ *ERROR MIKROTIK*\n\n" . $e->getMessage();
        }
        
        $keyboard = $telegram->inlineKeyboard([
             [$telegram->inlineButton('◀️ Kembali', 'menu:mikrotik')]
        ]);
        
        $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
    }
    
    /**
     * Show different menu based on selection
     */
    private function showTelegramMenu($chatId, $messageId, $menu, $telegram, $mikrotik)
    {
        switch ($menu) {
            case 'voucher':
                $this->showVoucherProfiles($chatId, $messageId, $telegram, $mikrotik);
                break;
                
            case 'harga':
                $this->showPriceList($chatId, $messageId, $telegram, $mikrotik);
                break;
                
            case 'mikrotik':
                $this->showMikrotikMenu($chatId, $messageId, $telegram);
                break;
                
            case 'billing':
                $this->showBillingMenu($chatId, $messageId, $telegram);
                break;
                
            case 'help':
                $this->sendTelegramHelp($chatId, $telegram, true);
                break;
        }
    }
    
    /**
     * Show main menu
     */
    private function showMainMenu($chatId, $messageId, $telegram)
    {
        $keyboard = $telegram->inlineKeyboard([
            [
                $telegram->inlineButton('🎫 Generate Voucher', 'menu:voucher'),
                $telegram->inlineButton('📋 Harga Paket', 'menu:harga')
            ],
            [
                $telegram->inlineButton('⚙️ MikroTik Menu', 'menu:mikrotik'),
                $telegram->inlineButton('❓ Help', 'menu:help')
            ]
        ]);
        
        $msg = "🤖 *GEMBOK BOT*\n\n";
        $msg .= "Pilih menu yang tersedia:";
        
        $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
    }
    
    /**
     * Show voucher profiles for selection
     */
    private function showVoucherProfiles($chatId, $messageId, $telegram, $mikrotik)
    {
        try {
            $profiles = $mikrotik->getHotspotProfiles();
            
            $buttons = [];
            foreach (array_slice($profiles, 0, 8) as $profile) {
                $name = $profile['name'] ?? 'unknown';
                $buttons[] = [$telegram->inlineButton("🎫 {$name}", "gen_voucher:{$name}")];
            }
            
            $buttons[] = [$telegram->inlineButton('◀️ Kembali', 'back:main')];
            
            $keyboard = $telegram->inlineKeyboard($buttons);
            
            $msg = "🎫 *PILIH PROFIL VOUCHER*\n\n";
            $msg .= "Klik profile untuk generate voucher:";
            
            $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
        } catch (\Exception $e) {
            $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate voucher from callback
     */
    private function generateVoucherInteractive($chatId, $profile, $telegram, $mikrotik)
    {
        // Fetch profile info from MikroTik
        $profileInfo = $mikrotik->getHotspotProfileInfo($profile);
        
        // Generate 5-digit random number (10000-99999)
        $username = (string)rand(10000, 99999);
        $password = $username;
        $comment = 'vc-gembok-tele';
        
        $result = $mikrotik->addHotspotUser($username, $password, $profile, '24h', $comment);
        
        if ($result) {
            $msg = "🎫 *VOUCHER CREATED*\n\n";
            $msg .= "👤 Username: `{$username}`\n";
            $msg .= "🔑 Password: `{$password}`\n";
            $msg .= "📦 Profile: `{$profile}`\n";
            
            // Show price and duration from profile if available
            if ($profileInfo) {
                if (!empty($profileInfo['price'])) {
                    $msg .= "💰 Harga: Rp " . number_format($profileInfo['price'], 0, ',', '.') . "\n";
                }
                if (!empty($profileInfo['duration'])) {
                    $msg .= "⏱️ Durasi: {$profileInfo['duration']}\n";
                }
            }
            
            $msg .= "\n✅ Voucher siap digunakan!";
            
            $keyboard = $telegram->inlineKeyboard([
                [$telegram->inlineButton('🎫 Generate Lagi', "menu:voucher")],
                [$telegram->inlineButton('🏠 Main Menu', 'back:main')]
            ]);
            
            $telegram->sendMessage($chatId, $msg, 'Markdown', $keyboard);
        } else {
            $telegram->sendMessage($chatId, "❌ *GAGAL*\n\n" . $mikrotik->getLastError());
        }
    }
    
    /**
     * Show price list
     */
    private function showPriceList($chatId, $messageId, $telegram, $mikrotik)
    {
        try {
            $db = \Config\Database::connect();
            $packages = $db->table('packages')->get()->getResultArray();
            
            $msg = "📋 *DAFTAR HARGA PAKET*\n\n";
            foreach ($packages as $pkg) {
                $msg .= "📦 *{$pkg['name']}*\n";
                $msg .= "💰 Rp " . number_format($pkg['price'], 0, ',', '.') . "\n";
                if ($pkg['description']) {
                    $msg .= "📝 {$pkg['description']}\n";
                }
                $msg .= "\n";
            }
            
            $keyboard = $telegram->inlineKeyboard([
                [$telegram->inlineButton('◀️ Kembali', 'back:main')]
            ]);
            
            $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
        } catch (\Exception $e) {
            $telegram->sendMessage($chatId, "❌ Error: " . $e->getMessage());
        }
    }
    
    /**
     * Show MikroTik menu
     */
    private function showMikrotikMenu($chatId, $messageId, $telegram)
    {
        $keyboard = $telegram->inlineKeyboard([
            [
                $telegram->inlineButton('🔌 PING', 'mik:ping'),
                $telegram->inlineButton('📊 STATUS', 'mik:status')
            ],
            [
                $telegram->inlineButton('🌐 PPPoE Active', 'mik:pppoe'),
                $telegram->inlineButton('📡 Hotspot Active', 'mik:hotspot')
            ],
            [
                $telegram->inlineButton('◀️ Kembali', 'back:main')
            ]
        ]);
        
        $msg = "⚙️ *MIKROTIK MENU*\n\n";
        $msg .= "Pilih menu monitoring:";
        
        $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
    }
    
    /**
     * Show Billing menu
     */
    private function showBillingMenu($chatId, $messageId, $telegram)
    {
        $keyboard = $telegram->inlineKeyboard([
            [
                $telegram->inlineButton('📋 TAGIHAN', 'cmd:TAGIHAN'),
                $telegram->inlineButton('📄 INVOICE', 'cmd:INVOICE')
            ],
            [
                $telegram->inlineButton('✅ AKTIF', 'cmd:AKTIF'),
                $telegram->inlineButton('⛔ NONAKTIF', 'cmd:NONAKTIF')
            ],
            [
                $telegram->inlineButton('◀️ Kembali', 'back:main')
            ]
        ]);
        
        $msg = "💰 *BILLING MENU*\n\n";
        $msg .= "Quick Actions:\n";
        $msg .= "• TAGIHAN - List overdue\n";
        $msg .= "• INVOICE - Generate monthly\n";
        $msg .= "• AKTIF - List active customers\n";
        $msg .= "• NONAKTIF - List isolated\n\n";
        $msg .= "Atau ketik perintah:\n";
        $msg .= "• CUSTOMER [nama]\n";
        $msg .= "• ISOLIR [nama]\n";
        $msg .= "• BAYAR [nama]";
        
        $telegram->editMessage($chatId, $messageId, $msg, 'Markdown', $keyboard);
    }
    
    /**
     * Send help message
     */
    private function sendTelegramHelp($chatId, $telegram, $isAdmin)
    {
        $msg = "❓ *BANTUAN GEMBOK BOT*\n\n";
        $msg .= "━━━━━━━━━━━━━━━━\n\n";
        $msg .= "*📋 PERINTAH UMUM:*\n\n";
        $msg .= "🔌 `PING` - Test koneksi MikroTik\n";
        $msg .= "📊 `STATUS` - Cek status MikroTik\n";
        $msg .= "🎫 `VOUCHER [profile]` - Generate voucher\n";
        $msg .= "   Contoh: `VOUCHER 3k`\n\n";
        
        if ($isAdmin) {
            $msg .= "*📊 PERINTAH INFO & LAPORAN:*\n\n";
            $msg .= "📈 `INFO` - Info sistem lengkap\n";
            $msg .= "   (Customer, Revenue, MikroTik)\n\n";
            
            $msg .= "📋 `LAPORAN` - Laporan harian\n";
            $msg .= "   (Pembayaran, Customer baru)\n\n";
            
            $msg .= "*⚙️ PERINTAH ADMIN MIKROTIK:*\n\n";
            $msg .= "➕ `TAMBAH [user] [pass] [profile]`\n";
            $msg .= "   Tambah PPPoE user baru\n";
            $msg .= "   Contoh: `TAMBAH user01 pass123 20Mbps`\n\n";
            
            $msg .= "✏️ `EDIT [user] [profile]`\n";
            $msg .= "   Update profile PPPoE user\n";
            $msg .= "   Contoh: `EDIT user01 50Mbps`\n\n";
            
            $msg .= "🗑️ `HAPUS [user]`\n";
            $msg .= "   Hapus PPPoE user\n";
            $msg .= "   Contoh: `HAPUS user01`\n\n";
            
            $msg .= "🔍 `CARI [user]`\n";
            $msg .= "   Cari detail PPPoE user\n";
            $msg .= "   Contoh: `CARI user01`\n\n";
            
            $msg .= "⚡ `KICK [user]`\n";
            $msg .= "   Kick user online\n";
            $msg .= "   Contoh: `KICK user01`\n\n";
            
            $msg .= "*💰 PERINTAH BILLING:*\n\n";
            $msg .= "👤 `CUSTOMER [nama/hp/username]`\n";
            $msg .= "   Cari data customer\n";
            $msg .= "   Contoh: `CUSTOMER Budi`\n\n";
            
            $msg .= "⛔ `ISOLIR [nama/hp]`\n";
            $msg .= "   Isolir customer (non-aktif)\n";
            $msg .= "   Contoh: `ISOLIR Budi`\n\n";
            
            $msg .= "✅ `UNISOLIR [nama/hp]`\n";
            $msg .= "   Aktifkan customer kembali\n";
            $msg .= "   Contoh: `UNISOLIR Budi`\n\n";
            
            $msg .= "💵 `BAYAR [nama/hp]`\n";
            $msg .= "   Tandai invoice lunas & aktifkan\n";
            $msg .= "   Contoh: `BAYAR Budi`\n\n";
            
            $msg .= "📋 `TAGIHAN`\n";
            $msg .= "   List tagihan overdue (10 teratas)\n\n";
            
            $msg .= "📄 `INVOICE`\n";
            $msg .= "   Generate invoice bulanan\n\n";
            
            $msg .= "✅ `AKTIF`\n";
            $msg .= "   List customer aktif (15 teratas)\n\n";
            
            $msg .= "⛔ `NONAKTIF`\n";
            $msg .= "   List customer isolir (15 teratas)\n\n";
            
            $msg .= "*🎫 PERINTAH VOUCHER:*\n\n";
            $msg .= "🎟️ `VCR [username] [profile]`\n";
            $msg .= "   Voucher custom (24 jam)\n";
            $msg .= "   Contoh: `VCR wifi123 3k`\n\n";
            
            $msg .= "👥 `MEMBER [user] [pass] [profile]`\n";
            $msg .= "   Member permanent (unlimited)\n";
            $msg .= "   Contoh: `MEMBER cafe01 pass123 3k`\n\n";
        }
        
        $msg .= "━━━━━━━━━━━━━━━━\n";
        $msg .= "💡 Gunakan menu interaktif dengan /start";
        
        $telegram->sendMessage($chatId, $msg);
    }
    
    /**
     * Check if chat ID is admin
     */
    private function isTelegramAdmin($chatId)
    {
        // Try to get admin chat IDs from the settings table first (via ConfigService)
        // Fallback to environment variable if not set in DB.
        $config = new \App\Services\ConfigService();
        $adminIdsString = $config->get('TELEGRAM_ADMIN_CHAT_IDS');
        if ($adminIdsString === null || $adminIdsString === '') {
            $adminIdsString = getenv('TELEGRAM_ADMIN_CHAT_IDS') ?: '';
        }
        $adminChatIds = explode(',', $adminIdsString);
        return in_array($chatId, array_map('trim', $adminChatIds));
    }
    
    // Placeholder for WhatsApp webhook
    public function whatsapp()
    {
        // Handle incoming WhatsApp message hooks here (Fonnte/WA Gateway)
        return $this->response->setJSON(['status' => 'ok']);
    }
    
    // Placeholder for Midtrans webhook
    public function midtrans()
    {
        // Handle Midtrans callback logic
        $this->logToDb('midtrans', file_get_contents('php://input'), 200, 'Received');
        return $this->response->setJSON(['status' => 'ok']);
    }

    private function logToDb($source, $payload, $code, $msg)
    {
        try {
            $db = \Config\Database::connect();
            $db->table('webhook_logs')->insert([
                'source' => $source,
                'payload' => $payload,
                'response_code' => $code,
                'response_message' => $msg,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log webhook to DB: ' . $e->getMessage());
        }
    }
}

