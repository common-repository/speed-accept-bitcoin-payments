<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Speed_Blocks_Support extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'speed_payment_gateway';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_speed_payment_gateway_settings', [] );
	}

	public function is_active() {
		if($this->settings['enabled'] == 'yes'){	
			return true;
		}else{
			return false;
		}
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-speed-blocks-integration',
			WC_GATEWAY_SPEED_URL . '/build/index.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			WC_SPEED_BITCOIN_PAYMENT_VERSION,
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-speed-blocks-integration', 'speed-accept-bitcoin-payments');
		}
		return [ 'wc-speed-blocks-integration' ];
	}

	public function get_payment_method_data() {
		return [
			'title'       => $this->settings['speed_payment_method_name'],
			'description' => $this->settings['speed_description'],
			'logo_url'    => $this->get_logo()
		];
	}

	public function get_logo(){
		if($this->settings['speed_logo'] == 'speed_logo_show'){
			return 'https://images.tryspeed.com/plugins/speed-logo.png';
		}else{
			return null;
		}
	}

}