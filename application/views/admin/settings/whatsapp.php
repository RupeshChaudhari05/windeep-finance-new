<!-- WhatsApp Settings Tab Content -->
<div class="card card-success card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-whatsapp mr-2"></i>WhatsApp Integration</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Setup Instructions:</strong> Configure your WhatsApp Business API credentials below. 
            You can use Meta Cloud API, Twilio, or a custom provider.
        </div>

        <form action="<?= base_url('admin/settings/save_whatsapp') ?>" method="POST" id="whatsappSettingsForm">
            <?= $this->security->get_csrf_token_name() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            Enable WhatsApp Notifications
                            <i class="fas fa-question-circle text-info" data-toggle="tooltip" title="Enable to send payment reminders and notifications via WhatsApp"></i>
                        </label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="whatsapp_enabled" name="whatsapp_enabled" value="1" <?= get_setting('whatsapp_enabled') == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="whatsapp_enabled">Enable WhatsApp</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            WhatsApp Provider
                            <i class="fas fa-question-circle text-info" data-toggle="tooltip" title="Choose your WhatsApp Business API provider"></i>
                        </label>
                        <select name="whatsapp_provider" class="form-control" id="whatsapp_provider">
                            <option value="meta" <?= get_setting('whatsapp_provider') == 'meta' ? 'selected' : '' ?>>Meta Cloud API (Official)</option>
                            <option value="twilio" <?= get_setting('whatsapp_provider') == 'twilio' ? 'selected' : '' ?>>Twilio</option>
                            <option value="custom" <?= get_setting('whatsapp_provider') == 'custom' ? 'selected' : '' ?>>Custom API</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Meta Cloud API Settings -->
            <div id="meta_settings" class="provider-settings">
                <h5 class="text-success mb-3"><i class="fab fa-facebook mr-2"></i>Meta Cloud API Settings</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Access Token</label>
                            <input type="password" name="whatsapp_api_key" class="form-control" 
                                   value="<?= get_setting('whatsapp_api_key') ?>" 
                                   placeholder="Your permanent access token">
                            <small class="text-muted">Get from Meta Business Suite > WhatsApp > API Setup</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone Number ID</label>
                            <input type="text" name="whatsapp_phone_number_id" class="form-control" 
                                   value="<?= get_setting('whatsapp_phone_number_id') ?>" 
                                   placeholder="e.g., 123456789012345">
                            <small class="text-muted">Found in WhatsApp Business Account settings</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Business Account ID</label>
                            <input type="text" name="whatsapp_business_account_id" class="form-control" 
                                   value="<?= get_setting('whatsapp_business_account_id') ?>" 
                                   placeholder="e.g., 987654321098765">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API Version</label>
                            <input type="text" name="whatsapp_api_url" class="form-control" 
                                   value="<?= get_setting('whatsapp_api_url') ?: 'https://graph.facebook.com/v18.0' ?>" 
                                   placeholder="https://graph.facebook.com/v18.0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Twilio Settings -->
            <div id="twilio_settings" class="provider-settings" style="display: none;">
                <h5 class="text-danger mb-3"><i class="fas fa-sms mr-2"></i>Twilio Settings</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account SID</label>
                            <input type="text" name="twilio_account_sid" class="form-control" 
                                   value="<?= get_setting('twilio_account_sid') ?>" 
                                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <small class="text-muted">Found in Twilio Console Dashboard</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Auth Token</label>
                            <input type="password" name="twilio_auth_token" class="form-control" 
                                   value="<?= get_setting('twilio_auth_token') ?>" 
                                   placeholder="Your auth token">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Twilio WhatsApp Number</label>
                            <input type="text" name="twilio_whatsapp_number" class="form-control" 
                                   value="<?= get_setting('twilio_whatsapp_number') ?>" 
                                   placeholder="+14155238886">
                            <small class="text-muted">WhatsApp-enabled Twilio number with whatsapp: prefix</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom API Settings -->
            <div id="custom_settings" class="provider-settings" style="display: none;">
                <h5 class="text-warning mb-3"><i class="fas fa-cog mr-2"></i>Custom API Settings</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API URL</label>
                            <input type="url" name="whatsapp_custom_url" class="form-control" 
                                   value="<?= get_setting('whatsapp_custom_url') ?>" 
                                   placeholder="https://api.yourprovider.com/send">
                            <small class="text-muted">Endpoint that accepts POST with phone and message</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API Key</label>
                            <input type="password" name="whatsapp_custom_key" class="form-control" 
                                   value="<?= get_setting('whatsapp_custom_key') ?>" 
                                   placeholder="Your API key">
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Notification Settings -->
            <h5 class="text-primary mb-3"><i class="fas fa-bell mr-2"></i>Notification Preferences</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notify_whatsapp_payment_reminder" 
                                   name="notify_whatsapp_payment_reminder" value="1" 
                                   <?= get_setting('notify_whatsapp_payment_reminder') == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="notify_whatsapp_payment_reminder">
                                Payment Reminders
                            </label>
                        </div>
                        <small class="text-muted">Send reminders before due date</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notify_whatsapp_loan_approval" 
                                   name="notify_whatsapp_loan_approval" value="1" 
                                   <?= get_setting('notify_whatsapp_loan_approval') == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="notify_whatsapp_loan_approval">
                                Loan Status Updates
                            </label>
                        </div>
                        <small class="text-muted">Notify on approval/rejection</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notify_whatsapp_payment_receipt" 
                                   name="notify_whatsapp_payment_receipt" value="1" 
                                   <?= get_setting('notify_whatsapp_payment_receipt') == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="notify_whatsapp_payment_receipt">
                                Payment Receipts
                            </label>
                        </div>
                        <small class="text-muted">Send receipt after payment</small>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-2"></i>Save WhatsApp Settings
                    </button>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-outline-primary" id="testWhatsApp">
                        <i class="fas fa-paper-plane mr-2"></i>Send Test Message
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle provider settings
    function toggleProviderSettings() {
        var provider = $('#whatsapp_provider').val();
        $('.provider-settings').hide();
        $('#' + provider + '_settings').show();
    }
    
    toggleProviderSettings();
    $('#whatsapp_provider').on('change', toggleProviderSettings);
    
    // Test WhatsApp
    $('#testWhatsApp').on('click', function() {
        var phone = prompt('Enter phone number (with country code, e.g., 919876543210):');
        if (!phone) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending...');
        
        $.post('<?= base_url('admin/settings/test_whatsapp') ?>', { phone: phone }, function(response) {
            if (response.success) {
                alert('Test message sent successfully!');
            } else {
                alert('Failed: ' + response.message);
            }
        }).fail(function() {
            alert('Request failed. Check your settings.');
        }).always(function() {
            $('#testWhatsApp').prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Send Test Message');
        });
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
