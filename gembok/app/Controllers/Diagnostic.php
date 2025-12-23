<?php
namespace App\Controllers;

use App\Services\GenieacsService;
use App\Models\CustomerModel;

/**
 * Quick Diagnostic - PPPoE Username Matching
 */
class Diagnostic extends BaseController
{
    public function pppoeMatch()
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $genieacs = new GenieacsService();
        $customerModel = new CustomerModel();
        
        echo "==============================================\n";
        echo "DIAGNOSTIC: PPPoE Username Matching\n";
        echo "==============================================\n\n";
        
        // Get phone from query string
        $phone = $this->request->getGet('phone');
        
        if (!$phone) {
            echo "❌ ERROR: No phone parameter provided\n";
            echo "Usage: /diagnostic/pppoeMatch?phone=628123456789\n";
            return;
        }
        
        echo "📱 Checking for phone: {$phone}\n\n";
        
        // Get customer
        $customer = $customerModel->where('phone', $phone)->first();
        
        if (!$customer) {
            echo "❌ Customer NOT found in database!\n";
            return;
        }
        
        echo "✅ Customer found: {$customer['name']}\n";
        echo "   ID: {$customer['id']}\n";
        echo "   PPPoE Username: " . ($customer['pppoe_username'] ?: '❌ NULL/EMPTY') . "\n";
        echo "   PPPoE Length: " . strlen($customer['pppoe_username'] ?? '') . " chars\n";
        echo "   PPPoE (HEX): " . bin2hex($customer['pppoe_username'] ?? '') . "\n\n";
        
        if (empty($customer['pppoe_username'])) {
            echo "❌ PROBLEM: Customer has NO PPPoE username!\n";
            echo "   Fix: UPDATE customers SET pppoe_username='user001' WHERE id={$customer['id']};\n";
            return;
        }
        
        // Search in GenieACS
        echo "🔍 Searching in GenieACS...\n\n";
        
        try {
            // TEST: Fetch WITHOUT projection to get ALL data
            echo "--- TEST 1: With Standard Projection ---\n";
            $result = $genieacs->getDevices(false); // No cache
            
            if ($result['code'] !== 200) {
                echo "❌ GenieACS Error: Code {$result['code']}\n";
                return;
            }
            
            $devices = $result['body'] ?? [];
            echo "Found " . count($devices) . " devices\n";
            
            if (!empty($devices)) {
                $firstDevice = $devices[0];
                $params = $firstDevice['parameters'] ?? [];
                echo "Parameters count: " . count($params) . "\n\n";
            }
            
            // TEST 2: Fetch WITHOUT projection (raw request)
            echo "--- TEST 2: WITHOUT Projection (Full Data) ---\n";
            
            // Direct request to GenieACS without projection
            require_once APPPATH . 'Services/ConfigService.php';
            $config = new \App\Services\ConfigService();
            $baseUrl = rtrim($config->get('GENIEACS_URL'), '/');
            $username = $config->get('GENIEACS_USERNAME');
            $password = $config->get('GENIEACS_PASSWORD');
            
            $ch = curl_init($baseUrl . '/devices?limit=1');
            $authHeader = 'Authorization: Basic ' . base64_encode("{$username}:{$password}");
            
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [$authHeader, 'Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($code === 200) {
                $rawDevices = json_decode($response, true);
                echo "HTTP 200 OK\n";
                echo "Devices fetched: " . count($rawDevices) . "\n\n";
                
                if (!empty($rawDevices)) {
                    $rawDevice = $rawDevices[0];
                    
                    echo "Device structure:\n";
                    echo "  _id: " . ($rawDevice['_id'] ?? 'N/A') . "\n";
                    echo "  _deviceId: " . json_encode($rawDevice['_deviceId'] ?? []) . "\n";
                    echo "  _lastInform: " . ($rawDevice['_lastInform'] ?? 'N/A') . "\n\n";
                    
                    // Check if parameters exist
                    if (isset($rawDevice['parameters'])) {
                        $rawParams = $rawDevice['parameters'];
                        echo "Parameters found: " . count($rawParams) . "\n";
                        echo "Showing PPPoE/Username related:\n\n";
                        
                        foreach ($rawParams as $key => $value) {
                            if (stripos($key, 'pppoe') !== false || 
                                stripos($key, 'username') !== false || 
                                stripos($key, 'Virtual') !== false) {
                                $displayValue = is_array($value) ? json_encode($value) : $value;
                                echo "  {$key} = {$displayValue}\n";
                            }
                        }
                    } else {
                        echo "⚠️ NO 'parameters' key in device response!\n";
                        echo "Available keys: " . implode(', ', array_keys($rawDevice)) . "\n\n";
                        echo "Full device structure:\n";
                        echo json_encode($rawDevice, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                    }
                }
            } else {
                echo "❌ HTTP Error: {$code}\n";
            }
            
            echo "\n";
            
            // Show RAW parameters dari device pertama untuk debugging
            if (!empty($devices)) {
                echo "--- RAW PARAMETERS (First Device) ---\n";
                $firstDevice = $devices[0];
                $params = $firstDevice['parameters'] ?? [];
                
                echo "Total parameters: " . count($params) . "\n";
                echo "Showing all parameter keys:\n\n";
                
                foreach ($params as $key => $value) {
                    // Only show relevant ones
                    if (stripos($key, 'pppoe') !== false || 
                        stripos($key, 'username') !== false || 
                        stripos($key, 'Virtual') !== false ||
                        stripos($key, 'WAN') !== false) {
                        echo "  {$key} = " . (is_string($value) ? "'{$value}'" : json_encode($value)) . "\n";
                    }
                }
                echo "\n";
            }
            
            // List all PPPoE usernames
            echo "--- All PPPoE Usernames in GenieACS ---\n";
            $foundMatch = false;
            
            foreach ($devices as $idx => $dev) {
                $params = $dev['parameters'] ?? [];
                $deviceId = $dev['deviceId'] ?? [];
                $serial = $deviceId['_SerialNumber'] ?? 'N/A';
                
                // Try multiple paths
                $pppoeUserPaths = [
                    'VirtualParameters.pppoeUsername',
                    'VirtualParameters.pppoeUsername2',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                ];
                
                $devicePppoe = null;
                $foundPath = null;
                
                foreach ($pppoeUserPaths as $path) {
                    if (!empty($params[$path])) {
                        $devicePppoe = $params[$path];
                        $foundPath = $path;
                        break;
                    }
                }
                
                if ($devicePppoe) {
                    $isMatch = (trim($devicePppoe) === trim($customer['pppoe_username']));
                    $matchSymbol = $isMatch ? '✅ MATCH!' : '  ';
                    
                    echo "{$matchSymbol} Device #{$idx}: {$serial}\n";
                    echo "   Path: {$foundPath}\n";
                    echo "   PPPoE: '{$devicePppoe}'\n";
                    echo "   Length: " . strlen($devicePppoe) . " chars\n";
                    echo "   HEX: " . bin2hex($devicePppoe) . "\n";
                    
                    if ($isMatch) {
                        $foundMatch = true;
                        echo "   ✅✅✅ THIS IS THE MATCH! ✅✅✅\n";
                    } else {
                        // Show difference
                        echo "   Diff:\n";
                        echo "     Customer: '" . $customer['pppoe_username'] . "' (HEX: " . bin2hex($customer['pppoe_username']) . ")\n";
                        echo "     Device:   '{$devicePppoe}' (HEX: " . bin2hex($devicePppoe) . ")\n";
                    }
                    echo "\n";
                }
            }
            
            echo "==============================================\n";
            if ($foundMatch) {
                echo "✅ RESULT: Match found!\n";
                echo "   Device should appear in portal.\n";
                echo "   If not, check Portal.php is uploaded.\n";
            } else {
                echo "❌ RESULT: NO MATCH FOUND!\n";
                echo "\nPossible issues:\n";
                echo "1. PPPoE username in database is wrong\n";
                echo "2. Device has different PPPoE username\n";
                echo "3. Case sensitivity (e.g., 'User001' vs 'user001')\n";
                echo "4. Whitespace (e.g., 'user001 ' vs 'user001')\n";
                echo "\nRecommended fix:\n";
                echo "Check the list above and update customer PPPoE to match device:\n";
                echo "UPDATE customers SET pppoe_username='CORRECT_VALUE' WHERE id={$customer['id']};\n";
            }
            echo "==============================================\n";
            
        } catch (\Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    }
}
