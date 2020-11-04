<?php

namespace App\Services;

use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class MessageService
{
    protected $componentService;

    public function __construct(ComponentService $componentService)
    {
        // 注入
        $this->componentService = $componentService;
    }

    /**
     * 建立文字訊息
     *
     * @param string $text
     * @return TextMessageBuilder
     */
    public function createTextMessage(string $text)
    {
        return new TextMessageBuilder($text);
    }

    /**
     * 建立歌曲 flex message
     *
     * @param array $tracks
     * @param array $albumInfo
     * @return FlexMessageBuilder
     */
    public function createTrackFlexMessage(array $tracks, array $albumInfo = [])
    {
        if (!$albumInfo) { // 關鍵字搜尋
            $bubbles = $this->buildTrackBubbles($tracks);

        } else { // 專輯歌曲
            $bubbles = $this->buildTrackBubblesWithAlbumInfo($tracks, $albumInfo);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 組成歌曲 bubbles ( 關鍵字搜尋 )
     *
     * @param array $tracks
     * @return array
     */
    private function buildTrackBubbles(array $tracks)
    {
        $bubbles = [];

        foreach ($tracks as $track) {
            $albumImg    = $track['album']['images'][1]['url'] ?? '';
            $territories = $track['available_territories'] ?? [];

            if (count($bubbles) >= 5) { // 只需要 5 組
                break;
            }
            if (!$albumImg || !$territories || !in_array('TW', $territories)) { // 找不到符合尺寸的圖片或權限不包含 tw 就跳過
                continue;
            }

            $trackId    = $track['id'];
            $trackName  = $track['name'];
            $artistName = $track['album']['artist']['name'];
            $previewUrl = getPreviewUrl($track['url']);
            $data       = ['area' => 'flexMessage', 'type' => 'preview'] + compact('trackId', 'previewUrl');

            $musicBoxes = [
                $this->componentService->createLgText($trackName), // 歌名
                $this->componentService->createSmText($artistName), // 歌手名
                $this->componentService->createTrackBtn($data)
            ];
            $bodyBoxes  = [
                $this->componentService->createImg($albumImg), // 專輯圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body      = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        return $bubbles;
    }

    /**
     * 組成歌曲 bubbles ( 專輯歌曲 )
     *
     * @param array $tracks
     * @param array $albumInfo
     * @return array
     */
    private function buildTrackBubblesWithAlbumInfo(array $tracks, array $albumInfo)
    {
        $bubbles = [];

        foreach ($tracks as $track) {
            $territories = $track['available_territories'] ?? [];

            if (count($bubbles) >= 5) { // 只需要 5 組
                break;
            }
            if (!$territories || !in_array('TW', $territories)) { // 找不到符合尺寸的圖片或權限不包含 tw 就跳過
                continue;
            }

            $trackId    = $track['id'];
            $trackName  = $track['name'];
            $previewUrl = getPreviewUrl($track['url']);
            $data       = ['area' => 'flexMessage', 'type' => 'preview'] + compact('trackId', 'previewUrl');

            $musicBoxes = [
                $this->componentService->createLgText($trackName), // 歌名
                $this->componentService->createSmText($albumInfo['artistName']), // 歌手名
                $this->componentService->createTrackBtn($data)
            ];
            $bodyBoxes  = [
                $this->componentService->createImg($albumInfo['albumImg']), // 專輯圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body      = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        return $bubbles;
    }

    /**
     * 建立歌手 flex message
     *
     * @param array $artists
     * @return FlexMessageBuilder
     */
    public function createArtistFlexMessage(array $artists)
    {
        $bubbles = [];

        foreach ($artists as $artist) {
            if (count($bubbles) >= 5) { // 只需要 5 組
                break;
            }
            if (!isset($artist['images'][1]['url'])) { // 找不到符合尺寸的圖片就跳過
                continue;
            }

            $artistId   = $artist['id'];
            $artistName = $artist['name'];
            $artistImg  = $artist['images'][1]['url'];
            $data       = ['area' => 'flexMessage', 'type' => 'AlbumsOfArtist'] + compact('artistId');

            $musicBoxes = [
                $this->componentService->createLgText($artistName), // 歌手名
                $this->componentService->createBtn('顯示歌手專輯', $data)
            ];
            $bodyBoxes  = [
                $this->componentService->createImg($artistImg), // 歌手圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body      = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 建立專輯 flex message
     *
     * @param array $albums
     * @return FlexMessageBuilder
     */
    public function createAlbumFlexMessage(array $albums)
    {
        $bubbles = [];

        foreach ($albums as $album) {
            $albumImg    = $album['images'][1]['url'] ?? '';
            $territories = $album['available_territories'] ?? [];

            if (count($bubbles) >= 5) { // 只需要 5 組
                break;
            }
            if (!$albumImg || !$territories || !in_array('TW', $territories)) { // 找不到符合尺寸的圖片或權限不包含 tw 就跳過
                continue;
            }

            $albumId    = $album['id'];
            $albumName  = $album['name'];
            $artistName = $album['artist']['name'];
            $data       = ['area' => 'flexMessage', 'type' => 'tracksInAlbum'] + compact('albumId', 'artistName', 'albumImg');

            $musicBoxes = [
                $this->componentService->createLgText($albumName), // 專輯名
                $this->componentService->createSmText($artistName), // 歌手名
                $this->componentService->createBtn('顯示專輯歌曲', $data)
            ];
            $bodyBoxs  = [
                $this->componentService->createImg($albumImg), // 專輯圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body = $this->componentService->createBody($bodyBoxs);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 建立聲音訊息
     *
     * @param string $url
     * @return AudioMessageBuilder
     */
    public function createAudioMessage(string $url)
    {
        return new AudioMessageBuilder($url, 30 * 1000);
    }
}
