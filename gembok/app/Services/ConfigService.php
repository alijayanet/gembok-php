<?php
namespace App\Services;

class ConfigService
{
    /**
     * Get a configuration value.
     * 
     * PRIORITY ORDER (UPDATED for Web Admin Editability):
     * 1. Database (settings table) - Editable via web admin
     * 2. Environment variables (.env file) - Fallback/default values
     * 
     * This allows admins to override .env values via web interface.
     * If a setting is saved via admin panel, it takes precedence.
     */
    public function get(string $key, $default = null)
    {
        // PRIORITY 1: Try database first (for web-editable settings)
        try {
            $db = \Config\Database::connect();
            $row = $db->table('settings')->where('key', $key)->get()->getRowArray();
            
            // If found in database and has a non-empty value, use it
            if ($row && isset($row['value']) && $row['value'] !== '' && $row['value'] !== null) {
                return $row['value'];
            }
        } catch (\Exception $e) {
            // Database not available or table doesn't exist, continue to fallback
            log_message('debug', 'ConfigService: Database read failed for key "' . $key . '", falling back to .env');
        }
        
        // PRIORITY 2: Fallback to environment variable (.env file)
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }
        
        // Check $_ENV superglobal
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        
        // Try to read from .env file directly (last resort)
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            if (preg_match('/' . preg_quote($key, '/') . '\s*=\s*(.+)/', $content, $matches)) {
                $value = trim($matches[1], '"\'');
                if ($value !== '') {
                    return $value;
                }
            }
        }
        
        return $default;
    }

    /** 
     * Save or update a configuration value in database
     */
    public function set(string $key, $value): bool
    {
        try {
            // Use direct database query for better compatibility
            $db = \Config\Database::connect();
            
            // Check if key exists
            $existing = $db->table('settings')->where('key', $key)->get()->getRowArray();
            
            if ($existing) {
                // Update existing using direct query
                return $db->table('settings')
                    ->where('key', $key)
                    ->update(['value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                // Insert new
                return $db->table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            // Log error for debugging
            log_message('error', 'ConfigService::set() failed for key "' . $key . '": ' . $e->getMessage());
            return false;
        }
    }

    /** 
     * Return an associative array of requested keys 
     */
    public function getAll(array $keys): array
    {
        $result = [];
        foreach ($keys as $k) {
            $result[$k] = $this->get($k);
        }
        return $result;
    }

    /**
     * Delete a configuration value
     */
    public function delete(string $key): bool
    {
        try {
            $db = \Config\Database::connect();
            return $db->table('settings')->where('key', $key)->delete();
        } catch (\Exception $e) {
            log_message('error', 'ConfigService::delete() failed: ' . $e->getMessage());
            return false;
        }
    }
}
