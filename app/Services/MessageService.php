<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
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
    public function createText(string $text)
    {
        return new TextMessageBuilder($text);
    }

    /**
     * 建立歌曲 flex message
     *
     * @param array $tracksInAlbum
     * @param array $tracks
     * @return FlexMessageBuilder
     */
    public function createTrackFlex(array $tracks, array $tracksInAlbum = [])
    {
        $bubbles = [];

        foreach ($tracks as $track) {
            $musicBoxes = [
                $this->componentService->createLgText($track['trackName']), // 歌名
                $this->componentService->createSmText($track['artistName']), // 歌手名
                $this->componentService->createTrackBtn($track['postbackData'])
            ];

            $bodyBoxes = [
                $this->componentService->createImg($track['albumImg']), // 專輯圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 建立歌曲 flex message ( 顯示專輯歌曲 )
     *
     * @param array $musicData
     * @param array $tracks
     * @return FlexMessageBuilder
     */
    public function createFindTrackFlex(array $musicData, array $tracks)
    {
        $bubbles = [];

        foreach ($tracks as $track) {
            $postbackData = [
                'area'       => 'flexMessage',
                'type'       => 'preview',
                'trackId'    => $track->id,
                'previewUrl' => getPreviewUrl($track->url)
            ];

            // 順序需為 lg ( 歌名 ) -> sm ( 歌手名 ) -> btn ( 按鈕 )
            $musicBoxes = [
                $this->componentService->createLgText($track->name),
                $this->componentService->createSmText($musicData['artistName']),
                $this->componentService->createTrackBtn($postbackData)
            ];

            // 順序需為 img ( 專輯圖片 ) -> music ( 音樂資訊 + 按鈕 )
            $bodyBoxes = [
                $this->componentService->createImg($musicData['albumImg']),
                $this->componentService->createMusic($musicBoxes)
            ];

            $body = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 建立歌手 flex message ( 透過關鍵字搜尋 )
     *
     * @param array $artists
     * @return FlexMessageBuilder
     */
    public function createArtistFlex(array $artists)
    {
        $bubbles = [];

        foreach ($artists as $artist) {
            $musicBoxes = [
                $this->componentService->createLgText($artist['artistName']), // 歌手名
                $this->componentService->createBtn('顯示歌手專輯', $artist['postbackData'])
            ];

            $bodyBoxes = [
                $this->componentService->createImg($artist['artistImg']), // 歌手圖片
                $this->componentService->createMusic($musicBoxes)
            ];

            $body = $this->componentService->createBody($bodyBoxes);
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        return new FlexMessageBuilder('搜尋結果', $carousel);
    }

    /**
     * 建立專輯 flex message ( 透過關鍵字搜尋 or 顯示歌手專輯 )
     *
     * @param array $albums
     * @return FlexMessageBuilder
     */
    public function createAlbumFlex(array $albums)
    {
        $bubbles = [];

        foreach ($albums as $album) {
            $musicBoxes = [
                $this->componentService->createLgText($album['albumName']), // 專輯名
                $this->componentService->createSmText($album['artistName']), // 歌手名
                $this->componentService->createBtn('顯示專輯歌曲', $album['postbackData'])
            ];

            $bodyBoxs = [
                $this->componentService->createImg($album['albumImg']), // 專輯圖片
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
    public function createAudio(string $url)
    {
        return new AudioMessageBuilder($url, 30 * 1000);
    }
}
