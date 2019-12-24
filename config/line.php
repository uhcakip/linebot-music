<?php

return [
    // basic setting
    'token' => env('LINEBOT_TOKEN', ''),
    'id' => env('LINEBOT_CHANNEL_ID', ''),
    'secret' => env('LINEBOT_CHANNEL_SECRET', ''),
    'user' => env('LINEBOT_USER_ID', ''),
    'rich_menu_id' => env('LINEBOT_RICH_MENU_ID', ''),

    // rich menu 搜尋範圍
    'type' => [
        'track'  => '歌曲',
        'artist' => '歌手',
        'album'  => '專輯',
    ],

    // flex message
    'main_color' => '#ffffff',
];