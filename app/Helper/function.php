<?php

use Illuminate\Support\Arr;

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