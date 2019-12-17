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

    public function createTextMsg(string $text)
    {
        return new TextMessageBuilder($text);
    }

    public function createFlexMsg(string $type, array $musicInfo)
    {
        $bubbles = [];
        $btn = $this->componentService->createBtnComponent($type);

        foreach ($musicInfo as $k => $v) {
            // 順序需為 lg -> sm -> btn
            $musicComponents = [];
            $bodyComponents = [];

            // 依據 type 顯示歌名 or 歌手名 or 專輯名
            $musicComponents[] = $this->componentService->createLgTextComponent($v->name);

            if ($type === 'track') {
                // 歌手名
                $musicComponents[] = $this->componentService->createSmTextComponent($v->album->artist->name);
            }
            if ($type === 'album') {
                // 歌手名
                $musicComponents[] = $this->componentService->createSmTextComponent($v->artist->name);
            }

            // 歌手圖片 or 專輯圖片
            $bodyComponents[] = $type === 'track'
                              ? $this->componentService->createMusicImgComponent($v->album->images[1]->url)
                              : $this->componentService->createMusicImgComponent($v->images[1]->url);

            // 按鈕
            $musicComponents[] = $btn;

            // 音樂資訊 + 按鈕
            $bodyComponents[] = $this->componentService->createMusicInfoComponent($musicComponents);

            // body
            $body = $this->componentService->createBodyComponent($bodyComponents);

            // bubble
            $bubbles[] = $this->componentService->createBubbleContainer($body);
        }

        $carousel = $this->componentService->createCarouselContainer($bubbles);
        // Log::info(print_r($carousel, true));

        return new FlexMessageBuilder('查詢結果', $carousel);
    }

    public function replyMessage(string $replyToken, MessageBuilder $msgBuilder)
    {
        return $this->lineBot->replyMessage($replyToken, $msgBuilder);
    }


}