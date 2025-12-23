<?php
/**
 * Cron Job Scheduler for Gembok App
 * 
 * Cara Penggunaan di cPanel / Hosting:
 * 1. Upload file ini ke root folder aplikasi (sejajar dengan spark, app, public)
 * 2. Di menu Cron Jobs cPanel, tambahkan entry baru:
 *    
 *    Opsi 1 (PHP CLI - Recommended):
 *    /usr/local/bin/php /path/to/your/site/cron_scheduler.php
 * 
 *    Opsi 2 (Wget/CURL - jika tidak akses CLI):
 *    wget -q -O - http://yourdomain.com/admin/billing/cron/isolir
 * 
 * frekuensi: Set setiap hari sekali (misal jam 01:00 pagi)
 * 0 1 * * *
 */

// Define base path
$basePath = __DIR__;

// Helper function to log
function writeLog($msg) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    $logDir = __DIR__ . '/writable/logs';
    if (!is_dir($logDir)) { mkdir($logDir, 0777, true); }
    file_put_contents($logDir . '/cron_custom.log', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

writeLog("Starting Custom Cron Job...");

// Load Composer Autoload (for Dotenv)
if (file_exists(__DIR__ . '/gembok/vendor/autoload.php')) {
    require __DIR__ . '/gembok/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// Load .env
$envPath = __DIR__ . '/gembok'; // Try inside gembok folder first
if (!file_exists($envPath . '/.env')) {
    $envPath = __DIR__; // Fallback to root
}

if (file_exists($envPath . '/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->load();
    } catch (Exception $e) {
        // Ignore
    }
}

writeLog("Starting Custom Cron Job...");

// Konfigurasi Dinamis
// Cek APP_BASE_URL dari .env, atau tebak sendiri
$domain = $_ENV['app.baseURL'] ?? getenv('app.baseURL');
if (empty($domain)) {
    // Fallback default
    $domain = "http://localhost/gembok-production"; 
}
// Remove trailing slash
$domain = rtrim($domain, '/');

// Secret Key
$secretKey = $_ENV['CRON_SECRET'] ?? getenv('CRON_SECRET') ?? "gembok_production_secret_2025";


// 1. Jalankan Cek Isolir (Setiap hari)
writeLog("Running: Check Isolation on $domain...");
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$domain/index.php/cron/run/isolir?key=$secretKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
// Add SSL ignore for localhost/self-signed
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$output = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
writeLog("Result Isolir: HTTP $httpCode - " . substr(strip_tags($output ?? ''), 0, 50));


// 2. Generate Invoice (Hanya tanggal 1 setiap bulan)
if (date('j') == 1) {
    writeLog("Running: Generate Invoices (Monthly 1st)...");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$domain/index.php/cron/run/invoice?key=$secretKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Add SSL ignore
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    writeLog("Result Invoice: HTTP $httpCode - " . substr(strip_tags($output ?? ''), 0, 50));
}

writeLog("Cron Job Finished.");
?>
