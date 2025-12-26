<?php
use OneSignal\Client;
use OneSignal\Devices\Notification;
//require 'vendor/autoload.php';
//print_r( __DIR__ . '/vendor/autoload.php');die;
//require_once __DIR__ . '/vendor/autoload.php';
require 'vendor/autoload.php';
defined('BASEPATH') OR exit('No direct script access allowed');

class OneSignal {

    private $client;
    private $app_id;
    private $api_key;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('onesignal');
        $this->app_id = $this->CI->config->item('onesignal_app_id');
        $this->api_key = $this->CI->config->item('onesignal_api_key');
        $this->client = new Client($this->app_id, $this->api_key);
    }

    public function send_notification($content, $headings = 'Notification', $image_url = null) {
        $notification_data = [
            'contents' => ['en' => $content],
            'included_segments' => ['All'],
            'headings' => ['en' => $headings],
        ];

        if ($image_url) {
            $notification_data['big_picture'] = $image_url; // For an image
        }

        $notification = new Notification($notification_data);

        try {
            $response = $this->client->notifications->create($notification);
            return $response;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
