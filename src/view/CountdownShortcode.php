<?php

namespace tuja\view;


use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use DateTime;

class CountdownShortcode
{
    private function render($the_date, $past_tense_format, $future_tense_format): string
    {
        if ($the_date === null) {
            return '[no date set]';
        }
        if (empty($past_tense_format)) {
            return '[past_format must be specified]';
        }
        if (empty($future_tense_format)) {
            return '[future_format must be specified]';
        }
        if (strpos($past_tense_format, '$1') === false || strpos($future_tense_format, '$1') === false) {
            return '[format must contain the placeholder "$1"]';
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('tuja-countdown-script');
        $seconds_left = $the_date->getTimestamp() - (new DateTime())->getTimestamp();
        return sprintf('<span class="tuja-countdown" data-seconds-left="%d" data-format-past="%s" data-format-future="%s">%s</span>',
            $seconds_left,
            $past_tense_format,
            $future_tense_format,
            // The returned "fuzzy time" will only be visible until the Javascript "takes over" (assuming Javascript is enabled in the browser).
            $this->to_fuzzy_time($the_date, $past_tense_format, $future_tense_format));
    }

    // The PHP and Javascript implementations of "fuzzy time" are very similar but not identical
    private function to_fuzzy_time(DateTime $other, string $pasteTenseFormat, string $futureTenseFormat)
    {
        $now = new DateTime();
        $diff = $now->diff($other);

        if ($diff->y > 0) {
            $value = $this->join_two_time_values($diff->y, 'år', $diff->m, 'månader');
        } elseif ($diff->m > 0) {
            $value = $this->join_two_time_values($diff->m, 'månader', $diff->d, 'dagar');
        } elseif ($diff->d > 0) {
            $value = $this->join_two_time_values($diff->d, 'dagar', $diff->h, 'tim');
        } elseif ($diff->h > 0) {
            $value = $this->join_two_time_values($diff->h, 'tim', $diff->i, 'min');
        } elseif ($diff->i > 0) {
            $value = $this->join_two_time_values($diff->i, 'min', 0, '');
        } else {
            $value = 'mindre än en minut';
        }

        if ($diff->invert) {
            return str_replace('$1', $value, $pasteTenseFormat);
        } else {
            return str_replace('$1', $value, $futureTenseFormat);
        }
    }

    private function join_two_time_values($value1, $label1, $value2, $label2)
    {
        if ($value2 > 0) {
            return sprintf('%d %s och %d %s', $value1, $label1, $value2, $label2);
        } else {
            return sprintf('%d %s', $value1, $label1);
        }
    }

    public static function signup_opens($atts)
    {
        return self::competition_shortcode(function ($competition) {
            return $competition->create_group_start;
        }, $atts);
    }

    public static function signup_closes($atts)
    {
        return self::competition_shortcode(function ($competition) {
            return $competition->create_group_end;
        }, $atts);
    }

    public static function submit_form_response_opens($atts)
    {
        return self::form_shortcode(function ($form) {
            return $form->submit_response_start;
        }, $atts);
    }

    public static function submit_form_response_closes($atts)
    {
        return self::form_shortcode(function ($form) {
            return $form->submit_response_end;
        }, $atts);
    }

    private static function competition_shortcode($date_function, $atts)
    {
        global $wpdb;
        $dao = new CompetitionDao($wpdb);
        $competition = $dao->get($atts['competition']);
        if ($competition === false) {
            return '[competition not found]';
        }
        $the_date = $date_function($competition);

        return self::shortcode($atts, $the_date);
    }

    private static function form_shortcode($date_function, $atts)
    {
        global $wpdb;
        $dao = new FormDao($wpdb);
        $form = $dao->get($atts['form']);
        if ($form === false) {
            return '[form not found]';
        }
        $the_date = $date_function($form);

        return self::shortcode($atts, $the_date);
    }

    private static function shortcode($atts, $the_date): string
    {
        $past_format = $atts['past_format'];
        if (empty($past_format)) {
            return '[past_format must be specified]';
        }

        $future_format = $atts['future_format'];
        if (empty($future_format)) {
            return '[future_format must be specified]';
        }

        $component = new CountdownShortcode();
        return $component->render($the_date, $past_format, $future_format);
    }
}