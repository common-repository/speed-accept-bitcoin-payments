<?php

namespace Speed\SpeedBitcoinPayment;

class Api_Call
{
    public static function request($url,$key, $method = 'POST', $params = array())
    {
        $url = "https://api.tryspeed.com/" . $url;

        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($key),
                'speed-version' => '2022-10-15'
            ),
            'body' => json_encode($params),
            'method' => $method,
        );

        $response = wp_remote_request($url, $args);

        return $response;
    }
}
