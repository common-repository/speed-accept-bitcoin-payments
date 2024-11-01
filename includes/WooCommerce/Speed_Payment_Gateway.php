<?php

namespace Speed\SpeedBitcoinPayment\WooCommerce;

use \Exception;
use WC_Payment_Gateway;
use Speed\SpeedBitcoinPayment\Logger;
use Speed\SpeedBitcoinPayment\Webhook;
use Speed\SpeedBitcoinPayment\Api_Call;


class Speed_Payment_Gateway extends WC_Payment_Gateway
{

    protected $speed_transaction_mode;
    protected $speed_payment_method_name;
    protected $speed_statement_descriptor;
    protected $speed_discription;
    protected $speed_test_restricted_key;
    protected $speed_webhook_test_secret_key;
    protected $speed_live_restricted_key;
    protected $speed_webhook_live_secret_key;
    protected $speed_woocommerce_order_states;
    protected $speed_logo;

    /**
     * Load the key variables through constructor to let WC know about the plugin options
     */
    public function __construct()
    {

        $this->id                 = 'speed_payment_gateway';
        $this->title              = __('Speed', 'speed-accept-bitcoin-payments');
        $this->method_title       = __('Speed', 'speed-accept-bitcoin-payments');
        $this->description        = __('Speed works by adding payment method on the checkout and then sending the details to Speed for verification.', 'speed-accept-bitcoin-payments');
        $this->method_description = __('Start accepting bitcoin instantly using lightning network. Connect your account to the Speed Bitcoin plugin in just two steps.', 'speed-accept-bitcoin-payments');
        $this->has_fields         = TRUE;
        $this->supports           = [
            'products',
        ];

        // load backend options fields
        $this->init_form_fields();

        // load the settings.
        $this->init_settings();

        $this->enabled                        = $this->get_option('enabled');
        $this->speed_transaction_mode         = $this->get_option('speed_transaction_mode');
        $this->speed_payment_method_name      = $this->get_option('speed_payment_method_name');
        $this->speed_statement_descriptor     = $this->get_option('speed_statement_descriptor');
        $this->speed_discription              = $this->get_option('speed_description');

        $this->speed_test_restricted_key     = $this->get_option('speed_test_restricted_key');
        $this->speed_webhook_test_secret_key  = $this->get_option('speed_webhook_test_secret_key');

        $this->speed_live_restricted_key     = $this->get_option('speed_live_restricted_key');
        $this->speed_webhook_live_secret_key  = $this->get_option('speed_webhook_live_secret_key');
        $this->speed_woocommerce_order_states  = $this->get_option('speed_woocommerce_order_states');
        $this->speed_logo  = $this->get_option('speed_logo');

        // Action hook to saves the settings
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        // Action hook to load webhook callback.
        add_action('woocommerce_api_wc_gateway_speed',  array($this, 'payment_callback'));


        // Save settings page options as defined in nested/injected HTML content.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this,
            'save_plugin_options',
        ]);
    }

    /**
     * Plugin options, creating form fileds to store data
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'speed_payment_config_form' => [
                'id'    => 'settings_page',
                'type'  => 'settings_page',
                'title'       => __('Payment mode', 'speed-accept-bitcoin-payments'),
            ],
        ];
    }

    /**
     * Plugin admin config page load 
     */
    public function generate_settings_page_html($key, $value)
    {
        include_once 'views/speed_options.php';
    }

    /**
     * Plugin save admin config page data
     */
    public function save_plugin_options()
    {
        $plugin_options           = 'woocommerce_speed_payment_gateway_settings';
        $plugin_settings_defaults = array();
        $plugin_settings          = get_option($plugin_options, $plugin_settings_defaults);

        $statuses = [
            'confirmed' => ($_POST['speed_woocommerce_order_states_confirmed'] && $_POST['speed_woocommerce_order_states_confirmed'] != '') ? sanitize_text_field($_POST['speed_woocommerce_order_states_confirmed']) : '',
        ];

        $speed_test_restricted_key = '';
        if ($_POST['speed_test_restricted_key'] && $_POST['speed_test_restricted_key'] != '' && preg_match("/rk_test_/i", $_POST['speed_test_restricted_key']) === 1) {
            $speed_test_restricted_key = sanitize_text_field($_POST['speed_test_restricted_key']);
        }

        $speed_webhook_test_secret_key = '';
        if ($_POST['speed_webhook_test_secret_key'] && $_POST['speed_webhook_test_secret_key'] != '' && preg_match("/wsec_/i", $_POST['speed_webhook_test_secret_key']) === 1) {
            $speed_webhook_test_secret_key = sanitize_text_field($_POST['speed_webhook_test_secret_key']);
        }

        $speed_live_restricted_key = '';
        if ($_POST['speed_live_restricted_key'] && $_POST['speed_live_restricted_key'] != '' && preg_match("/rk_live_/i", $_POST['speed_live_restricted_key']) === 1) {
            $speed_live_restricted_key = sanitize_text_field($_POST['speed_live_restricted_key']);
        }

        $speed_webhook_live_secret_key = '';
        if ($_POST['speed_webhook_live_secret_key'] && $_POST['speed_webhook_live_secret_key'] != '' && preg_match("/wsec_/i", $_POST['speed_webhook_live_secret_key']) === 1) {
            $speed_webhook_live_secret_key = sanitize_text_field($_POST['speed_webhook_live_secret_key']);
        }

        $new_plugin_settings = [
            'enabled'                        => ($_POST['enabled'] && $_POST['enabled'] != '') ? sanitize_text_field($_POST['enabled']) : '',
            'speed_transaction_mode'         => ($_POST['speed_transaction_mode'] && $_POST['speed_transaction_mode'] != '') ? sanitize_text_field($_POST['speed_transaction_mode']) : '',
            'speed_payment_method_name'      => ($_POST['speed_payment_method_name'] && $_POST['speed_payment_method_name'] != '') ? sanitize_text_field($_POST['speed_payment_method_name']) : '',
            'speed_statement_descriptor'     => ($_POST['speed_statement_descriptor'] && $_POST['speed_statement_descriptor'] != '') ? sanitize_text_field($_POST['speed_statement_descriptor']) : '',
            'speed_description'              => ($_POST['speed_description'] && $_POST['speed_description'] != '') ? sanitize_text_field($_POST['speed_description']) : '',
            'speed_test_restricted_key'     => $speed_test_restricted_key,
            'speed_webhook_test_secret_key'  => $speed_webhook_test_secret_key,
            'speed_live_restricted_key'     => $speed_live_restricted_key,
            'speed_webhook_live_secret_key'  => $speed_webhook_live_secret_key,
            'speed_woocommerce_order_states' => $statuses,
            'speed_logo'                     => ($_POST['speed_logo'] && $_POST['speed_logo'] != '') ? sanitize_text_field($_POST['speed_logo']) : '',
            'speed_code'                     => ($_POST['speed_code_data'] && $_POST['speed_code_data'] != '') ? sanitize_text_field($_POST['speed_code_data']) : '',
        ];

        /**
         * Update data to database
         */
        update_option($plugin_options, $new_plugin_settings);
    }

    /**
     * Get title and set it to checkout page
     */
    public function get_title()
    {
        if (!empty($this->speed_payment_method_name)) {
            $title_text = stripcslashes($this->speed_payment_method_name);
            $title      = __($title_text, 'speed-accept-bitcoin-payments');
        } else {
            $title = __('Speed Bitcoin Payment', 'speed-accept-bitcoin-payments');
        }

        return apply_filters('woocommerce_gateway_title', $title, $this->id);
    }

    /**
     * Get description and set it to checkout page
     */
    public function get_description()
    {
        if (!empty($this->speed_discription)) {
            $description_text = stripcslashes($this->speed_discription);
            $description      = __($description_text, 'speed-accept-bitcoin-payments');
        } else {
            $description = __('Speed is a lightning-network-based Bitcoin payment gateway. Use a Bitcoin or Lightning wallet to make a payment.', 'speed-accept-bitcoin-payments');
        }

        return apply_filters('woocommerce_gateway_description', $description, $this->id);
    }

    /**
     * Get icon and set it to checkout page
     */
    public function get_icon()
    {

        $logo = $this->speed_logo;

        switch ($logo) {
            case NULL:
            case 'speed_logo_show':
                $style    = 'style="max-width: 25px !important;max-height: none !important;"';
                break;
            case 'speed_logo_hide':
            default:
                return;
        }

        $icon_url = WC_SPEED_BITCOIN_PAYMENT_ASSETS . '/images/';

        $icon = '<img src="' . $icon_url . $iconfile . '" alt="Speed logo" ' . $style . ' />';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /*
     * Fields validation
     */
    public function validate_fields()
    {
        return true;
    }

    /*
     * We're processing the payments here, and redirect to payment page and update the order status
    */
    public function process_payment($order_id)
    {
        try {
            $order = wc_get_order($order_id);
            $order_info = ' - Order number' . $order->get_id();
            $statement_descriptor = $this->speed_statement_descriptor;
            $order_desc_length = strlen($order_info);

            $checkout_order_info = get_post_meta($order->get_id(), 'sabp_order_id', true);

            if ($this->check_payment_method_active()) {
                if (empty($checkout_order_info)) {
                    if (is_numeric($order->get_total())) {
                        $site_name = get_bloginfo('name');

                        $params = array(
                            'currency' => sanitize_text_field($order->get_currency()),
                            'amount' => $order->get_total(),
                            'statement_descriptor' => sanitize_textarea_field(substr($statement_descriptor, 0, 255)),
                            'description' => sanitize_textarea_field(substr($site_name, 0, 255 - $order_desc_length) . $order_info),
                            'success_url' => sanitize_url($order->get_checkout_order_received_url()),
                            'cancel_url' => sanitize_url(wc_get_checkout_url()),
                            'source' => sanitize_textarea_field(substr("woocommerce-speed-plugin-" . $site_name, 0, 255 - $order_desc_length)),
                            'source_id' => $order->get_id()
                        );

                        $key = $this->speed_transaction_mode == 'Test' ? $this->speed_test_restricted_key : $this->speed_live_restricted_key;
                        Logger::log('Order Details ' . $order_info);
                        
                        Logger::log('Webhook is active');
                        $response = Api_Call::request('payment-page', $key, "POST", $params);
                        $resbody = json_decode(wp_remote_retrieve_body($response));

                        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 201) {
                            if ($resbody->errors) {
                                return Logger::log($resbody->errors[0]->message, true);
                            }
                        } else {
                            $speed_order = $resbody;
                        }

                        $meta_value = array(
                            'url' => $speed_order->url,
                            'order_id' => $order_id,
                            'checkout_id' => $speed_order->id,
                            'status' => $order->status
                        );

                        update_post_meta($order->get_id(), 'sabp_order_id', $meta_value);

                        $order->update_status('wc-pending');

                        return array(
                            'result' => 'success',
                            'redirect' => $speed_order->url . "?source_type=woocommerce",
                        );
                    } else {
                        return Logger::log('Amount should be a number.');
                    }
                } else {
                    return array(
                        'result' => 'success',
                        'redirect' => $checkout_order_info['url'] . "?source_type=woocommerce",
                    );
                }
            } else {
                Logger::log('Webhook is deactive');
                $order->update_status('wc-failed');
                return array(
                    'result'   => 'failure',
                    'redirect' => wc_get_checkout_url(),
                );
            }
        } catch (Exception $e) {
            Logger::log('Exception caught ' . $e->getMessage());
        }
    }

    /*
     * Webhook callback function after confirming payment
    */
    public function payment_callback()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] !== 'POST') || !isset($_GET['wc-api']) || ('wc_gateway_speed' !== $_GET['wc-api'])) {
            Logger::log('You can not access this page');
            echo "You can not access this page.";
            exit;
        }

        // Validate it to make sure it is legit.
        $request_body = file_get_contents('php://input');
        $request_body_decoded = json_decode($request_body, true);
        try {
            if ($_GET['wc-api'] == 'wc_gateway_speed' && str_contains($request_body_decoded['data']['object']['source'], 'woocommerce-speed-plugin') && $request_body_decoded['event_type'] === "checkout_session.paid" && !empty($request_body_decoded['data']['object']['source_id'])) {
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                $header = array(
                    'webhook-signature' => $headers['webhook-signature'],
                    'webhook-timestamp' => $headers['webhook-timestamp'],
                    'webhook-id'        => $headers['webhook-id'],
                );

                $Webhook_secret = $this->speed_transaction_mode == 'Test' ? $this->speed_webhook_test_secret_key : $this->speed_webhook_live_secret_key;
                $wh = new Webhook($Webhook_secret);
                $response = $wh->verify($request_body, $header);

                if ($response['event_type'] == 'checkout_session.paid') {
                    $order_id = $response['data']['object']['source_id'];
                    $order = wc_get_order($order_id);
                    $order->update_status($this->speed_woocommerce_order_states['confirmed']);
                    Logger::log('Order id: ' . $order_id . ' status updated to ' . $this->speed_woocommerce_order_states['confirmed'] . ' by Speed plugin.');
                    status_header(200);
                    exit;
                }
            } else {
                status_header(204);
                exit;
            }
        } catch (Exception $e) {
            Logger::log('Exceptions::: ' . $e->getMessage());
            Logger::log('Webhook order update failed. ' . print_r($request_body, true));
            status_header(204);
            exit;
        }
    }

    /*
     * Check webhook active or not
    */
    public function check_payment_method_active(){
        if ($this->speed_transaction_mode == 'Test') {
            if ($this->speed_test_restricted_key == '' || $this->speed_webhook_test_secret_key == '') {
                return false;
            } else {
                $params = array(
                    'secret' => $this->speed_webhook_test_secret_key,
                    'url' => add_query_arg('wc-api', 'wc_gateway_speed', trailingslashit(get_home_url())),
                );
                $response = Api_Call::request('webhooks/verify-secret', $this->speed_test_restricted_key, "POST", $params);
                $response = json_decode(wp_remote_retrieve_body($response));
                if ($response->exists !== 1 && strtolower($response->status) !== "active") {
                    return false;
                }
            }
        } else {
            if ($this->speed_live_restricted_key == '' || $this->speed_webhook_live_secret_key == '') {
                return false;
            } else {
                $params = array(
                    'secret' => $this->speed_webhook_live_secret_key,
                    'url' => add_query_arg('wc-api', 'wc_gateway_speed', trailingslashit(get_home_url())),
                );
                $response = Api_Call::request('webhooks/verify-secret', $this->speed_live_restricted_key, "POST", $params);
                $response = json_decode(wp_remote_retrieve_body($response));
                if ($response->exists !== 1 && strtolower($response->status) !== "active") {
                    return false;
                }
            }
        }
        
        return true;
    }
}
