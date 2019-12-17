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

    public function getMusicByKeyword(string $type, array $args)
    {
        $response = $this->kkbox->search($args['message.text'], [$type], Territory::Taiwan, 0, 5);
        $attributes = $type . 's';
        $result = json_decode($response->getBody())->$attributes->data;
        // Log::info(print_r($result, true));

        return $result;
    }

    // 顯示歌手專輯
    public function getAlbumByArtistId(string $id)
    {
        $response = $this->kkbox->fetchAlbumsOfArtist($id);
        $result = json_decode($response->getBody())->data;
        // 取最新前五張專輯 ( 專輯名不重複 )
        $last = Arr::sort(array_slice($result, -5));
        // Log::info(print_r($last, true));

        return $last;
    }

    // 顯示專輯歌曲
    public function getTrackByAlbumId(string $id)
    {
        $response = $this->kkbox->fetchTracksInAlbum($id);
        $result = json_decode($response->getBody())->data;
        // Log::info(print_r($result, true));

        // 取專輯前五首歌
        return array_slice($result, 0, 5);
    }
}