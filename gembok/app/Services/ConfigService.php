<?php
namespace App\Services;

use App\Models\SettingModel;

class ConfigService
{
    /**
     * Get a configuration value. Always reads fresh from database.
     * Falls back to .env if not stored in DB.
     */
    public function get(string $key, $default = null)
    {
        // Always create fresh model instance to avoid caching issues
        $model = new SettingModel();
        $row = $model->find($key);
        
        if ($row) {
            return $row['value'];
        }
        
        // Fallback to environment variable
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }
        
        // Check $_ENV superglobal
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        return $default;
    }

    /** 
     * Save or update a configuration value in database
     */
    public function set(string $key, $value): bool
    {
        $model = new SettingModel();
        
        // Check if key exists
        $existing = $model->find($key);
        
        if ($existing) {
            // Update existing
            return $model->update($key, ['value' => $value]);
        } else {
            // Insert new
            return $model->insert(['key' => $key, 'value' => $value]);
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
        $model = new SettingModel();
        return $model->delete($key);
    }
}
?>
