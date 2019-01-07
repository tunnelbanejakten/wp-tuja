<?php

namespace util;

use Parsedown;
use tuja\data\model\Group;
use tuja\data\model\Person;

class Template
{
    private $content;

    private function __construct($content)
    {
        $this->content = $content;
    }

    public function render($parameters = array(), $is_markdown = false)
    {
        $rendered_content = $this->content;
        foreach ($parameters as $name => $value) {
            $rendered_content = str_replace('{{' . $name . '}}', $value, $rendered_content);
        }
        if ($is_markdown) {
            $markdown_parser = new Parsedown();
            return $markdown_parser->parse($rendered_content);
        } else {
            return $rendered_content;
        }
    }

    public function get_variables()
    {
        $variables = [];
        preg_match_all('/\{\{([a-zA-Z_]+)\}\}/', $this->content, $variables);
        return array_unique($variables[1]);
    }

    public static function person_parameters(Person $person)
    {
        return [
            'person_key' => $person->random_id,
            'person_name' => $person->name,
            'person_phone' => $person->phone,
            'person_email' => $person->email
        ];
    }

    public static function group_parameters(Group $group)
    {
        return [
            'group_name' => htmlspecialchars($group->name),
            'group_key' => $group->random_id
        ];
    }

    public static function site_parameters()
    {
        return [
            'base_url' => get_site_url()
        ];
    }

    public static function string($content)
    {
        return new Template($content);
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