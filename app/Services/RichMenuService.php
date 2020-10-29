<?php

namespace App\Services;

use App\Exceptions\CustomException;
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

    const WIDTH        = 2500;
    const HEIGHT       = 843;
    const SEARCH_TYPES = ['artist' => '歌手', 'track' => '歌曲', 'album' => '專輯'];

    public function __construct()
    {
        $this->httpClient = new CurlHTTPClient(config('bot.line_token'));
        $this->lineBot    = new LINEBot($this->httpClient, ['channelSecret' => config('bot.line_secret')]);
    }

    /**
     * 建立 rich menu
     */
    public function create()
    {
        $size    = new RichMenuSizeBuilder(self::HEIGHT, self::WIDTH);
        $width   = intval(self::WIDTH / count(self::SEARCH_TYPES)); // 以寬度為基準分成三個區塊
        $xOffset = 0;
        $areas   = [];

        foreach (self::SEARCH_TYPES as $k => $v) {
            $bound   = new RichMenuAreaBoundsBuilder($xOffset, 0, $width, self::HEIGHT);
            $action  = new PostbackTemplateActionBuilder($k, writeJson(['type' => $k]), '請輸入' . $v . '名');
            $areas[] = new RichMenuAreaBuilder($bound, $action);
            $xOffset += $width;
        }

        $richMenu   = new RichMenuBuilder($size, true, 'linebot-music', '選擇搜尋範圍', $areas);
        $response   = $this->lineBot->createRichMenu($richMenu)->getJSONDecodedBody(); // create 完會回傳一組 richMenuId
        $richMenuId = $response['richMenuId'];

        $this->lineBot->uploadRichMenuImage($richMenuId, 'public/rich_menu_img/line-rich-menu.png', 'image/png');
        $this->lineBot->setDefaultRichMenuId($richMenuId); // 設為預設 rich menu ( 每個 user 的聊天介面都會顯示 )
    }

    /**
     * 刪除全部 rich menu
     */
    public function delete()
    {
        $menus = $this->lineBot->getRichMenuList()->getJSONDecodedBody();
        $menus = $menus['richmenus'];

        foreach ($menus as $menu) {
            $this->lineBot->deleteRichMenu($menu['richMenuId']);
        }
    }

    /**
     * 綁定特定 rich menu 到特定 user
     *
     * @param string $userId
     * @param string $richMenuName
     */
    /*
    public function link(string $userId, string $richMenuName)
    {
        $menus = $this->lineBot->getRichMenuList()->getJSONDecodedBody();
        $menus = $menus['richmenus'];

        foreach ($menus as $menu) {
            if ($menu['name'] === $richMenuName) {
                // 不同 user 連結到不同的 rich menu ( link 完成後就可以用 getRichMenuId() 取得 richMenuId )
                $this->lineBot->linkRichMenu($userId, $menu['richMenuId']);
                break;
            }
        }
    }
    */

    /**
     * 取消 rich menu 和 user 的綁定
     *
     * @param string $userId
     */
    /*
    public function unlink(string $userId)
    {
        $this->lineBot->unlinkRichMenu($userId);
    }
    */
}
