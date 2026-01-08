<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Errors Controller - Error Pages
 */
class Errors extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form']);
    }

    /**
     * 404 Page Not Found
     */
    public function page_missing() {
        $data['title'] = 'Page Not Found';
        $data['page_title'] = '404 - Page Not Found';

        // Set HTTP status to 404
        $this->output->set_status_header(404);

        // Load admin layout for consistent look
        $this->load->view('admin/layouts/header', $data);
        $this->load->view('admin/layouts/sidebar', $data);
        $this->load->view('errors/404', $data);
        $this->load->view('admin/layouts/footer', $data);
    }
}