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

    public function getPppoeActive(): array
    {
        return $this->query('/ppp/active/print');
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
        return $this->query('/ip/hotspot/user/profile/print');
    }

    public function getHotspotActive(): array
    {
        return $this->query('/ip/hotspot/active/print');
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
}
