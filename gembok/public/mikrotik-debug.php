<?php
/**
 * Simple Error Logger for MikroTik Page
 * Place this at: public/mikrotik-debug.php
 * Access: https://gembok.alijaya.net/mikrotik-debug.php
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>MikroTik Debug</h1>";
echo "<pre>";

try {
    // Load CodeIgniter
    require __DIR__ . '/../vendor/autoload.php';
    
    // Load .env manually
    if (file_exists(__DIR__ . '/../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = str_replace("\r", '', trim($line));
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    echo "✅ .env loaded\n\n";
    
    // Show raw .env content (first 30 lines)
    echo "Raw .env File (First 30 Lines):\n";
    echo "================================\n";
    if (file_exists(__DIR__ . '/../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        $lines = explode("\n", $envContent);
        $count = 0;
        foreach ($lines as $line) {
            if ($count >= 30) break;
            // Mask passwords
            if (stripos($line, 'PASSWORD') !== false || stripos($line, 'PASS') !== false) {
                if (strpos($line, '=') !== false) {
                    list($k, $v) = explode('=', $line, 2);
                    $line = $k . '=****';
                }
            }
            echo htmlspecialchars($line) . "\n";
            $count++;
        }
    }
    echo "================================\n\n";
    
    // Get MikroTik config
    $host = $_ENV['MIKROTIK_HOST'] ?? getenv('MIKROTIK_HOST') ?? '';
    $port = $_ENV['MIKROTIK_PORT'] ?? getenv('MIKROTIK_PORT') ?? '8728';
    $user = $_ENV['MIKROTIK_USER'] ?? getenv('MIKROTIK_USER') ?? '';
    $pass = $_ENV['MIKROTIK_PASS'] ?? getenv('MIKROTIK_PASS') ?? '';
    
    echo "MikroTik Config from .env:\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "User: $user\n";
    echo "Pass: " . (empty($pass) ? 'EMPTY' : '****') . "\n\n";
    
    // Check database
    $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? '';
    $dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
    
    echo "Database Config:\n";
    echo "Host: $dbHost\n";
    echo "Database: $dbName\n";
    echo "User: $dbUser\n";
    echo "Pass: " . (empty($dbPass) ? 'EMPTY' : '****') . "\n\n";
    
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    echo "✅ Database connected\n\n";
    
    // Get MikroTik settings from database
    $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'MIKROTIK%'");
    $stmt->execute();
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "MikroTik Config from Database:\n";
    if (empty($dbSettings)) {
        echo "⚠️ No settings in database (will use .env)\n\n";
    } else {
        foreach ($dbSettings as $k => $v) {
            $display = (stripos($k, 'PASS') !== false) ? '****' : $v;
            $empty = ($v === '' || $v === null) ? ' (EMPTY!)' : '';
            echo "$k = $display$empty\n";
        }
        echo "\n";
    }
    
    // Final config (database > .env)
    $finalHost = (!empty($dbSettings['MIKROTIK_HOST'])) ? $dbSettings['MIKROTIK_HOST'] : $host;
    $finalPort = (!empty($dbSettings['MIKROTIK_PORT'])) ? $dbSettings['MIKROTIK_PORT'] : $port;
    $finalUser = (!empty($dbSettings['MIKROTIK_USER'])) ? $dbSettings['MIKROTIK_USER'] : $user;
    $finalPass = (!empty($dbSettings['MIKROTIK_PASS'])) ? $dbSettings['MIKROTIK_PASS'] : $pass;
    
    echo "Final Config (Database > .env):\n";
    echo "Host: $finalHost\n";
    echo "Port: $finalPort\n";
    echo "User: $finalUser\n";
    echo "Pass: " . (empty($finalPass) ? 'EMPTY' : '****') . "\n\n";
    
    // Test socket connection
    echo "Testing socket connection to $finalHost:$finalPort...\n";
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($finalHost, $finalPort, $errno, $errstr, 5);
    
    if ($socket) {
        echo "✅ Socket connection successful!\n";
        fclose($socket);
    } else {
        echo "❌ Socket connection failed!\n";
        echo "Error: $errstr (Code: $errno)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
