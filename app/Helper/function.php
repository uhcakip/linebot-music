<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use KKBOX\KKBOXOpenAPI\OpenAPI;
use KKBOX\KKBOXOpenAPI\Territory;

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

if (!function_exists('getMusicInfo')) {
    function getMusicInfo(string $type, array $args)
    {
        // initialize
        $kkbox = new OpenAPI(config('kkbox.id'), config('kkbox.secret'));
        $kkbox->fetchAndUpdateAccessToken();

        $response = $kkbox->search($args['message.text'], [$type], Territory::Taiwan, 0, 5);
        $attributes = $type . 's';
        $result = json_decode($response->getBody())->$attributes->data;
        // Log::info(print_r($result, true));
        return $result;
    }
}