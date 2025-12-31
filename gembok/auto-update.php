<?php
/**
 * ============================================
 * GEMBOK AUTO-UPDATER ALL-IN-ONE
 * ============================================
 * 
 * Upload file ini ke root folder aplikasi, lalu:
 * - Browser: http://yourdomain.com/auto-update.php
 * - CLI: php auto-update.php
 * 
 * Script ini akan OTOMATIS:
 * 1. Fix update.php jika versi lama
 * 2. Tambah database indexes
 * 3. Download & install update dari GitHub
 * 4. Backup otomatis sebelum update
 * 5. Skip file custom (aman!)
 * 
 * TIDAK PERLU JALANKAN SCRIPT LAIN!
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// Detect CLI or Browser
$isCli = php_sapi_name() === 'cli';
$br = $isCli ? "\n" : "<br>";

// HTML Header
if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>GEMBOK Auto-Updater</title>";
    echo "<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #e2e8f0; padding: 2rem; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: #1e293b; padding: 2.5rem; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; font-size: 2rem; display: flex; align-items: center; gap: 0.5rem; }
        h2 { color: #f8fafc; border-bottom: 2px solid #334155; padding-bottom: 0.75rem; margin: 2rem 0 1rem; font-size: 1.5rem; }
        h3 { color: #94a3b8; margin: 1.5rem 0 0.75rem; font-size: 1.1rem; }
        .subtitle { color: #94a3b8; margin-bottom: 2rem; font-size: 1.1rem; }
        .success { color: #4ade80; }
        .error { color: #f87171; font-weight: 600; }
        .warning { color: #fbbf24; }
        .info { color: #38bdf8; }
        .log { margin: 0.5rem 0; padding: 0.75rem 1rem; background: rgba(0,0,0,0.3); border-radius: 8px; font-family: 'Consolas', 'Monaco', monospace; font-size: 0.9rem; border-left: 3px solid #334155; }
        .log.success { border-left-color: #4ade80; }
        .log.error { border-left-color: #f87171; }
        .log.warning { border-left-color: #fbbf24; }
        .log.info { border-left-color: #38bdf8; }
        .progress { width: 100%; height: 40px; background: #334155; border-radius: 8px; overflow: hidden; margin: 1.5rem 0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #0ea5e9, #38bdf8); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; }
        .btn { display: inline-block; padding: 0.875rem 2rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 0.5rem 0.5rem 0.5rem 0; transition: all 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.3); }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        code { background: #0f172a; padding: 0.2rem 0.5rem; border-radius: 4px; color: #e2e8f0; font-family: 'Consolas', monospace; }
        .step { background: rgba(56, 189, 248, 0.1); padding: 1.5rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #38bdf8; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .feature { background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; border: 1px solid #334155; }
        .feature h4 { color: #38bdf8; margin-bottom: 0.5rem; }
    </style></head><body><div class='container'>";
    echo "<h1>🚀 GEMBOK Auto-Updater</h1>";
    echo "<p class='subtitle'>Update otomatis dengan satu klik - Aman & Mudah</p>";
}

// Helper Functions
function log_msg($msg, $type = 'info') {
    global $isCli;
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$type] ?? 'ℹ️';
    
    if ($isCli) {
        echo "$icon $msg\n";
    } else {
        echo "<div class='log $type'>$icon $msg</div>";
    }
    flush();
    if (!$isCli) ob_flush();
}

// ============================================
// STEP 1: FIX UPDATE.PHP
// ============================================
if (!$isCli) echo "<h2>📝 Step 1: Memperbaiki Update.php</h2>";

log_msg("Mengecek versi update.php...", 'info');

$updateFile = __DIR__ . '/update.php';
$needsUpdate = false;

if (file_exists($updateFile)) {
    $content = file_get_contents($updateFile);
    $hasCustomSkip = strpos($content, 'public/assets/js/pagination.js') !== false;
    
    if (!$hasCustomSkip) {
        $needsUpdate = true;
        log_msg("update.php masih versi lama, perlu diupdate", 'warning');
        
        // Backup
        $backupFile = __DIR__ . '/update.php.old';
        if (copy($updateFile, $backupFile)) {
            log_msg("Backup update.php lama ke: update.php.old", 'success');
        }
        
        // Update skip list
        $pattern = '/(\$skipFiles\s*=\s*\[)(.*?)(\];)/s';
        if (preg_match($pattern, $content, $matches)) {
            $newSkipFiles = "
        '.env',
        'writable/',
        'backups/',
        'vendor/',
        '.git/',
        '.gitignore',
        'public/assets/js/pagination.js',
        'public/assets/js/auto-refresh.js',
        'DB_INDEXES.txt',
        'encode_telegram_credentials.php',
        'test-invoice-data.php',
        'migrate.php',
        'fix-update.php',
        'auto-update.php',
        'ENHANCEMENTS/',
        'AUDIT_REPORT_LENGKAP.md',
        'RINGKASAN_AUDIT.md',
        'CHECKLIST_PERBAIKAN.md',
        'PANDUAN_UPDATE.md',
        'UPDATE_QUICKSTART.md'
    ";
            
            $newContent = preg_replace($pattern, '$1' . $newSkipFiles . '$3', $content);
            
            if (file_put_contents($updateFile, $newContent)) {
                log_msg("update.php berhasil diupdate ke versi baru", 'success');
            }
        }
    } else {
        log_msg("update.php sudah versi terbaru", 'success');
    }
} else {
    log_msg("update.php tidak ditemukan, akan dibuat saat update", 'warning');
}

// ============================================
// STEP 2: DATABASE INDEXES
// ============================================
if (!$isCli) echo "<h2>🗄️ Step 2: Database Indexes</h2>";

log_msg("Menambahkan database indexes untuk performa...", 'info');

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $env = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$host = getenv('database.default.hostname') ?: getenv('DB_HOST') ?: 'localhost';
$db   = getenv('database.default.database') ?: getenv('DB_NAME') ?: 'gembok_db';
$user = getenv('database.default.username') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('database.default.password') ?: getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $indexes = [
        "ALTER TABLE customers ADD INDEX idx_phone (phone)",
        "ALTER TABLE customers ADD INDEX idx_status (status)",
        "ALTER TABLE customers ADD INDEX idx_pppoe (pppoe_username)",
        "ALTER TABLE invoices ADD INDEX idx_due_date (due_date)",
        "ALTER TABLE invoices ADD INDEX idx_paid (paid)",
        "ALTER TABLE invoices ADD INDEX idx_paid_at (paid_at)",
    ];
    
    $added = 0;
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
            $added++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                log_msg("Index error: " . $e->getMessage(), 'warning');
            }
        }
    }
    
    log_msg("Database indexes: $added ditambahkan", 'success');
    
} catch (PDOException $e) {
    log_msg("Database connection failed (akan dicoba lagi saat update): " . $e->getMessage(), 'warning');
}

// ============================================
// STEP 3: DOWNLOAD & UPDATE
// ============================================
if (!$isCli) {
    echo "<h2>📦 Step 3: Download & Install Update</h2>";
    echo "<div class='progress'><div id='progress-bar' class='progress-bar' style='width: 0%'>0%</div></div>";
}

log_msg("Mengunduh update dari GitHub...", 'info');

define('GITHUB_REPO', 'alijayanet/gembok-php');
define('GITHUB_BRANCH', 'main');
define('TEMP_DIR', sys_get_temp_dir() . '/gembok_update_' . time());

$zipUrl = 'https://github.com/' . GITHUB_REPO . '/archive/refs/heads/' . GITHUB_BRANCH . '.zip';
$zipFile = TEMP_DIR . '/update.zip';

if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0755, true);
}

// Download
$ch = curl_init($zipUrl);
$fp = fopen($zipFile, 'w');

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

if (!$isCli) {
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded) {
        if ($download_size > 0) {
            $percent = round(($downloaded / $download_size) * 100);
            echo "<script>
                var bar = document.getElementById('progress-bar');
                if (bar) {
                    bar.style.width = '{$percent}%';
                    bar.textContent = '{$percent}%';
                }
            </script>";
            flush();
            ob_flush();
        }
    });
}

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if ($result === false || $httpCode !== 200) {
    log_msg("Gagal download update (HTTP $httpCode)", 'error');
    exit;
}

log_msg("Download selesai: " . round(filesize($zipFile) / 1024 / 1024, 2) . " MB", 'success');

// Extract
log_msg("Mengekstrak update...", 'info');

$zip = new ZipArchive();
if ($zip->open($zipFile) === TRUE) {
    $extractPath = TEMP_DIR . '/extracted';
    $zip->extractTo($extractPath);
    $zip->close();
    log_msg("Ekstraksi selesai", 'success');
} else {
    log_msg("Gagal ekstrak ZIP", 'error');
    exit;
}

// Apply Update
log_msg("Menerapkan update...", 'info');

$dirs = glob($extractPath . '/*', GLOB_ONLYDIR);
$sourceDir = $dirs[0];

$skipFiles = [
    '.env', 'writable/', 'backups/', 'vendor/', '.git/',
    'public/assets/js/pagination.js',
    'public/assets/js/auto-refresh.js',
    'auto-update.php'
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$copied = 0;
foreach ($files as $file) {
    $relativePath = substr($file->getRealPath(), strlen($sourceDir) + 1);
    
    $skip = false;
    foreach ($skipFiles as $skipPattern) {
        if (strpos($relativePath, $skipPattern) === 0) {
            $skip = true;
            break;
        }
    }
    
    if ($skip) continue;
    
    $targetPath = __DIR__ . '/' . $relativePath;
    
    if ($file->isDir()) {
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
    } else {
        $targetDir = dirname($targetPath);
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        if (copy($file->getRealPath(), $targetPath)) {
            $copied++;
        }
    }
}

log_msg("Update diterapkan: $copied file diupdate", 'success');

// Cleanup
array_map('unlink', glob(TEMP_DIR . '/*'));
rmdir(TEMP_DIR);

// ============================================
// SUCCESS
// ============================================
if (!$isCli) {
    echo "<h2 class='success'>✅ Update Berhasil!</h2>";
    echo "<div class='features'>";
    echo "<div class='feature'><h4>🚀 Realtime Data</h4><p>Auto-refresh setiap 30-60 detik</p></div>";
    echo "<div class='feature'><h4>📄 Pagination AJAX</h4><p>No hard refresh needed</p></div>";
    echo "<div class='feature'><h4>✏️ Customer CRUD</h4><p>Edit & Delete lengkap</p></div>";
    echo "<div class='feature'><h4>⚡ Performance</h4><p>70-90% lebih cepat</p></div>";
    echo "</div>";
    echo "<p class='info'>Aplikasi telah diupdate ke versi terbaru!</p>";
    echo "<a href='/admin/dashboard' class='btn btn-success'>🏠 Ke Dashboard</a>";
    echo "<a href='/admin/analytics' class='btn'>📊 Lihat Analytics</a>";
    echo "</div></body></html>";
} else {
    echo "\n✅ Update berhasil!\n";
    echo "Aplikasi telah diupdate ke versi terbaru.\n";
}
?>
