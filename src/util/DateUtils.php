<?php

namespace tuja\util;


use tuja\data\model\Person;
use tuja\data\model\ValidationException;
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

	public static function fix_pno( $input ) {
		if ( empty( trim( $input ) ) ) {
			return null;
		}
		if ( preg_match( '/' . Person::PNO_PATTERN . '/', $input ) !== 1 ) {
			throw new ValidationException( null, 'Ogiltigt datum eller personnummer.' );
		}

		$digits = preg_replace( "/[^0-9]/", "", $input );
		if ( strlen( $digits ) == 6 ) {
			// 831109
			return DateTime::createFromFormat( 'Ymd',
					self::is_20th_century( $digits ) ? "20$digits" : "19$digits"
				)->format( 'Ymd' ) . '-0000';
		} else if ( strlen( $digits ) == 8 ) {
			//19831109
			return DateTime::createFromFormat( 'Ymd',
					$digits
				)->format( 'Ymd' ) . '-0000';
		} else if ( strlen( $digits ) == 10 ) {
			// 8311090123
			// 8311090000
			return DateTime::createFromFormat( 'Ymd',
					substr( self::is_20th_century( $digits ) ? "20$digits" : "19$digits", 0, 8 )
				)->format( 'Ymd' ) . '-' . substr( $digits, 6, 4 );
		} else if ( strlen( $digits ) == 12 ) {
			// 198311090000
			// 198311090123
			return DateTime::createFromFormat( 'Ymd',
					substr( $digits, 0, 8 )
				)->format( 'Ymd' ) . '-' . substr( $digits, 8, 4 );
		} else {
			throw new ValidationException( null, 'Ogiltigt datum eller personnummer.' );
		}
	}

}