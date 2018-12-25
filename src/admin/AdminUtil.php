<?php

namespace admin;


use Exception;

class AdminUtil
{
    public static function printException(Exception $ex)
    {
        printf('<div class="notice notice-success" style="margin-left: 2px"><p>%s</p></div>', $ex->getMessage());
    }
}