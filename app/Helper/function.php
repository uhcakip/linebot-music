<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

if (!function_exists('addDefaultKeys')) {
    function addDefaultKeys(array $args)
    {
        $defaults = [
            'order' => 'updated_at',
            'sort'  => 'desc',
            'skip'  => 0,
            'take'  => Arr::get($args, 'take', 100000),
        ];

        return $args + $defaults;
    }
}

/*
if (!function_exists('filterAlbumInfo')) {
    function filterAlbumInfo(array $args)
    {
        $last = [];
        $albumName = '';

        foreach ($args as $k => $v) {
            if (count($last) === 5) break;
            if ($albumName !== $v->name) $last[$k] = $v;
            $albumName = $v->name;
        }

        return $last;
    }
}
*/