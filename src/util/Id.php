<?php

namespace tuja\util;

class Id
{
    const RANDOM_CHARS = '0123456789abcdefghijklmnopqrstuvwxyz';
    const RANDOM_DIGITS = '0123456789';
    const RANDOM_UNAMBIGUOUS_LETTERS = 'qwrtpsdfghjkzxcvbnm';

    const LENGTH = 10;

    public function random_string($length = self::LENGTH)
    {
        return $this->random_str($length);
    }

    public function random_digits($length = self::LENGTH)
    {
        return $this->random_str($length, self::RANDOM_DIGITS);
    }

    public function random_unambiguous_letters($length = self::LENGTH)
    {
        return $this->random_str($length, self::RANDOM_UNAMBIGUOUS_LETTERS);
    }

    // https://stackoverflow.com/a/31107425
    private function random_str($length, $keyspace = self::RANDOM_CHARS)
    {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces [] = $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

}