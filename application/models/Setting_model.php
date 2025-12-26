<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setting_model - System Settings Management
 */
class Setting_model extends MY_Model {
    
    protected $table = 'system_settings';
    protected $primary_key = 'id';
    
    /**
     * Get All Settings as Key-Value Array
     */
    public function get_all_settings() {
        $query = $this->db->get($this->table);
        $settings = [];
        
        foreach ($query->result() as $row) {
            $value = $row->setting_value;
            
            // Type casting
            switch ($row->setting_type) {
                case 'number':
                    $value = (float) $value;
                    break;
                case 'boolean':
                    $value = ($value === 'true' || $value === '1');
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $settings[$row->setting_key] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Get Single Setting
     */
    public function get_setting($key, $default = null) {
        $row = $this->db->where('setting_key', $key)->get($this->table)->row();
        
        if (!$row) {
            return $default;
        }
        
        $value = $row->setting_value;
        
        switch ($row->setting_type) {
            case 'number':
                return (float) $value;
            case 'boolean':
                return ($value === 'true' || $value === '1');
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    /**
     * Update Setting
     */
    public function update_setting($key, $value) {
        $exists = $this->db->where('setting_key', $key)->count_all_results($this->table);
        
        if ($exists) {
            return $this->db->where('setting_key', $key)
                            ->update($this->table, ['setting_value' => $value]);
        }
        
        return $this->db->insert($this->table, [
            'setting_key' => $key,
            'setting_value' => $value
        ]);
    }
    
    /**
     * Bulk Update Settings
     */
    public function update_settings($settings) {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            $this->update_setting($key, $value);
        }
        
        return true;
    }
}
