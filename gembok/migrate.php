<?php
/**
 * GEMBOK Migration Script
 * 
 * Script ini akan:
 * 1. Copy file-file baru yang belum ada
 * 2. Update database dengan indexes baru
 * 3. Tidak menimpa file yang sudah ada
 * 
 * AMAN untuk user yang sudah install versi lama
 */

// Detect CLI or Browser
$isCli = php_sapi_name() === 'cli';
$br = $isCli ? "\n" : "<br>";

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>GEMBOK Migration</title>";
    echo "<style>
        body { font-family: system-ui; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 12px; }
        h1 { color: #38bdf8; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        .info { color: #38bdf8; }
        .log { margin: 0.5rem 0; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 4px; font-family: monospace; }
    </style></head><body><div class='container'>";
    echo "<h1>🚀 GEMBOK Migration</h1>";
}

function log_msg($msg, $type = 'info') {
    global $isCli, $br;
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$type] ?? 'ℹ️';
    
    if ($isCli) {
        echo "$icon $msg\n";
    } else {
        echo "<div class='log $type'>$icon $msg</div>";
    }
    flush();
}

// ============================================
// 1. COPY NEW FILES (Jika belum ada)
// ============================================
log_msg("Mengecek file-file baru...", 'info');

$newFiles = [
    'public/assets/js/pagination.js' => __DIR__ . '/public/assets/js/pagination.js',
    'public/assets/js/auto-refresh.js' => __DIR__ . '/public/assets/js/auto-refresh.js',
    'DB_INDEXES.txt' => __DIR__ . '/DB_INDEXES.txt',
    'encode_telegram_credentials.php' => __DIR__ . '/encode_telegram_credentials.php',
    'test-invoice-data.php' => __DIR__ . '/test-invoice-data.php',
];

$copied = 0;
$skipped = 0;

foreach ($newFiles as $relPath => $fullPath) {
    if (file_exists($fullPath)) {
        log_msg("File sudah ada: $relPath", 'warning');
        $skipped++;
    } else {
        // File belum ada, tapi kita tidak bisa copy karena source tidak ada
        // Ini akan di-handle oleh update.php
        log_msg("File baru akan ditambahkan saat update: $relPath", 'info');
    }
}

// ============================================
// 2. UPDATE DATABASE INDEXES
// ============================================
log_msg("Mengupdate database indexes...", 'info');

// Load .env untuk database config
if (file_exists(__DIR__ . '/.env')) {
    $env = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
            }
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
    
    log_msg("Koneksi database berhasil", 'success');
    
    // Check existing indexes
    $existingIndexes = [];
    $stmt = $pdo->query("SHOW INDEX FROM customers");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIndexes[] = $row['Key_name'];
    }
    
    // Add indexes if not exists
    $indexes = [
        "ALTER TABLE customers ADD INDEX idx_phone (phone)",
        "ALTER TABLE customers ADD INDEX idx_status (status)",
        "ALTER TABLE customers ADD INDEX idx_pppoe (pppoe_username)",
        "ALTER TABLE customers ADD INDEX idx_package (package_id)",
        "ALTER TABLE invoices ADD INDEX idx_due_date (due_date)",
        "ALTER TABLE invoices ADD INDEX idx_status (status)",
        "ALTER TABLE invoices ADD INDEX idx_paid (paid)",
        "ALTER TABLE invoices ADD INDEX idx_customer (customer_id, status)",
        "ALTER TABLE invoices ADD INDEX idx_paid_at (paid_at)",
        "ALTER TABLE trouble_tickets ADD INDEX idx_status (status)",
        "ALTER TABLE trouble_tickets ADD INDEX idx_customer (customer_id)",
        "ALTER TABLE trouble_tickets ADD INDEX idx_created (created_at)",
        "ALTER TABLE onu_locations ADD INDEX idx_customer (customer_id)",
        "ALTER TABLE onu_locations ADD INDEX idx_serial (serial_number)",
        "ALTER TABLE webhook_logs ADD INDEX idx_source (source)",
        "ALTER TABLE webhook_logs ADD INDEX idx_created (created_at)",
    ];
    
    $added = 0;
    $exists = 0;
    
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
            $added++;
            
            // Extract index name from SQL
            preg_match('/ADD INDEX (\w+)/', $sql, $matches);
            $indexName = $matches[1] ?? 'unknown';
            log_msg("Index ditambahkan: $indexName", 'success');
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $exists++;
            } else {
                log_msg("Error: " . $e->getMessage(), 'error');
            }
        }
    }
    
    log_msg("Database indexes: $added ditambahkan, $exists sudah ada", 'success');
    
} catch (PDOException $e) {
    log_msg("Database error: " . $e->getMessage(), 'error');
}

// ============================================
// 3. UPDATE ROUTES (Check if new routes exist)
// ============================================
log_msg("Mengecek routes...", 'info');

$routesFile = __DIR__ . '/app/Config/Routes.php';
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);
    
    $newRoutes = [
        'api/dashboard/stats',
        'api/analytics/summary',
        'api/invoices/recent',
        'api/customers/list',
        'api/invoices/list',
        'api/mikrotik/users',
    ];
    
    $missing = [];
    foreach ($newRoutes as $route) {
        if (strpos($routesContent, $route) === false) {
            $missing[] = $route;
        }
    }
    
    if (empty($missing)) {
        log_msg("Semua routes sudah ada", 'success');
    } else {
        log_msg("Routes yang belum ada: " . implode(', ', $missing), 'warning');
        log_msg("Jalankan update.php untuk menambahkan routes baru", 'info');
    }
}

// ============================================
// 4. CHECK API ENDPOINTS
// ============================================
log_msg("Mengecek API endpoints...", 'info');

$apiFile = __DIR__ . '/app/Controllers/Api.php';
if (file_exists($apiFile)) {
    $apiContent = file_get_contents($apiFile);
    
    $newMethods = [
        'dashboardStats',
        'analyticsSummary',
        'recentInvoices',
        'customersList',
        'invoicesList',
        'mikrotikUsers',
    ];
    
    $missing = [];
    foreach ($newMethods as $method) {
        if (strpos($apiContent, "function $method") === false) {
            $missing[] = $method;
        }
    }
    
    if (empty($missing)) {
        log_msg("Semua API endpoints sudah ada", 'success');
    } else {
        log_msg("API methods yang belum ada: " . implode(', ', $missing), 'warning');
        log_msg("Jalankan update.php untuk menambahkan API baru", 'info');
    }
}

// ============================================
// 5. CHECK CUSTOMER CRUD
// ============================================
log_msg("Mengecek Customer CRUD...", 'info');

$billingFile = __DIR__ . '/app/Controllers/Billing.php';
if (file_exists($billingFile)) {
    $billingContent = file_get_contents($billingFile);
    
    $methods = ['editCustomer', 'deleteCustomer'];
    $missing = [];
    
    foreach ($methods as $method) {
        if (strpos($billingContent, "function $method") === false) {
            $missing[] = $method;
        }
    }
    
    if (empty($missing)) {
        log_msg("Customer CRUD lengkap", 'success');
    } else {
        log_msg("Methods yang belum ada: " . implode(', ', $missing), 'warning');
        log_msg("Jalankan update.php untuk menambahkan CRUD lengkap", 'info');
    }
}

// ============================================
// 6. CHECK TROUBLE TICKET CRUD
// ============================================
log_msg("Mengecek Trouble Ticket CRUD...", 'info');

$adminFile = __DIR__ . '/app/Controllers/Admin.php';
if (file_exists($adminFile)) {
    $adminContent = file_get_contents($adminFile);
    
    $methods = ['createTicket', 'updateTicket', 'assignTicket', 'closeTicket'];
    $missing = [];
    
    foreach ($methods as $method) {
        if (strpos($adminContent, "function $method") === false) {
            $missing[] = $method;
        }
    }
    
    if (empty($missing)) {
        log_msg("Trouble Ticket CRUD lengkap", 'success');
    } else {
        log_msg("Methods yang belum ada: " . implode(', ', $missing), 'warning');
        log_msg("Jalankan update.php untuk menambahkan Ticket CRUD", 'info');
    }
}

// ============================================
// SUMMARY
// ============================================
if (!$isCli) {
    echo "<h2 class='success'>✅ Migration Check Selesai</h2>";
    echo "<p>Untuk mendapatkan semua fitur terbaru, jalankan:</p>";
    echo "<p><code>php update.php</code></p>";
    echo "<p>atau kunjungi:</p>";
    echo "<p><a href='/update.php' style='color: #38bdf8;'>http://yourdomain.com/update.php</a></p>";
    echo "<h3>Fitur Baru yang Akan Ditambahkan:</h3>";
    echo "<ul>";
    echo "<li>✅ Realtime data dengan auto-refresh</li>";
    echo "<li>✅ Pagination AJAX (no hard refresh)</li>";
    echo "<li>✅ Customer Edit & Delete</li>";
    echo "<li>✅ Trouble Ticket CRUD lengkap</li>";
    echo "<li>✅ Database indexes untuk performa</li>";
    echo "<li>✅ Browser caching & Gzip compression</li>";
    echo "<li>✅ Security headers</li>";
    echo "</ul>";
    echo "</div></body></html>";
} else {
    echo "\n✅ Migration check selesai\n";
    echo "Jalankan: php update.php untuk update lengkap\n";
}
?>
