<?php

namespace tuja\util\messaging;

use tuja\util\Template;

class MessageSender
{
    public function send_mail($to, $subject, $body)
    {
        $attachments = [];
        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];
        $wrapped_body = Template::file('util/messaging/email_template.html')->render([
            'subject' => $subject,
            'body' => $body
        ]);
        return wp_mail($to, "[Tunnelbanejakten] $subject", $wrapped_body, $headers, $attachments);
    }
}