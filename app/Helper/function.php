<?php

use GuzzleHttp\Client;
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

if (!function_exists('getPreview')) {
    function getPreview(string $url)
    {
        $client = new Client();
        $html = $client->get($url)->getBody()->getContents();
        preg_match('/<meta property="music:preview_url:url" content="(.+)"/', $html, $matches);
        $preview = file_put_contents(public_path('music/test.mp3'), file_get_contents($matches[1]));
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