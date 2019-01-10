<?php

namespace WikivoyageApi;

class ArrayUtils
{
    public static function getFirstNotNullValue($values, $defaultValue)
    {
        foreach ($values as $value) {
            if (!is_null($value)) {
                return $value;
            }
        }

        return $defaultValue;
    }

    public static function getNonEmptyStringValue($array, $key)
    {
        if (isset($array[$key])) {
            $value = (string)$array[$key];
            if ($value !== '') {
                return $value;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
