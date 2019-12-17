<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

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

        switch ($type) {
            case 'track':
                foreach ($musicInfo as $k => $v) {
                    $musicInfoComponents = [];
                    $bodyComponents = [];

                    // 歌名
                    $musicInfoComponents[] = $this->componentService->createLgTextComponent($v->name);
                    // 歌手名
                    $musicInfoComponents[] = $this->componentService->createSmTextComponent($v->album->artist->name);
                    // 試聽按鈕
                    $musicInfoComponents[] = $btn;

                    // 專輯圖片
                    $bodyComponents[] = $this->componentService->createMusicImgComponent($v->album->images[1]->url);
                    // 歌名 + 歌手 + 按鈕
                    $bodyComponents[] = $this->componentService->createMusicInfoComponent($musicInfoComponents);

                    // body
                    $body = $this->componentService->createBodyComponent($bodyComponents);
                    // bubble
                    $bubbles[] = $this->componentService->createBubbleContainer($body);

                }
                break;
            case 'artist':
                foreach ($musicInfo as $k => $v) {
                    $musicInfoComponents = [];
                    $bodyComponents = [];

                    // 歌手名
                    $musicInfoComponents[] = $this->componentService->createLgTextComponent($v->name);
                    // 顯示相關歌曲按鈕
                    $musicInfoComponents[] = $btn;

                    // 歌手圖片
                    $bodyComponents[] = $this->componentService->createMusicImgComponent($v->images[1]->url);
                    // 歌手 + 按鈕
                    $bodyComponents[] = $this->componentService->createMusicInfoComponent($musicInfoComponents);

                    // body
                    $body = $this->componentService->createBodyComponent($bodyComponents);
                    // bubble
                    $bubbles[] = $this->componentService->createBubbleContainer($body);
                }
                break;
            case 'album':
                foreach ($musicInfo as $k => $v) {
                    $musicInfoComponents = [];
                    $bodyComponents = [];

                    // 專輯名
                    $musicInfoComponents[] = $this->componentService->createLgTextComponent($v->name);
                    // 歌手名
                    $musicInfoComponents[] = $this->componentService->createSmTextComponent($v->artist->name);
                    // 顯示相關歌曲按鈕
                    $musicInfoComponents[] = $btn;

                    // 專輯圖片
                    $bodyComponents[] = $this->componentService->createMusicImgComponent($v->images[1]->url);
                    // 專輯 + 歌手 + 按鈕
                    $bodyComponents[] = $this->componentService->createMusicInfoComponent($musicInfoComponents);

                    // body
                    $body = $this->componentService->createBodyComponent($bodyComponents);
                    // bubble
                    $bubbles[] = $this->componentService->createBubbleContainer($body);
                }
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