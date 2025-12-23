<?php
namespace App\Controllers;

use App\Services\GenieacsService;
use App\Models\CustomerModel;

/**
 * Debug Tool - GenieACS Connection Test
 * 
 * Access: https://gembok.alijaya.net/debug/genieacs
 */
class Debug extends BaseController
{
    public function genieacs()
    {
        $genieacs = new GenieacsService();
        $customerModel = new CustomerModel();
        
        echo "<html><head><title>GenieACS Debug</title>";
        echo "<style>
            body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
            h2 { color: #0ff; border-bottom: 2px solid #0ff; padding-bottom: 10px; }
            .success { color: #0f0; }
            .error { color: #f00; }
            .warning { color: #ff0; }
            pre { background: #000; padding: 15px; border: 1px solid #333; overflow-x: auto; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #333; padding: 8px; text-align: left; }
            th { background: #333; color: #0ff; }
        </style></head><body>";
        
        echo "<h1>🔍 GenieACS Debug Tool</h1>";
        
        // Test 1: GenieACS Connection
        echo "<h2>1. GenieACS Connection Test</h2>";
        try {
            $result = $genieacs->getDevices();
            
            if ($result['code'] === 200) {
                $deviceCount = count($result['body'] ?? []);
                echo "<p class='success'>✅ GenieACS Connected!</p>";
                echo "<p>Total Devices: <strong>{$deviceCount}</strong></p>";
            } else {
                echo "<p class='error'>❌ GenieACS Error</p>";
                echo "<pre>" . print_r($result, true) . "</pre>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
        }
        
        // Test 2: List All Devices with PPPoE Info
        echo "<h2>2. Devices List (with PPPoE Username)</h2>";
        try {
            $result = $genieacs->getDevices(false); // Don't use cache
            
            if ($result['code'] === 200 && !empty($result['body'])) {
                echo "<table>";
                echo "<tr><th>#</th><th>Serial</th><th>Model</th><th>PPPoE Username</th><th>Status</th></tr>";
                
                $no = 1;
                foreach ($result['body'] as $dev) {
                    $params = $dev['parameters'] ?? [];
                    $deviceId = $dev['deviceId'] ?? [];
                    
                    $serial = $deviceId['_SerialNumber'] ?? 'N/A';
                    $model = $params['InternetGatewayDevice.DeviceInfo.ModelName'] ?? 'N/A';
                    
                    // Try to find PPPoE username in different paths
                    $pppoeUser = '';
                    $pppoeUserPaths = [
                        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
                        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username',
                        'VirtualParameters.pppoeUsername',
                        'VirtualParameters.pppoeUsername2',
                    ];
                    
                    foreach ($pppoeUserPaths as $path) {
                        if (!empty($params[$path])) {
                            $pppoeUser = $params[$path];
                            break;
                        }
                    }
                    
                    $online = !empty($dev['lastInform']) ? '🟢 Online' : '🔴 Offline';
                    
                    echo "<tr>";
                    echo "<td>{$no}</td>";
                    echo "<td>{$serial}</td>";
                    echo "<td>{$model}</td>";
                    echo "<td>" . ($pppoeUser ?: '<span class="error">No PPPoE</span>') . "</td>";
                    echo "<td>{$online}</td>";
                    echo "</tr>";
                    
                    $no++;
                }
                
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ No devices found</p>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        // Test 3: Customers with PPPoE
        echo "<h2>3. Customers with PPPoE Username</h2>";
        $customers = $customerModel->where('pppoe_username IS NOT NULL')->where('pppoe_username !=', '')->findAll();
        
        if (!empty($customers)) {
            echo "<table>";
            echo "<tr><th>#</th><th>Name</th><th>Phone</th><th>PPPoE Username</th><th>Device Found?</th></tr>";
            
            $no = 1;
            foreach ($customers as $cust) {
                $device = $genieacs->getDeviceByPppoeUsername($cust['pppoe_username']);
                $found = $device ? '<span class="success">✅ Found</span>' : '<span class="error">❌ Not Found</span>';
                
                echo "<tr>";
                echo "<td>{$no}</td>";
                echo "<td>{$cust['name']}</td>";
                echo "<td>{$cust['phone']}</td>";
                echo "<td><strong>{$cust['pppoe_username']}</strong></td>";
                echo "<td>{$found}</td>";
                echo "</tr>";
                
                $no++;
            }
            
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No customers with PPPoE username found</p>";
        }
        
        // Test 4: Test Specific PPPoE
        echo "<h2>4. Test Specific PPPoE Username</h2>";
        $testUsername = $this->request->getGet('pppoe');
        
        if ($testUsername) {
            echo "<p>Testing PPPoE: <strong>{$testUsername}</strong></p>";
            
            $device = $genieacs->getDeviceByPppoeUsername($testUsername);
            
            if ($device) {
                echo "<p class='success'>✅ Device Found!</p>";
                echo "<pre>" . print_r($device, true) . "</pre>";
            } else {
                echo "<p class='error'>❌ Device NOT Found</p>";
            }
        } else {
            echo "<p>Add <code>?pppoe=username</code> to URL to test specific username</p>";
        }
        
        // Test 5: All Available Parameters
        echo "<h2>5. Show All Parameters (First Device)</h2>";
        try {
            $result = $genieacs->getDevices(false);
            
            if ($result['code'] === 200 && !empty($result['body'])) {
                $firstDevice = $result['body'][0];
                $params = $firstDevice['parameters'] ?? [];
                
                echo "<p class='success'>Showing parameters for first device:</p>";
                echo "<pre>";
                
                // Filter only WAN and PPPoE related parameters
                foreach ($params as $key => $value) {
                    if (stripos($key, 'WAN') !== false || stripos($key, 'pppoe') !== false || stripos($key, 'Username') !== false) {
                        echo "{$key} = {$value}\n";
                    }
                }
                
                echo "</pre>";
            }
        } catch (\Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        
        echo "</body></html>";
    }
}
