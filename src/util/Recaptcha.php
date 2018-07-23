<?php

namespace util;


use Exception;

class Recaptcha
{
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function verify($user_resonse)
    {
        $curl = curl_init(self::VERIFY_URL);
        $post_data = array(
            'secret' => $this->secret,
            'response' => $user_resonse
        );

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            throw new Exception('Oj då, något fick snett när vi skulle kolla att du inte är en robot. Lite teknisk information: ' . var_export($info));
        }
        curl_close($curl);
        $response = json_decode($curl_response);
        if (!$response->success) {
            throw new Exception('Kom du ihåg att kryssa i att du inte är en robot?');
        }
    }
}