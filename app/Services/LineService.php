<?php

namespace App\Services;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\FillerComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\RichMenuBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBoundsBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuSizeBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class LineService
{
    protected $httpClient;
    protected $lineBot;

    public function __construct()
    {
        $this->httpClient = new CurlHTTPClient(config('line.token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('line.secret')]);
    }

    public function createRichMenu()
    {
        // size
        $size = new RichMenuSizeBuilder(843, 2500);
        // bounds
        $bounds = [
            new RichMenuAreaBoundsBuilder(0, 0, 833, 843),
            new RichMenuAreaBoundsBuilder(833, 0, 833, 843),
            new RichMenuAreaBoundsBuilder(1666, 0, 833, 843),
        ];
        // actions
        $actions = [
            new PostbackTemplateActionBuilder('artist', 'artist', '請輸入歌手名'),
            new PostbackTemplateActionBuilder('track', 'track', '請輸入歌曲名'),
            new PostbackTemplateActionBuilder('album', 'album', '請輸入專輯名'),
        ];
        // areas
        $areas = [];
        for ($i = 0; $i < count($bounds); $i++) {
            $areas[] = new RichMenuAreaBuilder($bounds[$i], $actions[$i]);
        }

        // create
        $richMenu = new RichMenuBuilder($size, true, 'linebot-music', '選擇搜尋範圍', $areas);
        $response = $this->lineBot->createRichMenu($richMenu);
        // 建立完會回傳一組 richMenuId
        $richMenuId = $response->getJSONDecodedBody()['richMenuId'];

        // upload image
        $this->lineBot->uploadRichMenuImage($richMenuId, 'public/rich_menu_img/line-rich-menu.png', 'image/png');

        // link -> 完成後就可以用 getRichMenuId() 取得 richMenuId
        $this->lineBot->linkRichMenu(config('line.user'), $richMenuId);
    }

    public function deleteRichMenu()
    {
        $this->lineBot->unlinkRichMenu(config('line.user'));
        $this->lineBot->deleteRichMenu(config('line.user'));
    }

    public function createTextMsg(string $text)
    {
        return new TextMessageBuilder($text);
    }

    public function createFlexMsg()
    {
        // 試聽按鈕
        $previewTextComponent = new TextComponentBuilder('試聽');
        $previewTextComponent->setColor('#ffffff')->setAlign('center')->setOffsetTop('7.5px');
        $previewBtn = new BoxComponentBuilder('vertical', [$previewTextComponent], null, 'sm', 'xxl');
        $previewBtn->setHeight('40px')->setBorderWidth('1px')->setBorderColor('#ffffff')->setCornerRadius('4px')->setOffsetTop('4px');

        // 專輯名稱
        $albumTextComponent = new TextComponentBuilder('SQUARE UP');
        $albumTextComponent->setSize('sm')->setColor('#ebebeb');
        $albumName = new BoxComponentBuilder('baseline', [$albumTextComponent], null, 'lg');
        $albumName->setOffsetTop('15px');

        $trackTextComponent = new TextComponentBuilder('DDU-DU DDU-DU');
        $trackTextComponent->setSize('lg')->setColor('#ffffff')->setWeight('bold');
        $trackName = new BoxComponentBuilder('vertical', [$trackTextComponent]);
        $trackName->setOffsetTop('10px');

        $musicInfo = new BoxComponentBuilder('vertical', [$trackName, $albumName, $previewBtn]);
        $musicInfo->setPosition('absolute')->setBackgroundColor('#111111cc')->setOffsetBottom('0px')->setOffsetStart('0px')->setOffsetEnd('0px')->setPaddingAll('20px')->setPaddingTop('0px');

        $musicImg = new ImageComponentBuilder('https://i.kfs.io/album/global/36022022,4v1/fit/500x500.jpg', null, null, null, 'top', 'full', '2:3', 'cover');

        $body = new BoxComponentBuilder('vertical', [$musicImg, $musicInfo]);
        $body->setPaddingAll('0px');

        $bubble = new BubbleContainerBuilder(null, null, null, $body);

        $carousel = new CarouselContainerBuilder([$bubble, $bubble, $bubble]);

        return new FlexMessageBuilder('Computer Ver', $carousel);
    }

    public function replyMessage(string $replyToken, MessageBuilder $msgBuilder)
    {
        return $this->lineBot->replyMessage($replyToken, $msgBuilder);
    }

}