<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use KKBOX\KKBOXOpenAPI\OpenAPI;
use KKBOX\KKBOXOpenAPI\Territory;

class MusicService
{
    protected $kkbox;

    public function __construct()
    {
        $this->kkbox = new OpenAPI(config('kkbox.id'), config('kkbox.secret'));
        $this->kkbox->fetchAndUpdateAccessToken();
    }

    /**
     * 用關鍵字搜尋 ( 限定搜尋範圍 )
     *
     * @param string $type
     * @param string $keyword
     * @return mixed
     */
    public function getResult(string $type, string $keyword)
    {
        $response = $this->kkbox->search($keyword, [$type], Territory::Taiwan, 0, 5);
        $attributes = $type . 's';
        $result = json_decode($response->getBody())->$attributes->data;
        // Log::info(print_r($result, true));

        return $result;
    }

    /**
     * 用 artist id 搜尋歌手專輯
     *
     * @param string $artistId
     * @return mixed
     */
    public function getAlbums(string $artistId)
    {
        $response = $this->kkbox->fetchAlbumsOfArtist($artistId);
        $result = json_decode($response->getBody())->data;
        // 地區必須包含 TW 才有權限取得音樂資訊
        $filtered = Arr::where($result, function ($v) {
            return in_array('TW', $v->available_territories);
        });

        // 隨機取 5 張專輯
        return Arr::random($filtered, count($filtered) < 5 ? count($filtered) : 5);
    }

    /**
     * 用 album id 搜尋專輯歌曲
     *
     * @param string $albumId
     * @return array
     */
    public function getTracks(string $albumId)
    {
        $response = $this->kkbox->fetchTracksInAlbum($albumId);
        $result = json_decode($response->getBody())->data;

        // 隨機取 5 首歌
        return Arr::random($result, count($result) < 5 ? count($result) : 5);
    }
}