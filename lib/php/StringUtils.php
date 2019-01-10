<?php

namespace WikivoyageApi;

class StringUtils
{
    public static function nullStr($str)
    {
        if ($str === null) {
            return '';
        } else {
            return (string)$str;
        }
    }
}
