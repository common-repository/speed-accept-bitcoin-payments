<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the
 * plugin admin area. This file also includes all of the dependencies used by
 * the plugin, registers the activation and deactivation functions, and defines
 * a function that starts the plugin.
 *
 * @link              https://tryspeed.com
 * @since             1.0.0
 * @package           Speed_Bitcoin_Payment_for_WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Speed Bitcoin Payments for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/speed-accept-bitcoin-payments/
 * Description:       Accept Bitcoin Instantly via Speed
 * Version:           2.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Speed Team
 * Author URI:        https://tryspeed.com
 * Text Domain:       speed-accept-bitcoin-payments
 * Domain Path:       /languages
 *
 * WC requires at least: 5.0.0
 * WC tested up to: 8.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'WC_GATEWAY_SPEED_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_GATEWAY_SPEED_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/*
 * Main plugin class
 */
final class WC_Speed_Bitcoin_Payment
{

    /*
     * Plugin version
     *
     * $var string
     */
    const version = '2.0.0';

    /*
     * Plugin constructor
     */
    private function __construct()
    {

        $this->define_constants();

        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action( 'wp_ajax_handle_speed_store_secret', [$this, 'stotreSpeedWebhookSecret'] );
        add_action( 'wp_ajax_speed_store_restricted', [$this, 'stotreSpeedRestrictedKey'] );
        add_action( 'wp_ajax_speed_disconnect', [$this, 'speedDisconnectData'] );
    }

    /**
     * Initializes a singleton instance
     *
     * @return \WC_Speed_Bitcoin_Payment
     */
    public static function init()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     */
    public function define_constants()
    {
        define('WC_SPEED_BITCOIN_PAYMENT_VERSION', self::version);
        define('WC_SPEED_BITCOIN_PAYMENT_FILE', __FILE__);
        define('WC_SPEED_BITCOIN_PAYMENT_PATH', __DIR__);
        define('WC_SPEED_BITCOIN_PAYMENT_URL', plugins_url('', WC_SPEED_BITCOIN_PAYMENT_FILE));
        define('WC_SPEED_BITCOIN_PAYMENT_ASSETS', WC_SPEED_BITCOIN_PAYMENT_URL . '/assets');
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin()
    {

        new Speed\SpeedBitcoinPayment\Assets();
        add_filter('woocommerce_payment_gateways', [$this, 'speed_wc_add_gateway_class']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_extra_links']);
        load_plugin_textdomain('speed-accept-bitcoin-payments', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    public function speed_wc_add_gateway_class($gateways)
    {
        $gateways[] = new Speed\SpeedBitcoinPayment\WooCommerce\Speed_Payment_Gateway();
        return $gateways;
    }

    /**
     * Adds plugin page links
     *
     * @since 1.0.0
     * @param array $links all plugin links
     * @return array $links all plugin links + our custom links (i.e., "Settings")
     */
    public function add_extra_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=speed_payment_gateway') . '">' . __('Configure', 'speed-accept-bitcoin-payments') . '</a>'
        );

        return array_merge($plugin_links, $links);
    }

    public function stotreSpeedWebhookSecret(){
        $plugin_options           = 'woocommerce_speed_payment_gateway_settings';
        $plugin_settings          = get_option($plugin_options);
        if (!$plugin_settings) {
            $plugin_settings = array();
        }
        $plugin_settings['speed_webhook_test_secret_key'] = $_POST['webhook_test_secret'];
        $plugin_settings['speed_webhook_live_secret_key'] = $_POST['webhook_live_secret'];
        update_option($plugin_options, $plugin_settings);
    }

    public function stotreSpeedRestrictedKey(){
        $plugin_options           = 'woocommerce_speed_payment_gateway_settings';
        $plugin_settings          = get_option($plugin_options);
        if (!$plugin_settings) {
            $plugin_settings = array();
        }

        $plugin_settings['speed_test_restricted_key'] = $_POST['test_restricted_key'];
        $plugin_settings['speed_live_restricted_key'] = $_POST['live_restricted_key'];
        update_option($plugin_options, $plugin_settings);
    }

    public function speedDisconnectData(){
        $plugin_options = 'woocommerce_speed_payment_gateway_settings';

        $statuses = [
            'confirmed' => 'wc-processing',
        ];

        $new_plugin_settings = [
            'enabled' => 'yes',
            'speed_transaction_mode' => 'Test',
            'speed_payment_method_name' => '',
            'speed_statement_descriptor' => '',
            'speed_description' => '',
            'speed_test_restricted_key' => '',
            'speed_webhook_test_secret_key' => '',
            'speed_live_restricted_key' => '',
            'speed_webhook_live_secret_key' => '',
            'speed_woocommerce_order_states' => $statuses,
            'speed_logo' => 'speed_logo_show',
        ];

        update_option($plugin_options, $new_plugin_settings);
        wp_send_json_success(['status' => 1]);
    }


    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate()
    {

        $checkWC = in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));

        if (!$checkWC) {
            $admin_notice = new Speed\SpeedBitcoinPayment\Admin_Notice();
            add_action('admin_notices', [$admin_notice, 'check_require_plugin_notice']);
        } else {
            $site_name = get_bloginfo('name');

            //Set older plugin options into new one
            $plugin_options = 'woocommerce_speed_payment_gateway_settings';
            $plugin_settings_defaults = array();
            $plugin_settings = get_option($plugin_options, $plugin_settings_defaults);

            $statuses = [
                'confirmed' => 'wc-processing',
            ];

            $new_plugin_settings = [
                'enabled' => 'yes',
                'speed_transaction_mode' => 'Test',
                'speed_payment_method_name' => '',
                'speed_statement_descriptor' => '',
                'speed_description' => '',
                'speed_test_restricted_key' => '',
                'speed_webhook_test_secret_key' => '',
                'speed_live_restricted_key' => '',
                'speed_webhook_live_secret_key' => '',
                'speed_woocommerce_order_states' => $statuses,
                'speed_logo' => 'speed_logo_show',
            ];

            update_option($plugin_options, $new_plugin_settings);
        }
    }
}

/**
 * Initializes the main plugin
 *
 * @return \WC_Speed_Bitcoin_Payment
 */
function wc_speed_bitcoin_payment()
{
    return WC_Speed_Bitcoin_Payment::init();
}

// kick-off the plugin
wc_speed_bitcoin_payment();

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

add_action( 'woocommerce_blocks_loaded', 'woocommerce_gateway_speed_woocommerce_block_support' );

function woocommerce_gateway_speed_woocommerce_block_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once dirname( __FILE__ ) . '/includes/class-wc-speed-blocks-support.php';

        add_action(
        'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new WC_Speed_Blocks_Support );
        } );
    }
}

function add_speed_endpoint() {
  add_rewrite_endpoint( 'speed-settings-callback', EP_ROOT );
}

add_action( 'init', 'add_speed_endpoint' );

add_action( 'template_redirect', function() {
    global $wp_query;

    if (! isset( $wp_query->query_vars['speed-settings-callback'] ) ) {
        return;
    }

    $status = sanitize_html_class($_GET['status']);

    if(isset($_GET['code'])) {
        $data['code'] = sanitize_html_class($_GET['code']);
    }

    $speedSettingsUrl = admin_url('admin.php?page=wc-settings&tab=checkout&section=speed_payment_gateway');
    
    $plugin_options = 'woocommerce_speed_payment_gateway_settings';
    $plugin_settings = get_option($plugin_options);

    if (!$plugin_settings) {
        $plugin_settings = array();
    } else {
        if(isset($plugin_settings['speed_test_publishable_key'])) {
            unset($plugin_settings['speed_test_publishable_key']);
            unset($plugin_settings['speed_live_publishable_key']);
            $plugin_settings['speed_webhook_test_secret_key'] = '';
            $plugin_settings['speed_webhook_live_secret_key'] = '';
        }
    }

    update_option($plugin_options, $plugin_settings);
    
    if (isset($data['code']) && $status == 'SUCCESS') {
        $plugin_settings['speed_code'] = $data['code'];
        $plugin_settings['enabled'] = __('yes', 'speed-accept-bitcoin-payments');
        update_option($plugin_options, $plugin_settings);
    } else {
        $post_data = array(
            'msg' => __('Unfortunately, connect to your Speed account was denied. You can retry the authorization process or contact Speed support for assistance.', 'speed-accept-bitcoin-payments')
        );

        set_transient('redirect_post_data', $post_data, 120);
    }

    wp_redirect($speedSettingsUrl);
});