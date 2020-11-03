<?php

namespace App\Services;

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
     * 關鍵字搜尋 ( 限定搜尋範圍 )
     *
     * @param string $type
     * @param string $keyword
     * @return mixed
     */
    public function searchByKeyword(string $type, string $keyword)
    {
        $response = $this->kkbox->search($keyword, [$type], Territory::Taiwan, 0, 20);
        $response = json_decode($response->getBody(), true);
        $type    .= 's';

        return $response[$type]['data'];
    }

    /**
     * artist id 搜尋歌手專輯
     *
     * @param string $artistId
     * @return mixed
     */
    public function getAlbumsByArtistId(string $artistId)
    {
        $response = $this->kkbox->fetchAlbumsOfArtist($artistId, Territory::Taiwan, 0, 20);
        $albums   = json_decode($response->getBody(), true);

        return $albums['data'];
    }

    /**
     * album id 搜尋專輯歌曲
     *
     * @param string $albumId
     * @return array
     */
    public function getTracksByAlbumId(string $albumId)
    {
        $response = $this->kkbox->fetchTracksInAlbum($albumId);
        $tracks   = json_decode($response->getBody(), true);

        return $tracks['data'];
    }
}
