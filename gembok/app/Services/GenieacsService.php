<?php
namespace App\Services;
use App\Services\ConfigService;

class GenieacsService
{
    private string $baseUrl;
    private string $authHeader;
    private ConfigService $config;
    private string $cacheDir;
    private int $cacheExpiry = 30; // Cache for 30 seconds

    public function __construct()
    {
        $this->config = new ConfigService();
        $this->baseUrl = rtrim($this->config->get('GENIEACS_URL'), '/');
        $username = $this->config->get('GENIEACS_USERNAME');
        $password = $this->config->get('GENIEACS_PASSWORD');
        $token    = $this->config->get('GENIEACS_TOKEN');
        if ($username && $password) {
            $this->authHeader = 'Authorization: Basic ' . base64_encode("{$username}:{$password}");
        } elseif ($token) {
            $this->authHeader = 'Authorization: Bearer ' . $token;
        } else {
            $this->authHeader = '';
        }
        
        // Setup cache directory
        $this->cacheDir = WRITEPATH . 'cache/genieacs/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getDevices(bool $useCache = true): array
    {
        $cacheKey = 'devices_list';
        
        // Try to get from cache first
        if ($useCache) {
            $cached = $this->getCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Fetch from API
        $projection = $this->getProjectionFields();
        $result = $this->request('GET', '/devices', ['projection' => $projection]);
        
        // Cache the result
        if (isset($result['code']) && $result['code'] === 200) {
            $this->setCache($cacheKey, $result);
        }
        
        return $result;
    }

    public function getDevice(string $serial, bool $useCache = true): array
    {
        $cacheKey = 'device_' . md5($serial);
        
        // Try to get from cache first
        if ($useCache) {
            $cached = $this->getCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $projection = $this->getProjectionFields();
        
        // Robust query: search in ID, SerialNumber, or PPPoE User using case-insensitive regex
        $query = json_encode([
            '$or' => [
                ['_id' => ['$regex' => $serial, '$options' => 'i']],
                ['_deviceId._SerialNumber' => ['$regex' => $serial, '$options' => 'i']],
                ['VirtualParameters.pppoeUsername' => ['$regex' => $serial, '$options' => 'i']]
            ]
        ]);
        
        $result = $this->request('GET', '/devices', ['query' => $query, 'projection' => $projection]);
        $device = $result['body'][0] ?? [];
        
        // Cache the result
        if (!empty($device)) {
            $this->setCache($cacheKey, $device);
        }
        
        return $device;
    }

    /**
     * Get device by PPPoE Username
     * More reliable method specifically for PPPoE username matching
     */
    public function getDeviceByPppoeUsername(string $pppoeUsername): ?array
    {
        if (empty($pppoeUsername)) {
            return null;
        }
        
        try {
            // Get all devices (without cache for fresh data)
            $result = $this->getDevices(false);
            
            if ($result['code'] !== 200 || empty($result['body'])) {
                return null;
            }
            
            // Search through all devices
            foreach ($result['body'] as $device) {
                // Try multiple common PPPoE username parameter paths
                $pppoeUserPaths = [
                    'VirtualParameters.pppoeUsername',
                    'VirtualParameters.pppoeUsername2',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.3.Username',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.7.Username',
                ];
                
                foreach ($pppoeUserPaths as $path) {
                    $value = $this->getParameterValue($device, $path);
                    
                    // Match found (case-insensitive, trimmed)
                    if (!empty($value) && strcasecmp(trim($value), trim($pppoeUsername)) === 0) {
                        // Add flattened parameters for easier access
                        $device['flatParams'] = $this->flattenParameters($device);
                        return $device;
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            log_message('error', 'GenieACS getDeviceByPppoeUsername error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get parameter value from nested GenieACS structure
     * Handles both flat parameters and nested _value structure
     */
    private function getParameterValue(array $device, string $path): ?string
    {
        $parts = explode('.', $path);
        $current = $device;
        
        // Navigate through nested structure
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }
        
        // Check if it's a nested parameter with _value
        if (is_array($current) && isset($current['_value'])) {
            return (string)$current['_value'];
        }
        
        // Direct value
        if (is_string($current) || is_numeric($current)) {
            return (string)$current;
        }
        
        return null;
    }

    /**
     * Flatten nested GenieACS parameters for easier access
     * Converts nested structure to flat array with dot notation
     */
    private function flattenParameters(array $device): array
    {
        $flat = [];
        
        // Helper recursive function
        $flatten = function($data, $prefix = '') use (&$flatten, &$flat) {
            foreach ($data as $key => $value) {
                $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
                
                if (is_array($value)) {
                    // If has _value, extract it
                    if (isset($value['_value'])) {
                        $flat[$fullKey] = $value['_value'];
                    }
                    // Recurse for nested objects (but skip metadata keys)
                    if (!in_array($key, ['_object', '_writable', '_timestamp', '_type'])) {
                        $flatten($value, $fullKey);
                    }
                } else {
                    $flat[$fullKey] = $value;
                }
            }
        };
        
        // Flatten InternetGatewayDevice and VirtualParameters
        if (isset($device['InternetGatewayDevice'])) {
            $flatten($device['InternetGatewayDevice'], 'InternetGatewayDevice');
        }
        if (isset($device['VirtualParameters'])) {
            $flatten($device['VirtualParameters'], 'VirtualParameters');
        }
        
        return $flat;
    }

    /**
     * Clear all cache
     */
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get data from cache
     */
    private function getCache(string $key): ?array
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }

    /**
     * Set data to cache
     */
    private function setCache(string $key, array $value): void
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        $data = [
            'expires' => time() + $this->cacheExpiry,
            'value' => $value
        ];
        file_put_contents($file, json_encode($data));
    }

    private function getProjectionFields(): string 
    {
        return implode(',', [
            '_deviceId._SerialNumber',
            '_deviceId._ProductClass',
            '_registered',
            '_lastInform',
            // VirtualParameters
            'VirtualParameters.pppoeUsername',
            'VirtualParameters.pppoeUsername2',
            'VirtualParameters.gettemp',
            'VirtualParameters.RXPower',
            'VirtualParameters.pppoeIP',
            'VirtualParameters.IPTR069',
            'VirtualParameters.pppoeMac',
            'VirtualParameters.getponmode',
            'VirtualParameters.PonMac',
            'VirtualParameters.getSerialNumber',
            'VirtualParameters.activedevices',
            'VirtualParameters.useraktif',
            // WiFi
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase',
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
            // Device Info
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
            // WAN/PPPoE (backup paths)
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username',
            // Tags
            'tags'
        ]);
    }

    /**
     * Update SSID and Wi‑Fi password on a device via GenieACS.
     *
     * @param string $serial   Device serial number (as used by GenieACS)
     * @param string $ssid     New SSID value
     * @param string $password New Wi‑Fi password
     * @return array           Same format as other request methods (code, body)
     */
    public function setWifi(string $serial, string $ssid, string $password): array
    {
        // Clear device cache when updating
        $cacheFile = $this->cacheDir . md5('device_' . md5($serial)) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        // Use setParameterValues task which is standard TR-069
        $task = [
            'name' => 'setParameterValues',
            'parameterValues' => [
                ['name' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', 'value' => $ssid],
                ['name' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey', 'value' => $password],
            ]
        ];
        
        // Add 'connection_request' query param to trigger immediate contact
        return $this->request('POST', "/devices/{$serial}/tasks?connection_request", $task);
    }

    /**
     * Reboot a device via GenieACS.
     *
     * @param string $serial Device serial number
     * @return array
     */
    public function rebootDevice(string $serial): array
    {
        // "reboot" task. 'connection_request' param triggers immediate connection request to device
        return $this->request('POST', "/devices/{$serial}/tasks?connection_request", ['name' => 'reboot']);
    }

    /**
     * Set a single parameter on a device
     *
     * @param string $serial Device serial number or ID
     * @param string $parameter Parameter path (e.g., 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID')
     * @param mixed $value New value for the parameter
     * @return array
     */
    public function setParameter(string $serial, string $parameter, $value): array
    {
        // Clear device cache when updating
        $cacheFile = $this->cacheDir . md5('device_' . md5($serial)) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        // URL encode the device ID
        $encodedSerial = urlencode($serial);
        
        log_message('info', "GenieACS setParameter: device={$serial}, param={$parameter}, value={$value}");
        log_message('info', "GenieACS URL encoded device: {$encodedSerial}");
        
        // GenieACS task format - EXACT format from documentation
        // See: https://docs.genieacs.com/en/latest/api-reference.html#setparametervalues
        $task = [
            'name' => 'setParameterValues',
            'parameterValues' => [
                [$parameter, $value]
            ]
        ];
        
        log_message('info', "GenieACS task payload: " . json_encode($task));
        
        // Send task directly - GenieACS will return error if device not found
        $result = $this->request('POST', "/devices/{$encodedSerial}/tasks?timeout=3000&connection_request", $task);
        
        log_message('info', "GenieACS setParameter result: code={$result['code']}, error=" . ($result['error'] ?? 'none'));
        
        return $result;
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        
        // For GET requests, append query params
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $ch = curl_init($url);
        $options = [
            CURLOPT_HTTPHEADER       => [$this->authHeader, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER   => true,
            CURLOPT_CUSTOMREQUEST    => $method,
            CURLOPT_TIMEOUT          => 30, // 30 second timeout (increased from 10)
            CURLOPT_CONNECTTIMEOUT   => 10, // 10 second connection timeout (increased from 5)
            CURLOPT_SSL_VERIFYPEER   => false, // For self-signed SSL
            CURLOPT_SSL_VERIFYHOST   => false,
        ];
        
        if ($method !== 'GET' && !empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            log_message('error', "GenieACS request failed: URL={$url}, Error={$error}");
            return ['code' => 0, 'body' => null, 'error' => $error];
        }
        
        return ['code' => $code, 'body' => json_decode($response, true)];
    }
}

