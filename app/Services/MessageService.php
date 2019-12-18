<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class MessageService
{
    protected $componentService;

    protected $httpClient;
    protected $lineBot;

    public function __construct(ComponentService $componentService)
    {
        // 注入
        $this->componentService = $componentService;

        $this->httpClient = new CurlHTTPClient(config('line.token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('line.secret')]);
    }

    public function createText(string $text)
    {
        return new TextMessageBuilder($text);
    }

    public function createTrackFlex(string $info, array $tracks)
    {
        $bubbles = [];
        // 顯示專輯歌曲
        $infoArr = $info ? explode('|', $info) : [];

        foreach ($tracks as $k => $v) {
            // 順序需為 lg -> sm -> btn
            $musicBoxs = [];
            // 順序需為 img -> music
            $bodyBoxs = [];

            // 歌名
            $musicBoxs[] = $this->componentService->createLgText($v->name);
            // 歌手名
            $musicBoxs[] = $this->componentService->createSmText($infoArr ? $infoArr[2] : $v->album->artist->name);
            // 按鈕
            $musicBoxs[] = $this->componentService->createBtn('試聽', 'preview|' . $v->url);

            // 專輯圖片
            $bodyBoxs[] = $this->componentService->createImg($infoArr ? $infoArr[3] : $v->album->images[1]->url);
            // 音樂資訊 + 按鈕
            $bodyBoxs[] = $this->componentService->createMusic($musicBoxs);

            // 組成 body
            $body = $this->componentService->createBody($bodyBoxs);

            // 組成 bubble
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        // Log::info(print_r($carousel, true));

        return new FlexMessageBuilder('查詢結果', $carousel);
    }

    public function createArtistFlex(array $artists)
    {
        $bubbles = [];

        foreach ($artists as $k => $v) {
            // 順序需為 lg -> btn
            $musicBoxs = [];
            // 順序需為 img -> music
            $bodyBoxs = [];

            // 歌手名
            $musicBoxs[] = $this->componentService->createLgText($v->name);
            // 按鈕
            $musicBoxs[] = $this->componentService->createBtn('顯示歌手專輯', 'find_album|' . $v->id);

            // 歌手圖片
            $bodyBoxs[] = $this->componentService->createImg($v->images[1]->url);
            // 音樂資訊 + 按鈕
            $bodyBoxs[] = $this->componentService->createMusic($musicBoxs);

            // 組成 body
            $body = $this->componentService->createBody($bodyBoxs);

            // 組成 bubble
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        // Log::info(print_r($carousel, true));

        return new FlexMessageBuilder('查詢結果', $carousel);
    }

    public function createAlbumFlex(array $albums)
    {
        $bubbles = [];

        foreach ($albums as $k => $v) {
            // 順序需為 lg -> sm -> btn
            $musicBoxs = [];
            // 順序需為 img -> music
            $bodyBoxs = [];

            // 專輯名
            $musicBoxs[] = $this->componentService->createLgText($v->name);
            // 歌手名
            $musicBoxs[] = $this->componentService->createSmText($v->artist->name);
            // 按鈕
            $musicBoxs[] = $this->componentService->createBtn(
                '顯示專輯歌曲',
                'find_track|' . $v->id . '|' . $v->artist->name . '|' . $v->images[1]->url
            );

            // 專輯圖片
            $bodyBoxs[] = $this->componentService->createImg($v->images[1]->url);
            // 音樂資訊 + 按鈕
            $bodyBoxs[] = $this->componentService->createMusic($musicBoxs);

            // 組成 body
            $body = $this->componentService->createBody($bodyBoxs);

            // 組成 bubble
            $bubbles[] = $this->componentService->createBubble($body);
        }

        $carousel = $this->componentService->createCarousel($bubbles);
        // Log::info(print_r($carousel, true));

        return new FlexMessageBuilder('查詢結果', $carousel);
    }

    public function reply(string $replyToken, MessageBuilder $msgBuilder)
    {
        return $this->lineBot->replyMessage($replyToken, $msgBuilder);
    }
}