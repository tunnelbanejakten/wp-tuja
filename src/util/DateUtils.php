<?php

namespace util;


use data\model\ValidationException;
use DateTime;
use DateTimeZone;

class DateUtils
{
    /**
     * Takes a string representation of the local time in Sweden and returns a DateTime object for this timestamp.
     *
     * @param $date_value
     * @return DateTime|null
     * @throws ValidationException
     */
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
            // TODO: Why return null in one case and throw ValidationException in another case? Fix this inconsistency.
            return null;
        }
    }

    /**
     * Takes a DateTime object and returns a string representing the local time in Sweden at this time.
     *
     * @param $date_time
     * @return string
     */
    public static function to_date_local_value($date_time): string
    {
        $start_date_value = '';
        if ($date_time !== null) {
            $start_date = new DateTime('@' . $date_time->getTimestamp());
            $start_date->setTimezone(new DateTimeZone('Europe/Stockholm'));
            // TODO: from_date_local_value handles both a space and a T as the separator between date and time. Should to_date_local_value do something similar?
            $start_date_value = $start_date->format("Y-m-d\TH:i");
        }
        return $start_date_value;
    }

}