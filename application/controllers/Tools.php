<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tools - Development utilities
 */
class Tools extends CI_Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Clear PHP opcode cache (opcache) if available. Only allowed from localhost in development.
     */
    public function clear_opcache() {
        // Only allow from localhost
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($remote, ['127.0.0.1', '::1'])) {
            show_error('Forbidden', 403);
            return;
        }

        // Only allow in development environment
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            show_error('Not allowed in this environment', 403);
            return;
        }

        $result = ['success' => false, 'message' => 'No opcache available'];

        if (function_exists('opcache_reset')) {
            opcache_reset();
            $result = ['success' => true, 'message' => 'Opcache cleared'];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
