<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate_limiter Library
 * Prevents brute force attacks and API abuse
 */
class Rate_limiter {
    
    protected $CI;
    protected $cache_prefix = 'rate_limit_';
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));
    }
    
    /**
     * Check if rate limit exceeded
     * 
     * @param string $identifier - Unique identifier (IP, user ID, etc.)
     * @param string $action - Action being performed (login, api_call, etc.)
     * @param int $max_attempts - Maximum allowed attempts
     * @param int $window - Time window in seconds
     * @return array - ['allowed' => bool, 'remaining' => int, 'reset_at' => timestamp]
     */
    public function check($identifier, $action = 'default', $max_attempts = 5, $window = 300) {
        $key = $this->cache_prefix . $action . '_' . md5($identifier);
        
        // Get current attempts
        $data = $this->CI->cache->get($key);
        
        if (!$data) {
            $data = [
                'attempts' => 0,
                'first_attempt' => time(),
                'reset_at' => time() + $window
            ];
        }
        
        // Check if window has expired
        if (time() >= $data['reset_at']) {
            $data = [
                'attempts' => 0,
                'first_attempt' => time(),
                'reset_at' => time() + $window
            ];
        }
        
        $remaining = max(0, $max_attempts - $data['attempts']);
        $allowed = $data['attempts'] < $max_attempts;
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_at' => $data['reset_at'],
            'retry_after' => $allowed ? 0 : ($data['reset_at'] - time())
        ];
    }
    
    /**
     * Record an attempt
     * 
     * @param string $identifier
     * @param string $action
     * @param int $window
     * @return bool
     */
    public function record($identifier, $action = 'default', $window = 300) {
        $key = $this->cache_prefix . $action . '_' . md5($identifier);
        
        // Get current data
        $data = $this->CI->cache->get($key);
        
        if (!$data) {
            $data = [
                'attempts' => 0,
                'first_attempt' => time(),
                'reset_at' => time() + $window
            ];
        }
        
        // Check if window expired
        if (time() >= $data['reset_at']) {
            $data = [
                'attempts' => 1,
                'first_attempt' => time(),
                'reset_at' => time() + $window
            ];
        } else {
            $data['attempts']++;
        }
        
        // Save to cache
        return $this->CI->cache->save($key, $data, $window);
    }
    
    /**
     * Check if identifier is currently locked out
     * 
     * @param string $identifier
     * @param string $action
     * @param int $lockout_time
     * @return array - ['locked' => bool, 'unlock_at' => timestamp]
     */
    public function is_locked($identifier, $action = 'default', $lockout_time = 900) {
        $key = $this->cache_prefix . 'lockout_' . $action . '_' . md5($identifier);
        
        $lockout = $this->CI->cache->get($key);
        
        if (!$lockout) {
            return ['locked' => false, 'unlock_at' => 0];
        }
        
        if (time() >= $lockout['unlock_at']) {
            $this->CI->cache->delete($key);
            return ['locked' => false, 'unlock_at' => 0];
        }
        
        return [
            'locked' => true,
            'unlock_at' => $lockout['unlock_at'],
            'retry_after' => $lockout['unlock_at'] - time()
        ];
    }
    
    /**
     * Lock identifier after too many failed attempts
     * 
     * @param string $identifier
     * @param string $action
     * @param int $lockout_time - Lockout duration in seconds
     * @return bool
     */
    public function lock($identifier, $action = 'default', $lockout_time = 900) {
        $key = $this->cache_prefix . 'lockout_' . $action . '_' . md5($identifier);
        
        $lockout = [
            'locked_at' => time(),
            'unlock_at' => time() + $lockout_time
        ];
        
        // Also log the lockout
        log_message('warning', 'Rate limit lockout: ' . $action . ' for ' . $identifier);
        
        return $this->CI->cache->save($key, $lockout, $lockout_time);
    }
    
    /**
     * Reset rate limit for identifier
     * 
     * @param string $identifier
     * @param string $action
     * @return bool
     */
    public function reset($identifier, $action = 'default') {
        $key = $this->cache_prefix . $action . '_' . md5($identifier);
        $lockout_key = $this->cache_prefix . 'lockout_' . $action . '_' . md5($identifier);
        
        $this->CI->cache->delete($key);
        $this->CI->cache->delete($lockout_key);
        
        return true;
    }
    
    /**
     * Comprehensive rate limit check with lockout
     * 
     * @param string $identifier
     * @param string $action
     * @param array $config - ['max_attempts', 'window', 'lockout_time']
     * @return array - Status with details
     */
    public function check_and_record($identifier, $action = 'default', $config = []) {
        $max_attempts = $config['max_attempts'] ?? 5;
        $window = $config['window'] ?? 300;
        $lockout_time = $config['lockout_time'] ?? 900;
        
        // First check if locked out
        $lockout = $this->is_locked($identifier, $action, $lockout_time);
        if ($lockout['locked']) {
            return [
                'allowed' => false,
                'reason' => 'locked_out',
                'unlock_at' => $lockout['unlock_at'],
                'retry_after' => $lockout['retry_after']
            ];
        }
        
        // Check rate limit
        $status = $this->check($identifier, $action, $max_attempts, $window);
        
        if (!$status['allowed']) {
            // Lock the user
            $this->lock($identifier, $action, $lockout_time);
            
            return [
                'allowed' => false,
                'reason' => 'too_many_attempts',
                'max_attempts' => $max_attempts,
                'window' => $window,
                'locked_until' => time() + $lockout_time
            ];
        }
        
        // Record the attempt
        $this->record($identifier, $action, $window);
        
        return [
            'allowed' => true,
            'remaining' => $status['remaining'] - 1,
            'reset_at' => $status['reset_at']
        ];
    }
}
