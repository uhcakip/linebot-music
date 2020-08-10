<?php

return [
    // basic setting
    'line_token'  => env('LINEBOT_TOKEN', ''),
    'line_id'     => env('LINEBOT_CHANNEL_ID', ''),
    'line_secret' => env('LINEBOT_CHANNEL_SECRET', ''),
    'line_user'   => env('LINEBOT_USER_ID', ''),

    // rich menu 搜尋範圍
    'type' => [
        'artist' => '歌手',
        'track'  => '歌曲',
        'album'  => '專輯',
    ],

    // flex message
    'main_color' => '#ffffff',
];
