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
        Log::info(print_r($result, true));

        switch ($type) {
            case 'track':

        }

        /**
         * tracks:
         * ->data[key]
         *
         * ->name(歌名)
         * ->album->name(專輯名)
         * ->album->images[1]->url(專輯圖片 500x500)
         * ->artist->name(歌手名)
         *
         * artists:
         * ->data[key]
         *
         * ->name(歌手名)
         * ->images[1]->url(歌手圖片 300x300)
         *
         *
         * albums
         * ->data[key]
         *
         * ->name(專輯名)
         * ->images[1]->url(專輯圖片 500x500)
         * ->artist->name(歌手名)
         *
         */
    }
}