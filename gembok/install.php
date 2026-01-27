<?php
/**
 * install.php – Single File Database Installer & Upgrader
 *
 * Usage: 
 *   - Browser: http://your-domain.com/install.php
 *   - CLI: php install.php
 * 
 * Functions:
 *   1. Connects to MySQL using .env credentials
 *   2. Creates all tables with latest schema
 *   3. Adds default data (Admin user, key settings)
 *   4. Safe to run multiple times (Idempotent)
 */

// Detect Environment
$isCli = php_sapi_name() === 'cli';
$br = $isCli ? "\n" : "<br>";

// HTML Header for Browser Mode
if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>GEMBOK Installer</title>";
    echo "<style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; font-size: 1.8rem; }
        p.subtitle { color: #94a3b8; margin-bottom: 2rem; margin-top: 0; }
        h2 { color: #f8fafc; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; margin-top: 2rem; font-size: 1.25rem; }
        .success { color: #4ade80; }
        .error { color: #f87171; font-weight: bold; }
        .warning { color: #fbbf24; }
        .info { color: #38bdf8; }
        .log-item { margin: 0.25rem 0; font-family: 'Consolas', monospace; font-size: 0.9rem; }
        .btn { display: inline-block; padding: 0.75rem 2rem; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 2rem; transition: background 0.2s; }
        .btn:hover { background: #0284c7; }
        code { background: #0f172a; padding: 0.1rem 0.3rem; border-radius: 4px; color: #e2e8f0; }
    </style></head><body><div class='container'>";
    echo "<h1>🚀 GEMBOK Database Auto-Installer</h1>";
    echo "<p class='subtitle'>Setup database tables and default configuration.</p>";
}

// -------------------------------------------------
// 1. Dependencies & Config
// -------------------------------------------------

// Load Composer Autoload (for Dotenv)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback if vendor not strictly needed for basic PHP logic, 
    // but code uses Dotenv so it IS needed.
    $msg = "⚠️ Error: 'vendor' directory not found. Please run 'composer install' or upload vendor folder.";
    echo $isCli ? $msg . "\n" : "<p class='error'>$msg</p></div></body></html>";
    exit;
}

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } catch (Exception $e) {
        // Ignore error if env invalid
    }
}

// Get DB Credentials
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'gembok_db'); // Support both keys
$user = $_ENV['DB_USERNAME'] ?? ($_ENV['DB_USER'] ?? 'root');
$pass = $_ENV['DB_PASSWORD'] ?? ($_ENV['DB_PASS'] ?? '');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo $isCli 
        ? "✅ Connected to database: $db\n" 
        : "<div class='log-item success'>✅ Connected to database: <strong>$db</strong></div>";
} catch (PDOException $e) {
    $msg = "❌ Database Connection Failed: " . $e->getMessage();
    echo $isCli ? $msg . "\n" : "<p class='error'>$msg</p><p>Please check your <code>.env</code> file credentials.</p></div></body></html>";
    exit;
}

// -------------------------------------------------
// 2. Helper Functions
// -------------------------------------------------

function execQuery(PDO $pdo, string $sql, string $msg, bool $isCli) {
    try {
        $pdo->exec($sql);
        echo $isCli ? "   ✔ $msg\n" : "<div class='log-item success'>✔ $msg</div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo $isCli ? "   NOTE: $msg (Already Exists)\n" : "<div class='log-item warning'>NOTE: $msg (Already Exists)</div>";
        } else {
            echo $isCli ? "   ❌ $msg - " . $e->getMessage() . "\n" : "<div class='log-item error'>❌ $msg - " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

function addColumn(PDO $pdo, $table, $col, $def, $isCli) {
    // Check if column exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $col]);
    
    if ($stmt->fetchColumn() == 0) {
        execQuery($pdo, "ALTER TABLE `$table` ADD `$col` $def", "Added column `$col` to `$table`", $isCli);
    }
}

function renameColumn(PDO $pdo, $table, $oldCol, $newCol, $def, $isCli) {
    // Check if old column exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $oldCol]);
    
    if ($stmt->fetchColumn() > 0) {
        // Old column exists, rename it
        execQuery($pdo, "ALTER TABLE `$table` CHANGE `$oldCol` `$newCol` $def", "Renamed column `$oldCol` to `$newCol` in `$table`", $isCli);
    } else {
        // Check if new column already exists
        $stmt->execute([$table, $newCol]);
        if ($stmt->fetchColumn() == 0) {
            // Neither exists, add the new column
            execQuery($pdo, "ALTER TABLE `$table` ADD `$newCol` $def", "Added column `$newCol` to `$table`", $isCli);
        }
    }
}

// -------------------------------------------------
// 3. Create Tables (Full Schema)
// -------------------------------------------------

if (!$isCli) echo "<h2>📦 Creating Tables</h2>";
else echo "\n📦 Creating Tables...\n";

// A. Users (Admin/Agents)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(20) NULL,
    `role` ENUM('admin','agent','customer','technician') DEFAULT 'customer',
    `whatsapp_lid` VARCHAR(50) NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `users`", $isCli);

// B. Settings (Config)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `settings`", $isCli);

// C. Packages (Internet Plans)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `profile_normal` VARCHAR(100) NOT NULL,
    `profile_isolir` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `packages`", $isCli);

// D. Customers
// Updated with all billing columns
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `package_id` INT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `pppoe_username` VARCHAR(50) NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(150) NULL,
    `address` TEXT NULL,
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `isolation_date` INT DEFAULT 20, 
    `whatsapp_lid` VARCHAR(50) NULL,
    `status` ENUM('active','inactive','isolated','paid','unpaid') DEFAULT 'inactive',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `customers`", $isCli);

// E. Invoices
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NOT NULL,
    `paid` BOOLEAN DEFAULT FALSE,
    `status` ENUM('pending','paid','cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `invoices`", $isCli);

// F. Payments
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'cash',
    `payment_reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `payments`", $isCli);

// G. Vouchers (Hotspot History)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `vouchers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(50) NOT NULL,
    `profile` VARCHAR(50) NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `used` BOOLEAN DEFAULT FALSE,
    `used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `vouchers`", $isCli);

// H. ODP Locations (Map)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `odp_locations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `lat` DECIMAL(10,6) NOT NULL,
    `lng` DECIMAL(10,6) NOT NULL,
    `capacity` INT DEFAULT 8,
    `used_ports` INT DEFAULT 0,
    `parent_odp_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `odp_locations`", $isCli);

// I. ONU Locations (Map)
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `onu_locations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `serial_number` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `lat` DECIMAL(10,6) NOT NULL,
    `lng` DECIMAL(10,6) NOT NULL,
    `odp_id` INT UNSIGNED NULL,
    `customer_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `onu_locations`", $isCli);

// J. Trouble Tickets
execQuery($pdo, "CREATE TABLE IF NOT EXISTS `trouble_tickets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending','in_progress','resolved','closed') DEFAULT 'pending',
    `priority` ENUM('low','medium','high') DEFAULT 'low',
    `assigned_to` INT UNSIGNED NULL,
    `resolution_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "Table `trouble_tickets`", $isCli);


// -------------------------------------------------
// 4. Update Schema (For Existing Installations)
// -------------------------------------------------
if (!$isCli) echo "<h2>🔧 Updating Schema (If needed)</h2>";
else echo "\n🔧 Updating Schema...\n";

// Add missing columns if they weren't in the original CREATE
addColumn($pdo, 'customers', 'package_id', 'INT UNSIGNED NULL', $isCli);
addColumn($pdo, 'customers', 'isolation_date', 'INT DEFAULT 20', $isCli);
addColumn($pdo, 'customers', 'lat', 'DECIMAL(10,8) NULL', $isCli);
addColumn($pdo, 'customers', 'lng', 'DECIMAL(10,8) NULL', $isCli);
addColumn($pdo, 'customers', 'portal_password', 'VARCHAR(255) NULL', $isCli); // New Password Column
addColumn($pdo, 'users', 'phone', 'VARCHAR(20) NULL', $isCli); // New Phone Column for Users/Technicians

// Update role enum for users
execQuery($pdo, "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','agent','customer','technician') DEFAULT 'customer'", "Updated `users` role enum to include `technician`", $isCli);

// Update trouble_tickets assigned_to column type
execQuery($pdo, "ALTER TABLE `trouble_tickets` MODIFY COLUMN `assigned_to` INT UNSIGNED NULL", "Updated `trouble_tickets.assigned_to` to INT UNSIGNED", $isCli);

addColumn($pdo, 'trouble_tickets', 'attachment', 'VARCHAR(255) NULL', $isCli); // Photo proof
addColumn($pdo, 'invoices', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $isCli);

// Fix: Rename old column name to new standard
renameColumn($pdo, 'onu_locations', 'serial', 'serial_number', 'VARCHAR(100) NOT NULL', $isCli);


// -------------------------------------------------
// 5. Default Data
// -------------------------------------------------
if (!$isCli) echo "<h2>🌱 Default Data</h2>";
else echo "\n🌱 Default Data...\n";

// A. Default Admin
$stmt = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `username` = 'admin'");
if ($stmt->fetchColumn() == 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES (?, ?, ?, ?)")
        ->execute(['admin', $pass, 'Administrator', 'admin']);
    echo $isCli ? "   ✔ Admin User Created (admin / admin123)\n" : "<div class='log-item success'>✔ Admin User Created (User: admin / Pass: admin123)</div>";
} else {
    echo $isCli ? "   NOTE: Admin user already exists\n" : "<div class='log-item info'>NOTE: Admin user already exists</div>";
}

// B. Default Settings
$defaults = [
    'APP_NAME' => 'GEMBOK APP',
    'COMPANY_NAME' => 'Gembok Internet',
    'COMPANY_ADDRESS' => 'Jl. Internet No. 1, Cyber City',
    'COMPANY_PHONE' => '081234567890',
    'MIKROTIK_HOST' => '192.168.1.1',
    'MIKROTIK_USER' => 'admin',
    'MIKROTIK_PASS' => '',
    'MIKROTIK_PORT' => '8728',
    'WHATSAPP_API_URL' => '',
    'WHATSAPP_TOKEN' => '',
    'GENIEACS_URL' => 'http://localhost:7557',
    'CRON_SECRET' => 'gembok_secret_cron_123'
];

foreach ($defaults as $k => $v) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `settings` WHERE `key` = ?");
    $stmt->execute([$k]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)")->execute([$k, $v]);
    }
}
echo $isCli ? "   ✔ Settings initialized\n" : "<div class='log-item success'>✔ Settings initialized</div>";

// -------------------------------------------------
// 6. Finish
// -------------------------------------------------
if (!$isCli) {
    // Auto-detect full URL with protocol and domain
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Production: admin login is at /admin/login (not /public/admin/...)
    $adminUrl = $protocol . '://' . $host . '/admin/login';
    
    echo "<h2 class='success' style='margin-top:2rem'>✅ Install Failed? No, it Succeeded!</h2>";
    echo "<p>If you see green checks above, everything is ready.</p>";
    echo "<a href='$adminUrl' class='btn'>🚀 Go to Admin Dashboard</a>";
    echo "</div></body></html>";
} else {
    echo "\n✅ Installation Complete!\n";
}
?>
