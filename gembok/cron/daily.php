<?php
/**
 * daily.php – Cron job harian untuk aplikasi Gembok (PHP/CodeIgniter).
 *
 * Letakkan file ini di folder `cron/` pada server hosting Anda dan
 * tambahkan entry cron seperti:
 *   0 2 * * * /usr/bin/php /path/to/your/project/cron/daily.php >/dev/null 2>&1
 *
 * Tugas yang dijalankan:
 *   1. Auto-isolir customer yang belum bayar (ganti profile MikroTik).
 *   2. Un-isolir customer yang sudah lunas (restore profile normal).
 *   3. Kirim reminder pembayaran via WhatsApp (jika provider di-set).
 *   4. Log aktivitas ke `writable/logs/cron.log`.
 *   5. (Opsional) backup database – aktifkan dengan men-set env `ENABLE_DB_BACKUP`.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\MikrotikService;
use App\Services\GenieacsService;
use App\Services\WhatsappGatewayService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// ---------------------------------------------------------------------
// Logger setup (cron.log)
// ---------------------------------------------------------------------
$log = new Logger('cron');
$logFile = __DIR__ . '/../writable/logs/cron.log';
$log->pushHandler(new StreamHandler($logFile, Logger::INFO));
$log->info('--- Daily cron started ---');

// ---------------------------------------------------------------------
// Load environment variables (Dotenv) – same .env as CI4 app
// ---------------------------------------------------------------------
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ---------------------------------------------------------------------
// 1. Auto-Isolir customers with unpaid invoices (Change MikroTik profile)
// ---------------------------------------------------------------------
try {
    $db = \Config\Database::connect();
    $mikrotik = new MikrotikService();
    
    $log->info('Starting auto-isolir process...');
    
    // Get all active customers
    $customers = $db->table('customers')
        ->where('status', 'active')
        ->get()
        ->getResultArray();
    
    foreach ($customers as $cust) {
        // Check if customer has unpaid invoices (belum bayar)
        $unpaid = $db->table('invoices')
            ->where('customer_id', $cust['id'])
            ->where('status', 'unpaid')
            ->where('due_date <', date('Y-m-d')) // Lewat jatuh tempo
            ->countAllResults();
        
        // If has unpaid invoices AND status is active -> ISOLIR
        if ($unpaid > 0 && $cust['status'] === 'active') {
            // Get package to find isolir profile
            $package = $db->table('packages')->where('id', $cust['package_id'])->get()->getRowArray();
            
            if ($package && !empty($package['profile_isolir']) && !empty($cust['pppoe_username'])) {
                // Change MikroTik profile to isolir profile
                $result = $mikrotik->setPppoeUserProfile($cust['pppoe_username'], $package['profile_isolir']);
                
                if ($result) {
                    // Update customer status to isolated
                    $db->table('customers')
                        ->where('id', $cust['id'])
                        ->update([
                            'status' => 'isolated',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    $log->info('Customer isolated', [
                        'customer_id' => $cust['id'],
                        'name' => $cust['name'],
                        'pppoe' => $cust['pppoe_username'],
                        'profile_changed_to' => $package['profile_isolir']
                    ]);
                } else {
                    $log->warning('Failed to change MikroTik profile', [
                        'customer_id' => $cust['id'],
                        'pppoe' => $cust['pppoe_username']
                    ]);
                }
            }
        }
    }
    
    $log->info('Auto-isolir process completed');
} catch (Throwable $e) {
    $log->error('Error during auto-isolir: ' . $e->getMessage());
}

// ---------------------------------------------------------------------
// 2. Un-Isolir customers whose invoices are all paid (Restore normal profile)
// ---------------------------------------------------------------------
try {
    $db = \Config\Database::connect();
    $mikrotik = new MikrotikService();
    
    $log->info('Starting un-isolir process...');
    
    // Get all isolated customers
    $customers = $db->table('customers')
        ->where('status', 'isolated')
        ->get()
        ->getResultArray();
    
    foreach ($customers as $cust) {
        // Check unpaid invoices
        $unpaid = $db->table('invoices')
            ->where('customer_id', $cust['id'])
            ->where('status', 'unpaid')
            ->where('due_date <', date('Y-m-d'))
            ->countAllResults();
        
        // If ALL invoices are paid (no overdue unpaid) -> UN-ISOLIR
        if ($unpaid === 0) {
            // Get package to find normal profile
            $package = $db->table('packages')->where('id', $cust['package_id'])->get()->getRowArray();
            
            if ($package && !empty($package['profile_normal']) && !empty($cust['pppoe_username'])) {
                // Restore normal profile
                $result = $mikrotik->setPppoeUserProfile($cust['pppoe_username'], $package['profile_normal']);
                
                if ($result) {
                    // Update customer status to active
                    $db->table('customers')
                        ->where('id', $cust['id'])
                        ->update([
                            'status' => 'active',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    $log->info('Customer un-isolated', [
                        'customer_id' => $cust['id'],
                        'name' => $cust['name'],
                        'pppoe' => $cust['pppoe_username'],
                        'profile_changed_to' => $package['profile_normal']
                    ]);
                } else {
                    $log->warning('Failed to restore MikroTik profile', [
                        'customer_id' => $cust['id'],
                        'pppoe' => $cust['pppoe_username']
                    ]);
                }
            }
        }
    }
    
    $log->info('Un-isolir process completed');
} catch (Throwable $e) {
    $log->error('Error during un-isolir: ' . $e->getMessage());
}

// ---------------------------------------------------------------------
// 3. Send payment reminder via WhatsApp for invoices due tomorrow
// ---------------------------------------------------------------------
try {
    $gateway = new WhatsappGatewayService();
    $db = \Config\Database::connect();
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $dueInvoices = $db->table('invoices')
        ->where('due_date', $tomorrow)
        ->where('status', 'unpaid')
        ->get()
        ->getResultArray();
    
    foreach ($dueInvoices as $inv) {
        $customer = $db->table('customers')->where('id', $inv['customer_id'])->get()->getRowArray();
        
        if ($customer && !empty($customer['phone'])) {
            $msg = "📢 *Pengingat Pembayaran*\n\n";
            $msg .= "Halo {$customer['name']},\n\n";
            $msg .= "Tagihan Anda:\n";
            $msg .= "• No Invoice: {$inv['invoice_number']}\n";
            $msg .= "• Jumlah: Rp " . number_format($inv['amount'], 0, ',', '.') . "\n";
            $msg .= "• Jatuh Tempo: {$inv['due_date']}\n\n";
            $msg .= "Silakan lakukan pembayaran agar layanan tetap aktif.\n";
            $msg .= "Terima kasih! 🙏";
            
            $gateway->sendMessage($customer['phone'], $msg);
            $log->info('Reminder sent', [
                'phone' => $customer['phone'], 
                'invoice' => $inv['invoice_number']
            ]);
        }
    }
    
    $log->info('WhatsApp reminders completed');
} catch (Throwable $e) {
    $log->error('Error sending reminders: ' . $e->getMessage());
}

// ---------------------------------------------------------------------
// 4. Optional DB backup – enable via .env ENABLE_DB_BACKUP=true
// ---------------------------------------------------------------------
if (getenv('ENABLE_DB_BACKUP') === 'true') {
    try {
        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $date = date('Ymd_His');
        $backupFile = "$backupDir/db_backup_$date.sql";
        $dbHost = getenv('DB_HOST');
        $dbName = getenv('DB_DATABASE');
        $dbUser = getenv('DB_USERNAME');
        $dbPass = getenv('DB_PASSWORD');
        
        // Use mysqldump – assumes it is available on the server.
        $command = "mysqldump -h $dbHost -u $dbUser -p'$dbPass' $dbName > $backupFile";
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $log->info('Database backup created', ['file' => $backupFile]);
        } else {
            $log->error('Database backup failed', ['command' => $command, 'output' => $output]);
        }
    } catch (Throwable $e) {
        $log->error('Error during DB backup: ' . $e->getMessage());
    }
}

$log->info('--- Daily cron finished ---');
?>
