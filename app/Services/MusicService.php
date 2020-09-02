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
        $this->kkbox = new OpenAPI(config('kkbox.kkbox_id'), config('kkbox.kkbox_secret'));
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
        $response  = $this->kkbox->search($keyword, [$type], Territory::Taiwan, 0, 30);
        $attribute = $type . 's';

        $results = json_decode($response->getBody())->$attribute->data;
        $results = collect($results)->filter(function ($result) { // 地區必須包含 TW 才有權限取得音樂資訊
            return in_array('TW', $result->available_territories);
        });

        //Log::info(ucfirst($type) . ' search result: ' . writeJson($results->all()));

        return $results->take($results->count() < 5 ? $results->count() : 5)->all();
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

        $results = json_decode($response->getBody())->data;
        $results = collect($results)->filter(function ($result) { // 地區必須包含 TW 才有權限取得音樂資訊
            return in_array('TW', $result->available_territories);
        });

        // 隨機取 5 張專輯
        return $results->random($results->count() < 5 ? $results->count() : 5)->all();
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
        $result   = json_decode($response->getBody())->data;

        // 隨機取 5 首歌
        return Arr::random($result, (count($result) < 5 ? count($result) : 5));
    }
}
