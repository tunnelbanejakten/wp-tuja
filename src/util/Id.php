<?php

namespace tuja\util;

class Id
{
    public function random_string($length)
    {
        return $this->random_str($length);
    }

    // https://stackoverflow.com/a/31107425
    private function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz')
    {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces [] = $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

}