<?php
namespace App\Services;

use App\Services\ConfigService;

/**
 * MikroTik Service - Lightweight Version
 * Uses simple socket-based API communication to avoid memory issues
 */
class MikrotikService
{
    private ConfigService $config;
    private bool $connected = false;
    private string $lastError = '';
    private $socket = null;
    private array $connectionConfig = [];
    
    // Static flag to prevent repeated connection attempts in same request
    private static bool $connectionFailed = false;

    public function __construct()
    {
        $this->config = new ConfigService();
        
        // Load config from database/env
        $host = $this->config->get('MIKROTIK_HOST');
        $user = $this->config->get('MIKROTIK_USER');
        $pass = $this->config->get('MIKROTIK_PASS');
        $port = (int)($this->config->get('MIKROTIK_PORT') ?: 8728);
        
        // Validate configuration
        if (empty($host) || empty($user)) {
            $this->lastError = 'MikroTik tidak dikonfigurasi. Silakan set di halaman Settings.';
            return;
        }
        
        $this->connectionConfig = [
            'host' => $host,
            'user' => $user,
            'pass' => $pass ?? '',
            'port' => $port
        ];
    }

    /**
     * Connect to MikroTik using socket
     */
    private function connect(): bool
    {
        if ($this->connected) {
            return true;
        }
        
        // Skip if already failed in this request
        if (self::$connectionFailed) {
            return false;
        }
        
        if (empty($this->connectionConfig)) {
            return false;
        }
        
        $host = $this->connectionConfig['host'];
        $port = $this->connectionConfig['port'];
        
        // Open socket connection with short timeout
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 2);
        
        if (!$this->socket) {
            $this->lastError = "Tidak dapat terhubung ke {$host}:{$port} - {$errstr}";
            self::$connectionFailed = true;
            return false;
        }
        
        // Set socket timeout for read/write
        stream_set_timeout($this->socket, 2);
        
        // Try to login
        if (!$this->login()) {
            @fclose($this->socket);
            $this->socket = null;
            self::$connectionFailed = true;
            return false;
        }
        
        $this->connected = true;
        return true;
    }

    /**
     * Login to MikroTik API
     */
    private function login(): bool
    {
        $user = $this->connectionConfig['user'];
        $pass = $this->connectionConfig['pass'];
        
        // Send login command (RouterOS 6.43+)
        $this->writeWord('/login');
        $this->writeWord('=name=' . $user);
        $this->writeWord('=password=' . $pass);
        $this->writeWord('');
        
        // Read response
        $response = $this->readResponse();
        
        if (isset($response[0]) && $response[0] === '!done') {
            return true;
        }
        
        // Check for error message
        if (isset($response[0]) && $response[0] === '!trap') {
            $this->lastError = 'Login gagal: ' . ($response['message'] ?? 'Invalid credentials');
            return false;
        }
        
        $this->lastError = 'Login gagal: Unknown response';
        return false;
    }

    /**
     * Write word to socket (MikroTik API protocol)
     */
    private function writeWord(string $word): void
    {
        $len = strlen($word);
        
        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            fwrite($this->socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            fwrite($this->socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }
        
        if ($len > 0) {
            fwrite($this->socket, $word);
        }
    }

    /**
     * Read word from socket
     */
    private function readWord(): ?string
    {
        $byte = fread($this->socket, 1);
        if ($byte === false || $byte === '') {
            return null;
        }
        
        $len = ord($byte);
        
        if (($len & 0x80) === 0x00) {
            // 1 byte length
        } elseif (($len & 0xC0) === 0x80) {
            $len = (($len & ~0xC0) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xE0) === 0xC0) {
            $len = (($len & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xF0) === 0xE0) {
            $len = (($len & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        
        if ($len === 0) {
            return '';
        }
        
        $word = '';
        while (strlen($word) < $len) {
            $chunk = fread($this->socket, $len - strlen($word));
            if ($chunk === false) break;
            $word .= $chunk;
        }
        
        return $word;
    }

    /**
     * Read full response from socket
     */
    private function readResponse(): array
    {
        $response = [];
        $record = [];
        
        while (true) {
            $word = $this->readWord();
            
            if ($word === null || $word === '') {
                // End of sentence
                if (!empty($record)) {
                    $response[] = $record;
                    $record = [];
                }
                
                // Check if we got !done or !trap
                if (isset($response[0][0])) {
                    $status = $response[0][0];
                    if ($status === '!done' || $status === '!trap' || $status === '!fatal') {
                        break;
                    }
                }
                
                // Also break if first word of response indicates completion
                if (isset($response[0]) && is_string($response[0]) && in_array($response[0], ['!done', '!trap', '!fatal'])) {
                    break;
                }
                
                // Prevent infinite loop
                $info = stream_get_meta_data($this->socket);
                if ($info['timed_out']) {
                    break;
                }
                
                continue;
            }
            
            if ($word[0] === '!') {
                $response[] = $word;
            } elseif (strpos($word, '=') === 0) {
                // Attribute
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2) {
                    $record[$parts[0]] = $parts[1];
                }
            }
        }
        
        if (!empty($record)) {
            $response[] = $record;
        }
        
        return $response;
    }

    /**
     * Execute command and get response
     */
    public function query(string $command, array $params = []): array
    {
        if (!$this->connect()) {
            return [];
        }
        
        // Send command
        $this->writeWord($command);
        foreach ($params as $key => $value) {
            $this->writeWord('=' . $key . '=' . $value);
        }
        $this->writeWord('');
        
        // Read response
        $response = $this->readResponse();
        
        // Parse response into array of records
        $result = [];
        foreach ($response as $item) {
            if (is_array($item)) {
                $result[] = $item;
            }
        }
        
        return $result;
    }

    /**
     * Destructor - close socket
     */
    public function __destruct()
    {
        if ($this->socket) {
            @fclose($this->socket);
        }
    }

    // ==========================================
    // PUBLIC API METHODS
    // ==========================================

    public function isConnected(): bool
    {
        return $this->connect();
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getActivePppoe(): array
    {
        return $this->query('/ppp/active/print', [
            '.proplist' => 'name,address,uptime,service'
        ]);
    }

    public function getPppoeSecrets(): array
    {
        return $this->query('/ppp/secret/print');
    }

    public function getPppoeProfiles(): array
    {
        return $this->query('/ppp/profile/print');
    }

    public function getHotspotUsers(): array
    {
        return $this->query('/ip/hotspot/user/print');
    }

    public function getHotspotProfiles(): array
    {
        return $this->query('/ip/hotspot/user/profile/print', [
            '.proplist' => 'name'
        ]);
    }

    public function getActiveHotspotUsers(): array
    {
        return $this->query('/ip/hotspot/active/print', [
            '.proplist' => 'user,address,uptime'
        ]);
    }

    public function getPppoeActiveCount(): int
    {
        $active = $this->getPppoeActive();
        return count($active);
    }

    public function setPppoeUserProfile(string $username, string $profile): bool
    {
        // Find user ID first
        $users = $this->query('/ppp/secret/print', ['?name' => $username]);
        if (empty($users)) return false;
        
        $id = $users[0]['.id'];
        
        // Disable first to cut connection if any
        $this->query('/ppp/secret/set', [
            '.id' => $id,
            'profile' => $profile,
            'disabled' => 'false' // Ensure enabled
        ]);
        
        // Kick active connection to apply profile change
        $this->kickPppoeUser($username);
        
        return true;
    }

    public function kickPppoeUser(string $username): void
    {
        $active = $this->query('/ppp/active/print', ['?name' => $username]);
        if (!empty($active)) {
            $id = $active[0]['.id'];
            $this->query('/ppp/active/remove', ['.id' => $id]);
        }
    }

    public function enablePppoeSecret(string $username): bool
    {
        $users = $this->query('/ppp/secret/print', ['?name' => $username]);
        if (empty($users)) return false;
        
        $this->query('/ppp/secret/enable', ['.id' => $users[0]['.id']]);
        return true;
    }

    public function disablePppoeSecret(string $username): bool
    {
        $users = $this->query('/ppp/secret/print', ['?name' => $username]);
        if (empty($users)) return false;
        
        $this->query('/ppp/secret/disable', ['.id' => $users[0]['.id']]);
        // Also kick active connection
        $this->kickPppoeUser($username);
        return true;
    }

    // ==========================================
    // PPPoE SECRET MANAGEMENT (CRUD)
    // ==========================================

    /**
     * Add new PPPoE user
     */
    public function addPppoeSecret(string $username, string $password, string $profile = 'default', string $service = 'pppoe'): bool
    {
        if (empty($username) || empty($password)) {
            $this->lastError = 'Username dan password wajib diisi';
            return false;
        }

        try {
            $this->query('/ppp/secret/add', [
                'name' => $username,
                'password' => $password,
                'profile' => $profile,
                'service' => $service
            ]);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update PPPoE user (password and/or profile)
     */
    public function updatePppoeSecret(string $username, array $data): bool
    {
        $users = $this->query('/ppp/secret/print', ['?name' => $username]);
        if (empty($users)) {
            $this->lastError = 'User tidak ditemukan';
            return false;
        }

        $id = $users[0]['.id'];
        $params = ['.id' => $id];

        // Only update provided fields
        if (isset($data['password']) && !empty($data['password'])) {
            $params['password'] = $data['password'];
        }
        if (isset($data['profile'])) {
            $params['profile'] = $data['profile'];
        }
        if (isset($data['service'])) {
            $params['service'] = $data['service'];
        }

        try {
            $this->query('/ppp/secret/set', $params);
            
            // If profile changed, kick user to apply new profile
            if (isset($data['profile'])) {
                $this->kickPppoeUser($username);
            }
            
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete PPPoE user
     */
    public function deletePppoeSecret(string $username): bool
    {
        $users = $this->query('/ppp/secret/print', ['?name' => $username]);
        if (empty($users)) {
            $this->lastError = 'User tidak ditemukan';
            return false;
        }

        try {
            // Kick active connection first
            $this->kickPppoeUser($username);
            
            // Then delete the secret
            $this->query('/ppp/secret/remove', ['.id' => $users[0]['.id']]);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // HOTSPOT USER MANAGEMENT (CRUD)
    // ==========================================

    /**
     * Add Hotspot user
     * Automatically adds comment: 'vc-username-gembok' for vouchers, 'up-username-gembok' for permanent users
     */
    public function addHotspotUser(string $username, string $password, string $profile = 'default', string $limitUptime = ''): bool
    {
        if (empty($username) || empty($password)) {
            $this->lastError = 'Username dan password wajib diisi';
            return false;
        }

        try {
            $params = [
                'name' => $username,
                'password' => $password,
                'profile' => $profile
            ];

            // Add limit-uptime if provided
            if (!empty($limitUptime)) {
                $params['limit-uptime'] = $limitUptime;
                // Add comment with 'vc' prefix for vouchers + gembok tag
                $params['comment'] = 'vc-' . $username . '-gembok';
            } else {
                // Add comment with 'up' prefix for permanent users + gembok tag
                $params['comment'] = 'up-' . $username . '-gembok';
            }

            $this->query('/ip/hotspot/user/add', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update Hotspot user
     * Updates comment based on limit-uptime presence (includes gembok tag)
     */
    public function updateHotspotUser(string $username, array $data): bool
    {
        $users = $this->query('/ip/hotspot/user/print', ['?name' => $username]);
        if (empty($users)) {
            $this->lastError = 'User tidak ditemukan';
            return false;
        }

        $id = $users[0]['.id'];
        $params = ['.id' => $id];

        if (isset($data['password']) && !empty($data['password'])) {
            $params['password'] = $data['password'];
        }
        if (isset($data['profile'])) {
            $params['profile'] = $data['profile'];
        }
        if (isset($data['limit_uptime'])) {
            $params['limit-uptime'] = $data['limit_uptime'];
            // Update comment: 'vc' if has limit-uptime, 'up' if empty/removed (with gembok tag)
            if (!empty($data['limit_uptime'])) {
                $params['comment'] = 'vc-' . $username . '-gembok';
            } else {
                $params['comment'] = 'up-' . $username . '-gembok';
            }
        }

        try {
            $this->query('/ip/hotspot/user/set', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete Hotspot user
     */
    public function deleteHotspotUser(string $username): bool
    {
        $users = $this->query('/ip/hotspot/user/print', ['?name' => $username]);
        if (empty($users)) {
            $this->lastError = 'User tidak ditemukan';
            return false;
        }

        try {
            // Kick active session first if exists
            $active = $this->query('/ip/hotspot/active/print', ['?user' => $username]);
            if (!empty($active)) {
                $this->query('/ip/hotspot/active/remove', ['.id' => $active[0]['.id']]);
            }
            
            // Delete user
            $this->query('/ip/hotspot/user/remove', ['.id' => $users[0]['.id']]);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // PPPoE PROFILE MANAGEMENT
    // ==========================================

    /**
     * Add PPPoE Profile
     */
    public function addPppoeProfile(string $name, string $rateLimit = '', string $localAddress = '', string $remoteAddress = ''): bool
    {
        if (empty($name)) {
            $this->lastError = 'Nama profile wajib diisi';
            return false;
        }

        try {
            $params = ['name' => $name];
            
            if (!empty($rateLimit)) {
                $params['rate-limit'] = $rateLimit;
            }
            if (!empty($localAddress)) {
                $params['local-address'] = $localAddress;
            }
            if (!empty($remoteAddress)) {
                $params['remote-address'] = $remoteAddress;
            }

            $this->query('/ppp/profile/add', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update PPPoE Profile
     */
    public function updatePppoeProfile(string $name, array $data): bool
    {
        $profiles = $this->query('/ppp/profile/print', ['?name' => $name]);
        if (empty($profiles)) {
            $this->lastError = 'Profile tidak ditemukan';
            return false;
        }

        $id = $profiles[0]['.id'];
        $params = ['.id' => $id];

        if (isset($data['name'])) {
            $params['name'] = $data['name'];
        }
        if (isset($data['rate_limit'])) {
            $params['rate-limit'] = $data['rate_limit'];
        }
        if (isset($data['local_address'])) {
            $params['local-address'] = $data['local_address'];
        }
        if (isset($data['remote_address'])) {
            $params['remote-address'] = $data['remote_address'];
        }

        try {
            $this->query('/ppp/profile/set', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete PPPoE Profile
     */
    public function deletePppoeProfile(string $name): bool
    {
        // Don't allow deleting default profiles
        if (in_array($name, ['default', 'default-encryption'])) {
            $this->lastError = 'Profile default tidak dapat dihapus';
            return false;
        }

        $profiles = $this->query('/ppp/profile/print', ['?name' => $name]);
        if (empty($profiles)) {
            $this->lastError = 'Profile tidak ditemukan';
            return false;
        }

        try {
            $this->query('/ppp/profile/remove', ['.id' => $profiles[0]['.id']]);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ==========================================
    // HOTSPOT PROFILE MANAGEMENT
    // ==========================================

    /**
     * Add Hotspot Profile
     */
    public function addHotspotProfile(string $name, int $sharedUsers = 1, string $rateLimit = ''): bool
    {
        if (empty($name)) {
            $this->lastError = 'Nama profile wajib diisi';
            return false;
        }

        try {
            $params = [
                'name' => $name,
                'shared-users' => (string)$sharedUsers
            ];

            if (!empty($rateLimit)) {
                $params['rate-limit'] = $rateLimit;
            }

            $this->query('/ip/hotspot/user/profile/add', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update Hotspot Profile
     */
    public function updateHotspotProfile(string $name, array $data): bool
    {
        $profiles = $this->query('/ip/hotspot/user/profile/print', ['?name' => $name]);
        if (empty($profiles)) {
            $this->lastError = 'Profile tidak ditemukan';
            return false;
        }

        $id = $profiles[0]['.id'];
        $params = ['.id' => $id];

        if (isset($data['name'])) {
            $params['name'] = $data['name'];
        }
        if (isset($data['shared_users'])) {
            $params['shared-users'] = (string)$data['shared_users'];
        }
        if (isset($data['rate_limit'])) {
            $params['rate-limit'] = $data['rate_limit'];
        }

        try {
            $this->query('/ip/hotspot/user/profile/set', $params);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete Hotspot Profile
     */
    public function deleteHotspotProfile(string $name): bool
    {
        // Don't allow deleting default profile
        if ($name === 'default') {
            $this->lastError = 'Profile default tidak dapat dihapus';
            return false;
        }

        $profiles = $this->query('/ip/hotspot/user/profile/print', ['?name' => $name]);
        if (empty($profiles)) {
            $this->lastError = 'Profile tidak ditemukan';
            return false;
        }

        try {
            $this->query('/ip/hotspot/user/profile/remove', ['.id' => $profiles[0]['.id']]);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Enable PPPoE user
     */
    public function enablePppoe(string $username): bool
    {
        return $this->enablePppoeSecret($username);
    }

    /**
     * Disable PPPoE user
     */
    public function disablePppoe(string $username): bool
    {
        return $this->disablePppoeSecret($username);
    }
    
    /**
     * Get Hotspot Profile Info (price, duration)
     * Supports:
     * 1. Comment format: "HARGA:5000|DURASI:1jam"
     * 2. On-Login script format (Mikhmon): :put (",rem,2002,1d,3000,,Disable,");
     */
    public function getHotspotProfileInfo(string $profileName): ?array
    {
        if (!$this->connect()) {
            return null;
        }
        
        try {
            // Get hotspot user profile details
            $this->writeWord('/ip/hotspot/user/profile/print');
            $this->writeWord('?name=' . $profileName);
            $this->writeWord('');
            
            $response = $this->readResponse();
            
            if (empty($response)) {
                return null;
            }
            
            $profile = $response[0] ?? null;
            
            if (!$profile) {
                return null;
            }
            
            $info = [];
            
            // 1. Try Extract from Comment
            if (isset($profile['comment'])) {
                $comment = $profile['comment'];
                if (preg_match('/HARGA:(\d+)/', $comment, $matches)) {
                    $info['price'] = (int)$matches[1];
                }
                if (preg_match('/DURASI:([^|]+)/', $comment, $matches)) {
                    $info['duration'] = trim($matches[1]);
                }
            }
            
            // 2. Try Extract from On-Login Script (Mikhmon format)
            // 2. Try Extract from On-Login Script (Mikhmon format)
            if ((empty($info['price']) || empty($info['duration'])) && isset($profile['on-login'])) {
                $script = $profile['on-login'];
                
                // NUCLEAR OPTION: Remove ALL whitespace (newlines, spaces, tabs)
                $cleanScript = preg_replace('/\s+/', '', $script);
                
                // Cari marker Mikhmon: ,rem, (WITHOUT QUOTE checking first)
                // Format matches: ...",rem,BATCH,DURATION,PRICE,...
                $marker = ',rem,';
                $startPos = strpos($cleanScript, $marker);
                
                if ($startPos !== false) {
                    // Extract after ,rem,
                    $sub = substr($cleanScript, $startPos + strlen($marker)); 
                    
                    // Explode by comma
                    $parts = explode(',', $sub);
                    
                    // parts[0] = BATCH (e.g. 2002)
                    // parts[1] = DURATION (e.g. 1d)
                    // parts[2] = PRICE (e.g. 3000)
                    
                    if (count($parts) >= 3) {
                        // Duration
                        if (empty($info['duration']) && !empty($parts[1])) {
                            $info['duration'] = $parts[1]; 
                        }
                        
                        // Price
                        if (empty($info['price']) && !empty($parts[2]) && is_numeric($parts[2])) {
                            $info['price'] = (int)$parts[2];
                        }
                    }
                }
            }
            
            return !empty($info) ? $info : null;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
}
