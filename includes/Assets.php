<?php

namespace Speed\SpeedBitcoinPayment;

/**
 * Assets handlers class
 */
class Assets
{

    /**
     * Class constructor
     */
    function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * All available scripts
     *
     * @return array
     */
    public function get_scripts()
    {
        return [
            'wcspeed-admin-jquery' => [
                'src'     => 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js',
            ],
            'wcspeed-bootstrap-js' => [
                'src'     => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            ],
            'wcspeed-validation-script' => [
                'src'     => WC_SPEED_BITCOIN_PAYMENT_ASSETS . '/js/validation.js',
            ],
        ];
    }

    /**
     * All available styles
     *
     * @return array
     */
    public function get_styles()
    {
        return [
            'wcspeed-admin-bootstrap-style' => [
                'src'     => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            ],
            'wcspeed-admin-icons' => [
                'src'     => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css',
            ],
            'wcspeed-admin-style' => [
                'src'     => WC_SPEED_BITCOIN_PAYMENT_ASSETS . '/css/admin.css',
            ],
            'wcspeed-checkout-style' => [
                'src'     => WC_SPEED_BITCOIN_PAYMENT_ASSETS . '/css/checkout.css',
            ]
        ];
    }

    /**
     * Register scripts and styles
     *
     * @return void
     */
    public function register_assets()
    {
        $scripts = $this->get_scripts();
        $styles  = $this->get_styles();

        foreach ($scripts as $handle => $script) {
            wp_register_script($handle, $script['src'], true);
        }

        foreach ($styles as $handle => $style) {
            wp_register_style($handle, $style['src']);
        }
    }
}
