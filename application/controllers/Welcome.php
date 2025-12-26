<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
		// Redirect to admin login if not logged in
		redirect('admin/auth');
	}
}
