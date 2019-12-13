<?php

namespace App\Services;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\RichMenuBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBoundsBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuSizeBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class RichMenuService
{
    protected $httpClient;
    protected $lineBot;

    public function __construct()
    {
        $this->httpClient = new CurlHTTPClient(config('line.token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('line.secret')]);
    }

    public function create()
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

    public function delete()
    {
        $this->lineBot->unlinkRichMenu(config('line.user'));
        $this->lineBot->deleteRichMenu(config('line.user'));
    }
}