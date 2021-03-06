<?php

namespace tuja\util;

class Id
{
    const RANDOM_CHARS = '0123456789abcdefghijklmnopqrstuvwxyz';

    const LENGTH = 10;

    public function random_string($length = self::LENGTH)
    {
        return $this->random_str($length);
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