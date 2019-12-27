<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
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
        $this->httpClient = new CurlHTTPClient(config('bot.line_token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('bot.line_secret')]);
    }

    /**
     * 建立 Rich Menu
     */
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
        // build
        $richMenu = new RichMenuBuilder($size, true, 'linebot-music', '選擇搜尋範圍', $areas);

        // create
        $response = $this->lineBot->createRichMenu($richMenu);
        // 建立完會回傳一組 richMenuId
        $richMenuId = $response->getJSONDecodedBody()['richMenuId'];
        // upload image
        $this->lineBot->uploadRichMenuImage($richMenuId, 'public/rich_menu_img/line-rich-menu.png', 'image/png');
        // 設為預設 rich menu ( 每個 user 的聊天介面都會顯示 )
        $this->lineBot->setDefaultRichMenuId($richMenuId);
    }

    /**
     * 綁定特定 rich menu 到特定 user
     *
     * @param string $userId
     * @param string $richMenuName
     */
    public function link(string $userId, string $richMenuName)
    {
        $list = $this->lineBot->getRichMenuList()->getJSONDecodedBody();
        foreach ($list['richmenus'] as $v) {
            if ($v['name'] === $richMenuName) {
                // 不同 user 連結到不同的 rich menu ( link 完成後就可以用 getRichMenuId() 取得 richMenuId )
                $this->lineBot->linkRichMenu($userId, $v['richMenuId']);
                break;
            }
        }
    }

    /**
     * 取消 rich menu 和 user 的綁定
     *
     * @param string $userId
     */
    public function unlink(string $userId)
    {
        $this->lineBot->unlinkRichMenu($userId);
    }

    /**
     * 刪除單一 rich menu
     *
     * @param string $richMenuName
     */
    public function delete(string $richMenuName)
    {
        $list = $this->lineBot->getRichMenuList()->getJSONDecodedBody();
        foreach ($list['richmenus'] as $v) {
            if ($v['name'] === $richMenuName) {
                $this->lineBot->deleteRichMenu($richMenuName);
                break;
            }
        }
    }

    /**
     * 刪除全部 rich menu
     */
    public function deleteAll()
    {
        $list = $this->lineBot->getRichMenuList()->getJSONDecodedBody();
        foreach ($list['richmenus'] as $v) {
            $this->lineBot->deleteRichMenu($v['richMenuId']);
        }
    }
}