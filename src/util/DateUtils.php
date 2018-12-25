<?php

namespace util;


use data\model\ValidationException;
use DateTime;
use DateTimeZone;

class DateUtils
{
    public static function from_date_local_value($date_value)
    {
        if (!empty($date_value)) {
            $new_date = DateTime::createFromFormat("Y-m-d H:i",
                str_replace('T', ' ', $date_value),
                new DateTimeZone('Europe/Stockholm'));
            if ($new_date !== false) {
                return $new_date;
            } else {
                throw new ValidationException(null, 'Ogiltigt datum');
            }
        } else {
            return null;
        }
    }

    public static function to_date_local_value($date_time): string
    {
        $start_date_value = '';
        if ($date_time !== null) {
            $start_date = new DateTime('@' . $date_time->getTimestamp());
            $start_date->setTimezone(new DateTimeZone('Europe/Stockholm'));
            $start_date_value = $start_date->format("Y-m-d\TH:i");
        }
        return $start_date_value;
    }

}