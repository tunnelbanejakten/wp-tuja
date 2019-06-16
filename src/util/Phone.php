<?php

namespace tuja\util;


class Phone
{
    static function fix_phone_number($number)
    {
        $no_leading_zero = ltrim(trim(preg_replace('/[^0-9+]/', '', $number)), '0');
        if (@$no_leading_zero[0] == '7') {
            return "+46$no_leading_zero";
        } else if (substr($no_leading_zero, 0, 2) == '46') {
            return "+$no_leading_zero";
        } else {
            return $no_leading_zero;
        }
    }
}