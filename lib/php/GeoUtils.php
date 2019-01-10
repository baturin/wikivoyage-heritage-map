<?php

namespace WikivoyageApi;

class GeoUtils
{
    public static function swapLatLong($data)
    {
        return array_map(function($item) {
            return [$item[1], $item[0]];
        }, $data);
    }
}
