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
     * @param array $args
     * @return mixed
     */
    public function getResult(string $type, array $args)
    {
        $response = $this->kkbox->search($args['message.text'], [$type], Territory::Taiwan, 0, 5);
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
        $response = $this->kkbox->fetchAlbumsOfArtist($artistId, 'TW');
        $result = json_decode($response->getBody())->data;
        // 地區包含 TW
        $filtered = Arr::where($result, function ($v) {
            $availableArr = $v->available_territories;
            return in_array('TW', $availableArr);
        });

        // 隨機取 5 張專輯
        return Arr::random($filtered, 5);
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
        return Arr::random($result, count($result) >= 5 ? 5 : count($result));
    }
}