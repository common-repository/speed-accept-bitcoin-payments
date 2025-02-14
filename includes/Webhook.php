<?php

namespace Speed\SpeedBitcoinPayment;

use Speed\SpeedBitcoinPayment\Logger;

class Webhook
{
    const SECRET_PREFIX = "wsec_";
    private $secret;

    public function __construct($secret)
    {
        if (substr($secret, 0, strlen(Webhook::SECRET_PREFIX)) === Webhook::SECRET_PREFIX) {
            $secret = substr($secret, strlen(Webhook::SECRET_PREFIX));
        }
        $this->secret = base64_decode($secret);
    }

    public function verify($payload, $headers)
    {
        if (
            isset($headers['webhook-id'])
            && isset($headers['webhook-timestamp'])
            && isset($headers['webhook-signature'])
        ) {
            $msgId = $headers['webhook-id'];
            $msgTimestamp = $headers['webhook-timestamp'];
            $msgSignature = $headers['webhook-signature'];
        } else {
            Logger::log('Missing required headers');
        }

        $signature = $this->sign($msgId, $msgTimestamp, $payload);
        $expectedSignature = explode(',', $signature, 2)[1];

        $passedSignatures = explode(' ', $msgSignature);
        foreach ($passedSignatures as $versionedSignature) {
            $sigParts = explode(',', $versionedSignature, 2);
            $version = $sigParts[0];
            $passedSignature = $sigParts[1];

            if (strcmp($version, "v1") != 0) {
                continue;
            }

            if (hash_equals($expectedSignature, $passedSignature)) {
                return json_decode($payload, true);
            }
        }
        Logger::log('No matching signature found');
    }

    public function sign($msgId, $timestamp, $payload)
    {
        $is_positive_integer = ctype_digit($timestamp);
        if (!$is_positive_integer) {
            Logger::log('Invalid timestamp');
        }
        $toSign = "{$msgId}.{$timestamp}.{$payload}";
        $hex_hash = hash_hmac('sha256', $toSign, $this->secret);
        $signature = base64_encode(pack('H*', $hex_hash));

        return "v1,{$signature}";
    }
}
