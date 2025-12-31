<?php
/**
 * Temporary MikroTik Page Workaround
 * Place at: public/temp-mikrotik.php
 * Access: https://gembok.alijaya.net/temp-mikrotik.php
 */

// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html>
<head>
    <title>MikroTik Management (Temp)</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .error { background: #fee; border-left: 4px solid #f00; padding: 10px; margin: 10px 0; }
        .success { background: #efe; border-left: 4px solid #0f0; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 MikroTik Management (Temporary)</h1>
    <p><a href="/admin">← Back to Admin</a></p>

<?php
try {
    // Load autoloader
    require __DIR__ . '/../vendor/autoload.php';
    
    // Load .env
    if (file_exists(__DIR__ . '/../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = str_replace("\r", '', trim($line));
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, '"\'');
            }
        }
    }
    
    // Load MikrotikService
    require_once __DIR__ . '/../app/Services/ConfigService.php';
    require_once __DIR__ . '/../app/Services/MikrotikService.php';
    
    $mik = new \App\Services\MikrotikService();
    
    echo "<h2>Connection Status</h2>";
    if ($mik->isConnected()) {
        echo "<div class='success'>✅ Connected to MikroTik</div>";
        
        // Get PPPoE Secrets
        echo "<h2>PPPoE Secrets</h2>";
        try {
            $secrets = $mik->getPppoeSecrets();
            if (empty($secrets)) {
                echo "<p>No PPPoE secrets found</p>";
            } else {
                echo "<table>";
                echo "<tr><th>Name</th><th>Profile</th><th>Service</th><th>Disabled</th></tr>";
                foreach ($secrets as $secret) {
                    $name = htmlspecialchars($secret['name'] ?? '');
                    $profile = htmlspecialchars($secret['profile'] ?? '');
                    $service = htmlspecialchars($secret['service'] ?? '');
                    $disabled = isset($secret['disabled']) && $secret['disabled'] === 'true' ? '❌' : '✅';
                    echo "<tr><td>$name</td><td>$profile</td><td>$service</td><td>$disabled</td></tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error getting PPPoE secrets: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        
        // Get Active PPPoE
        echo "<h2>Active PPPoE Connections</h2>";
        try {
            $active = $mik->getPppoeActive();
            if (empty($active)) {
                echo "<p>No active connections</p>";
            } else {
                echo "<table>";
                echo "<tr><th>Name</th><th>Address</th><th>Uptime</th></tr>";
                foreach ($active as $conn) {
                    $name = htmlspecialchars($conn['name'] ?? '');
                    $address = htmlspecialchars($conn['address'] ?? '');
                    $uptime = htmlspecialchars($conn['uptime'] ?? '');
                    echo "<tr><td>$name</td><td>$address</td><td>$uptime</td></tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error getting active connections: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Cannot connect to MikroTik</div>";
        echo "<p>Error: " . htmlspecialchars($mik->getLastError()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

</div>
</body>
</html>
