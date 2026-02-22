<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// OneSignal credentials - loaded from .env for security, with fallbacks
$config['onesignal_app_id'] = getenv('ONESIGNAL_APP_ID') ?: '24f9ac65-3d89-40dd-a9d1-536b34eb78d3';
$config['onesignal_api_key'] = getenv('ONESIGNAL_API_KEY') ?: 'NjkyOTRiZjctOTk1OS00MjZlLTk2NjgtNDA0YTBkOWViMTQ3';
