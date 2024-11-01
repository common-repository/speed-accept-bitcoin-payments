<?php

namespace Speed\SpeedBitcoinPayment;

class Logger
{
    public static $logger;
    const WC_LOG_FILENAME = 'speed-accept-bitcoin-payments';

    /**
     * Speed Logger to log all the debugs
     */
    public static function log($message)
    {

        if (!class_exists('WC_Logger')) {
            return;
        }

        if (apply_filters('wc_speed_logging', true, $message)) {
            if (empty(self::$logger)) {
                self::$logger = wc_get_logger();
            }

            $log_entry  = "\n" . '==== Speed Version: ' . WC_SPEED_BITCOIN_PAYMENT_VERSION . ' ====' . "\n";
            $log_entry .= "Speed: " . $message . "\n\n";

            self::$logger->debug($log_entry, ['source' => self::WC_LOG_FILENAME]);
        }
    }
}
