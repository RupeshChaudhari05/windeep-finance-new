<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends CI_Controller {

	public function view($page = 'home')
	{
        if ( ! file_exists(APPPATH.'views/public/static/'.$page.'.php'))
        {
                // Whoops, we don't have a page for that!
                show_404();
        }

        $data['title'] = ucfirst(str_replace('_', ' ', $page)) . ' - Windeep Finance - Enterprise Financial Solutions';

        $this->load->view('public/static/includes/header', $data);
        $this->load->view('public/static/'.$page, $data);
        $this->load->view('public/static/includes/footer', $data);
	}
}
