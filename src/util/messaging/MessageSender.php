<?php

namespace util\messaging;

class MessageSender
{
    public function send_mail($to, $subject, $body)
    {
        $attachments = [];
        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];
        return wp_mail($to, "[Tunnelbanejakten] $subject", utf8_encode($body), $headers, $attachments);
    }
}