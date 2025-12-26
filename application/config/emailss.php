<?php 

$config['protocol'] = 'smtp';
$config['smtp_host'] = 'your_smtp_host';
$config['smtp_user'] = 'your_smtp_user';
$config['smtp_pass'] = 'your_smtp_password';
$config['smtp_port'] = 587;
$config['mailtype'] = 'html';
$config['charset']  = 'utf-8';
$config['newline']  = "\r\n";

$this->email->initialize($config);