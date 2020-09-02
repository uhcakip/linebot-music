<?php

use GuzzleHttp\Client;

if (!function_exists('getDefalutColumns')) {
    /**
     * repo 加入預設欄位
     *
     * @return array
     */
    function getDefalutColumns()
    {
        return [
            'order' => 'updated_at',
            'sort'  => 'desc',
            'skip'  => 0,
            'take'  => 100000
        ];
    }
}

if (!function_exists('getPreviewUrl')) {
    /**
     * 取得音樂試聽連結
     *
     * @param string $pageUrl
     * @return mixed
     */
    function getPreviewUrl(string $pageUrl)
    {
        $client = new Client();
        $html   = $client->get($pageUrl)->getBody()->getContents();

        preg_match('/<meta property="music:preview_url:url" content="(.+)"/', $html, $matches);

        return $matches[1];
    }
}

if (!function_exists('saveMusic')) {
    /**
     * 儲存、回傳試聽音檔路徑
     *
     * @param string $trackId
     * @param string $previewUrl
     * @return string
     */
    function saveMusic(string $trackId, string $previewUrl)
    {
        $dir  = 'music';
        $path = $dir . '/' . $trackId . '.mp3';

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        if (!file_exists($path)) {
            file_put_contents(public_path($path), file_get_contents($previewUrl));
        }

        return asset($path);
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
