<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['key', 'value', 'description'];
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /**
     * Get setting value by key
     */
    public function getValue($key, $default = null)
    {
        $setting = $this->where('key', $key)->first();
        return $setting ? $setting['value'] : $default;
    }

    /**
     * Set setting value
     */
    public function setValue($key, $value, $description = '')
    {
        $existing = $this->where('key', $key)->first();
        
        if ($existing) {
            return $this->update($existing['id'], ['value' => $value]);
        } else {
            return $this->insert([
                'key' => $key,
                'value' => $value,
                'description' => $description
            ]);
        }
    }

    /**
     * Get all settings as key-value pairs
     */
    public function getAllSettings()
    {
        $settings = $this->findAll();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        return $result;
    }
}
