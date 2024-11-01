<?php
wp_enqueue_style('wcspeed-admin-style');
wp_enqueue_style('wcspeed-admin-bootstrap-style');
wp_enqueue_script('wcspeed-admin-jquery');
wp_enqueue_script('wcspeed-validation-script');
wp_enqueue_script('wcspeed-bootstrap-js');

$plugin_options           = 'woocommerce_speed_payment_gateway_settings';
$plugin_settings_defaults = array();
$plugin_settings          = get_option($plugin_options, $plugin_settings_defaults);
$plugin_image_path = 'https://images.tryspeed.com/plugins/';

$speedStatuses = [
    'confirmed' => 'Update Paid Orders To [Processing]',
];

$statuses = [
    'confirmed' => 'wc-processing',
];

$wcStatuses = wc_get_order_statuses();
unset($wcStatuses["wc-pending"]);
unset($wcStatuses["wc-cancelled"]);
unset($wcStatuses["wc-refunded"]);
unset($wcStatuses["wc-failed"]);
unset($wcStatuses["wc-checkout-draft"]);

compact('speedStatuses', 'statuses', 'wcStatuses');

//Call data from Options
$enabled                        = (!empty($plugin_settings['enabled'])) ? $plugin_settings['enabled'] : '';
$speed_transaction_mode         = (!empty($plugin_settings['speed_transaction_mode'])) ? $plugin_settings['speed_transaction_mode'] : '';
$speed_payment_method_name      = (!empty($plugin_settings['speed_payment_method_name'])) ? $plugin_settings['speed_payment_method_name'] : '';
$speed_statement_descriptor     = (!empty($plugin_settings['speed_statement_descriptor'])) ? $plugin_settings['speed_statement_descriptor'] : '';
$speed_description              = (!empty($plugin_settings['speed_description'])) ? $plugin_settings['speed_description'] : '';
$speed_test_restricted_key     = (!empty($plugin_settings['speed_test_restricted_key'])) ? $plugin_settings['speed_test_restricted_key'] : '';
$speed_webhook_test_secret_key  = (!empty($plugin_settings['speed_webhook_test_secret_key'])) ? $plugin_settings['speed_webhook_test_secret_key'] : '';
$speed_live_restricted_key     = (!empty($plugin_settings['speed_live_restricted_key'])) ? $plugin_settings['speed_live_restricted_key'] : '';
$speed_webhook_live_secret_key  = (!empty($plugin_settings['speed_webhook_live_secret_key'])) ? $plugin_settings['speed_webhook_live_secret_key'] : '';
$speed_woocommerce_order_states = (!empty($plugin_settings['speed_woocommerce_order_states'])) ? $plugin_settings['speed_woocommerce_order_states'] : $statuses;
$speed_logo                     = (!empty($plugin_settings['speed_logo'])) ? $plugin_settings['speed_logo'] : 'speed_logo_show';
$speed_code                     = (!empty($plugin_settings['speed_code'])) ? $plugin_settings['speed_code'] : '';

$post_data = get_transient('redirect_post_data');

if ($post_data) {
    delete_transient('redirect_post_data');
}

?>

<div class="speed-wrapper">
    <input type="hidden" id="speed_plugin_redirect_url" value="<?php echo home_url('?speed-settings-callback'); ?>">
    <input type="hidden" id="speed_plugin_enable_status" value="<?php echo $enabled; ?>">
    <input type="hidden" id="speed_plugin_transaction_mode" value="<?php echo $speed_transaction_mode; ?>">
    <input type="hidden" id="speed_success_img" value="<?php echo $plugin_image_path . 'success.png' ?>">
    <div class="speed-tab">
        <div class="speed-tab-item">
            <button class="disconnect-btn" type="button"><?php _e('Disconnect', 'speed-accept-bitcoin-payments'); ?></button>
        </div>
        <div class="speed-tab-content">
            <div class="loader-sec">
                <div style="padding: 16px;">
                    <div>
                        <img src="https://images.tryspeed.com/app/speed-preloader.gif"
                        alt="preloader"
                        width=10
                        height=10
                        style="width:fit-content; height:fit-content;"
                        />
                    </div>
                </div>
            </div>
            <div id="speed_error">
                <div class="alert alert-danger">
                    <div>
                        <img src="https://images.tryspeed.com/plugins/error-icon.png" width="18" height="16">
                        <span><?php _e("Oops! Something went wrong. Please try after <span id='waitTime'></span>.", 'speed-accept-bitcoin-payments'); ?></span>
                    </div>
                    <button type="button" class="close" onclick="closeAlert(this.parentNode);">×</button>
                </div>
            </div>
            <?php if(isset($post_data) && isset($post_data['msg'])) { ?>
                <div id="speed_connect_error">
                    <div class="alert alert-danger">
                        <div>
                            <img src="https://images.tryspeed.com/plugins/error-icon.png" width="18" height="16">
                            <span><?php _e($post_data['msg'], 'speed-accept-bitcoin-payments'); ?></span>
                        </div>
                        <button type="button" class="close" onclick="closeAlert(this.parentNode);">×</button>
                    </div>
                </div>
            <?php } ?>
            <div class="speed-status">
                <img src="<?php echo $plugin_image_path  . 'warning-icon.png' ?>" alt="Warning Icon"><?php _e("<span>Your store is now enabled in <b>Test Mode</b>. Use this mode to safely test Bitcoin payments without processing real transactions.</span>", 'speed-accept-bitcoin-payments'); ?>
            </div>

            <div class="speed-steps">
                <ul class="timeline">
                    <li class="timeline-step">
                        <div class="timeline-badge step1 active">1</div>
                        <div class="timeline-content">
                            <h3><?php _e('Step 1', 'speed-accept-bitcoin-payments'); ?></h3>
                            <p><?php _e('Link your Speed account to accept Bitcoin on your WooCommerce store!', 'speed-accept-bitcoin-payments'); ?></p>
                            <div class="speed-connect-section">
                                <button class="speed-connect-btn" id="speed-connect-btn"><?php _e('Connect Speed', 'speed-accept-bitcoin-payments'); ?><img src="<?php echo $plugin_image_path  . 'speed-logo-white.png' ?>" alt="Speed Icon"></button>
                                <div class="res-key-section" id="res-key-loading">
                                    <div class="circle"></div>
                                    <span><?php _e('Restricted keys are processing', 'speed-accept-bitcoin-payments'); ?></span>
                                </div>
                                <div class="res-key-section" id="res-key-generated">
                                    <img src="<?php echo $plugin_image_path  . 'success.png' ?>" alt="Success Icon">
                                    <span class="success"><?php _e('Restricted keys generated', 'speed-accept-bitcoin-payments'); ?></span>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="timeline-step">
                        <div class="timeline-badge step2">2</div>
                        <div class="timeline-content">
                            <h3><?php _e('Step 2', 'speed-accept-bitcoin-payments'); ?></h3>
                            <p><?php _e('Provide the required plugin details you want to show on your WooCommerce store.', 'speed-accept-bitcoin-payments'); ?></p>
                        </div>
                    </li>
                </ul>
            </div>

            <div id="setting-section" class="tab-content">
                <input type="hidden" name="speed_code_data" id="speed_code_data" value="<?= $speed_code; ?>">
                <input type="hidden" id="speed_webhook_url" name="speed_webhook_url" value="<?php echo add_query_arg('wc-api', 'wc_gateway_speed', trailingslashit(get_home_url())); ?>" readonly required>
                <input type="hidden" id="admin-ajax-url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                <input type="hidden" name="speed_test_restricted_key" id="speed_test_restricted_key" value="<?= $speed_test_restricted_key; ?>">
                <input type="hidden" name="speed_live_restricted_key" id="speed_live_restricted_key" value="<?= $speed_live_restricted_key; ?>">
                <input type="hidden" name="speed_webhook_test_secret_key" id="speed_webhook_test_secret_key" value="<?= $speed_webhook_test_secret_key; ?>">
                <input type="hidden" name="speed_webhook_live_secret_key" id="speed_webhook_live_secret_key" value="<?= $speed_webhook_live_secret_key; ?>">
                
                <h2><?php _e('Plugin Setting', 'speed-accept-bitcoin-payments'); ?></h2>
                <div class="speed-form-group">
                    <label for="enabled"><?php _e('Plugin Status', 'speed-accept-bitcoin-payments'); ?></label>
                    <div class="div-input">
                        <select id="enabled" name="enabled">
                            <option value="yes" <?php if ($enabled === "yes") echo 'selected'; ?>>Enable</option>
                            <option value="no" <?php if ($enabled === "no") echo 'selected'; ?>>Disable</option>
                        </select>
                    </div>
                </div>
                <div class="speed-form-group">
                    <label for="speed_transaction_mode"><?php _e('Transaction Mode', 'speed-accept-bitcoin-payments'); ?></label>
                    <div class="div-input">
                        <select id="speed_transaction_mode" name="speed_transaction_mode">
                            <option value="Test" <?php if ($speed_transaction_mode == "Test") echo 'selected'; ?>>Test</option>
                            <option value="Live" <?php if ($speed_transaction_mode == "Live") { echo 'selected'; } ?>>Live</option>
                        </select>
                    </div>
                </div>
                <div class="speed-form-group">
                    <label for="speed_payment_method_name"><?php _e('Payment Method Name', 'speed-accept-bitcoin-payments'); ?><span>*</span></label>
                    <div class="div-input">
                        <div class="div-input">
                            <input id="speed_payment_method_name" type="text" name="speed_payment_method_name" value="<?php echo $speed_payment_method_name; ?>" placeholder="Enter Payment Method Name" minlength="1" maxlength="30" onkeyup=checkVaidation() required>
                            <span><?php _e('The name entered here will appear on the payment method section of the checkout page.', 'speed-accept-bitcoin-payments'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="speed-form-group">
                    <label for="speed_statement_descriptor"><?php _e('Statement Descriptor', 'speed-accept-bitcoin-payments'); ?><span>*</span></label>
                    <div class="div-input">
                        <input id="speed_statement_descriptor" type="text" name="speed_statement_descriptor" value="<?php echo $speed_statement_descriptor; ?>" placeholder="Enter Statement Descriptor" minlength="1" maxlength="250" onkeyup=checkVaidation() required>
                        <span><?php _e('This note will be visible to your customer in their wallet app when they initiate the payment.', 'speed-accept-bitcoin-payments'); ?></span>
                    </div>
                </div>
                <div class="speed-form-group">
                    <label for="speed_description"><?php _e('Description', 'speed-accept-bitcoin-payments'); ?><span>*</span></label>
                    <div class="div-input">
                        <textarea id="speed_description" name="speed_description" placeholder="Enter Descripton" rows="3" cols="20" minlength="1" maxlength="250" onkeyup=checkVaidation() required><?php echo $speed_description; ?></textarea>
                        <span><?php _e('This description entered here will appear on the payment method description section of the checkout page.', 'speed-accept-bitcoin-payments'); ?></span>
                    </div>
                </div>
                <div class="speed-form-group">
                    <label for="speed_logo"><?php _e('Select image you want to show during customer checkout', 'speed-accept-bitcoin-payments'); ?></label>
                    <div class="div-input-radio">
                        <input type="radio" name="speed_logo" value="speed_logo_show" <?php if ($speed_logo == "speed_logo_show") echo "checked"; ?>> <img src="<?php echo $plugin_image_path  . 'speed-logo.png' ?>" alt="Speed Icon">
                        <input type="radio" name="speed_logo" value="speed_logo_hide" <?php if ($speed_logo == "speed_logo_hide") echo "checked"; ?>> <span class="radio-text"><?php _e('No Logo', 'speed-accept-bitcoin-payments'); ?></span>
                    </div>
                </div>
                <?php foreach ($speedStatuses as $speedState => $speedName) : ?>
                    <div class="speed-form-group">
                        <label for="<?php echo $speedState; ?>"><?php _e("Update Paid Orders To", 'speed-accept-bitcoin-payments'); ?></label>
                        <div class="div-input">
                            <select id="<?php echo $speedState; ?>" name="speed_woocommerce_order_states_<?php echo $speedState; ?>">
                                <?php
                                foreach ($wcStatuses as $wcState => $wcName) {
                                    echo "<option value='$wcState'";
                                    if ($wcState === $speed_woocommerce_order_states[$speedState]) {
                                        echo 'selected';
                                    }
                                    echo ">$wcName</option>";
                                }
                                ?>
                            </select>
                            <?php if (strpos($speedName, 'awaiting') !== FALSE) : ?>
                                <span><?php _e('Payment not guaranteed yet at this stage! Do not yet provide the product or service.', 'speed-accept-bitcoin-payments'); ?></span>
                            <?php endif; ?>

                            <span><?php _e('To update the order status upon receipt of payment in Speed, select the desired status that you would like to set in WordPress.', 'speed-accept-bitcoin-payments'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
             
            <div class="speed-form-group speed-btn-wrap">
                <button class="speed-connect-btn" id="generate-webhook-btn"><?php _e('Save Changes', 'speed-accept-bitcoin-payments'); ?></button>
                <p id="webhook-wait"></p>
                <div class="webhook-section" id="webhook-section-loading">
                    <div class="circle"></div>
                    <span><?php _e('Creating your webhooks', 'speed-accept-bitcoin-payments'); ?></span>
                </div>
                <div class="webhook-section" id="webhook-created">
                    <img src="<?php echo $plugin_image_path  . 'success.png' ?>" alt="Success Icon">
                    <span class="success"><?php _e('Webhooks generated', 'speed-accept-bitcoin-payments'); ?></span>
                </div>
                <button id="speed_save_btn" class="speed-btn save-btn"><?php _e('Save Changes', 'speed-accept-bitcoin-payments'); ?></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="disconnectModal" tabindex="-1" role="dialog" aria-labelledby="disconnectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disconnectModalLabel"><?php _e('Disconnect', 'speed-accept-bitcoin-payments'); ?></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <img src="<?php echo $plugin_image_path  . 'close.png' ?>" alt="Speed Icon">
                    </button>
                </div>
                <div class="modal-body">
                    <?php _e('Are you sure you want to disconnect this plugin?', 'speed-accept-bitcoin-payments'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancel-btn" class="btn" data-bs-dismiss="modal"><?php _e('Cancel', 'speed-accept-bitcoin-payments'); ?></button>
                    <button type="button" id="continue-btn" class="btn"><?php _e('Continue', 'speed-accept-bitcoin-payments'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>