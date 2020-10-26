<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

if (!function_exists('writeJson')) {
    /**
     * 返回 json 格式
     *
     * @param $args
     * @return false|string
     */
    function writeJson($args)
    {
        return json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('buildLogMsg')) {
    /**
     * 客製 log 訊息
     *
     * @param string $msg
     * @param string $data
     * @return string
     */
    function buildLogMsg(string $msg, string $data = '')
    {
        return $data ? "[ $msg ]: $data" : "[ $msg ]";
    }
}

if (!function_exists('ObjToArr')) {
    function objToArr(object $obj)
    {
        $obj = (array)$obj;
        $arr = [];

        foreach ($obj as $k => $v) {
            $arr[] = $v;
        }

        return $arr;
    }
}

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