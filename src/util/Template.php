<?php

namespace util;

class Template
{
    private $content;

    private function __construct($content)
    {
        $this->content = $content;
    }

    public function render($parameters = array())
    {
        $rendered_content = $this->content;
        foreach ($parameters as $name => $value) {
            $rendered_content = str_replace('{{' . $name . '}}', $value, $rendered_content);
        }
        return $rendered_content;
    }

    public static function file($path)
    {
        if (file_exists($path)) {
            return new Template(file_get_contents($path));
        } elseif (file_exists(__DIR__ . '/' . $path)) {
            return new Template(file_get_contents(__DIR__ . '/' . $path));
        } elseif (file_exists(__DIR__ . '/../' . $path)) {
            return new Template(file_get_contents(__DIR__ . '/../' . $path));
        }
    }
}