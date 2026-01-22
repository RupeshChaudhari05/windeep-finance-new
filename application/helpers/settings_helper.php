<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Settings Helper
 * 
 * Global helper functions for accessing system settings
 */

if (!function_exists('get_setting')) {
    /**
     * Get a setting value from the database
     *
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    function get_setting($key, $default = null) {
        $CI =& get_instance();
        
        // Check if settings are already loaded in controller
        if (isset($CI->settings) && isset($CI->settings[$key])) {
            return $CI->settings[$key];
        }
        
        // Try to get from Setting_model
        if (!isset($CI->Setting_model)) {
            $CI->load->model('Setting_model');
        }
        
        return $CI->Setting_model->get_setting($key, $default);
    }
}

if (!function_exists('set_setting')) {
    /**
     * Update a setting value in the database
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success
     */
    function set_setting($key, $value) {
        $CI =& get_instance();
        
        if (!isset($CI->Setting_model)) {
            $CI->load->model('Setting_model');
        }
        
        return $CI->Setting_model->update_setting($key, $value);
    }
}

if (!function_exists('get_company_name')) {
    /**
     * Get company name (shorthand)
     */
    function get_company_name() {
        return get_setting('company_name', 'Windeep Finance');
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * Get currency symbol (shorthand)
     */
    function get_currency_symbol() {
        return get_setting('currency_symbol', '₹');
    }
}

if (!function_exists('format_amount')) {
    /**
     * Format amount with currency symbol
     */
    function format_amount($amount, $decimals = 2) {
        $symbol = get_currency_symbol();
        return $symbol . number_format((float)$amount, $decimals);
    }
}

if (!function_exists('is_feature_enabled')) {
    /**
     * Check if a feature is enabled
     */
    function is_feature_enabled($feature) {
        $value = get_setting($feature . '_enabled', '0');
        return ($value === '1' || $value === 'true' || $value === true);
    }
}
