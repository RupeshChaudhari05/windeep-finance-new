<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
		// If admin already logged in, go to admin dashboard
		if ($this->session->userdata('admin_id')) {
			redirect('admin/dashboard');
		}
		// If member already logged in, go to member dashboard
		if ($this->session->userdata('member_logged_in')) {
			redirect('member/dashboard');
		}

		$data['title'] = 'Welcome to Windeep Finance';
		$this->load->view('public/landing', $data);
	}
}
