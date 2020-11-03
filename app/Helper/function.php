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
    /**
     * 物件轉陣列
     *
     * @param object $obj
     * @return array
     */
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

        preg_match('/<meta property="music:preview_url:secure_url" content="(.+)"/', $html, $matches);

        return $matches[1];
    }
}

if (!function_exists('storeTrack')) {
    /**
     * 儲存 + 回傳試聽音檔路徑
     *
     * @param string $trackId
     * @param string $previewUrl
     * @return string
     */
    function storeTrack(string $trackId, string $previewUrl)
    {
        $storePath = $trackId . '.mp3';

        if (Storage::disk('tracks')->missing($storePath)) {
            Storage::disk('tracks')->put($storePath, file_get_contents($previewUrl));
        }

        return asset('tracks/' . $storePath);
    }
}