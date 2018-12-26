<?php

namespace view;


use data\store\CompetitionDao;
use data\store\FormDao;
use DateTime;

class SignupOpensCountdownShortcode extends CountdownShortcode
{

    private $competition_dao;

    public function __construct($wpdb)
    {
        $this->competition_dao = new CompetitionDao($wpdb);
    }

    public function render(int $competition_id, string $past_tense_format = '$1 ago', string $future_tense_format = 'in $1')
    {
        $competition = $this->competition_dao->get($competition_id);
        if ($competition === false) {
            return '[competition not found]';
        }
        $the_date = $competition->create_group_start;
        return $this->get_html($the_date, $future_tense_format, $past_tense_format);
    }

}
class SignupClosesCountdownShortcode extends CountdownShortcode
{

    private $competition_dao;

    public function __construct($wpdb)
    {
        $this->competition_dao = new CompetitionDao($wpdb);
    }

    public function render(int $competition_id, string $past_tense_format = '$1 ago', string $future_tense_format = 'in $1')
    {
        $competition = $this->competition_dao->get($competition_id);
        if ($competition === false) {
            return '[competition not found]';
        }
        $the_date = $competition->create_group_end;
        return $this->get_html($the_date, $future_tense_format, $past_tense_format);
    }

}

class FormOpensCountdownShortcode extends CountdownShortcode
{

    private $form_dao;

    public function __construct($wpdb)
    {
        $this->form_dao = new FormDao($wpdb);
    }

    public function render(int $form_id, string $past_tense_format = '$1 ago', string $future_tense_format = 'in $1')
    {
        $form = $this->form_dao->get($form_id);
        if ($form === false) {
            return '[form not found]';
        }
        $the_date = $form->submit_response_start;
        return $this->get_html($the_date, $future_tense_format, $past_tense_format);
    }

}

class FormClosesCountdownShortcode extends CountdownShortcode
{

    private $form_dao;

    public function __construct($wpdb)
    {
        $this->form_dao = new FormDao($wpdb);
    }

    public function render(int $form_id, string $past_tense_format = '$1 ago', string $future_tense_format = 'in $1')
    {
        $form = $this->form_dao->get($form_id);
        if ($form === false) {
            return '[form not found]';
        }
        $the_date = $form->submit_response_end;
        return $this->get_html($the_date, $future_tense_format, $past_tense_format);
    }

}

class CountdownShortcode
{
    protected function get_html($the_date, string $future_tense_format, string $past_tense_format): string
    {
        if ($the_date === null) {
            return '[no date set]';
        }
        if (strpos($past_tense_format, '$1') === false || strpos($future_tense_format, '$1') === false) {
            return '[format must contain the placeholder "$1"]';
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('tuja-countdown-script');
        return sprintf('<span class="tuja-countdown" data-seconds-left="%d" data-format-past="%s" data-format-future="%s">%s</span>',
            $the_date->getTimestamp() - (new DateTime())->getTimestamp(),
            $past_tense_format,
            $future_tense_format,
            $this->to_fuzzy_time($the_date, $past_tense_format, $future_tense_format));
    }

    protected function to_fuzzy_time(DateTime $other, string $pasteTenseFormat, string $futureTenseFormat)
    {
        $now = new DateTime();
        $diff = $now->diff($other);

        if ($diff->y > 0) {
            $value = $this->helper($diff->y, 'år', $diff->m, 'månader');
        } elseif ($diff->m > 0) {
            $value = $this->helper($diff->m, 'månader', $diff->d, 'dagar');
        } elseif ($diff->d > 0) {
            $value = $this->helper($diff->d, 'dagar', $diff->h, 'h');
        } elseif ($diff->h > 0) {
            $value = $this->helper($diff->h, 'h', $diff->i, 'min');
        } elseif ($diff->i > 0) {
            $value = $this->helper($diff->i, 'min', 0, '');
        } else {
            $value = 'mindre än en minut';
        }

        if ($diff->invert) {
            return str_replace('$1', $value, $pasteTenseFormat);
        } else {
            return str_replace('$1', $value, $futureTenseFormat);
        }
    }

    private function helper($value1, $label1, $value2, $label2)
    {
        if ($value2 > 0) {
            return sprintf('%d %s och %d %s', $value1, $label1, $value2, $label2);
        } else {
            return sprintf('%d %s', $value1, $label1);
        }
    }
}