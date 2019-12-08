<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\RichMenuBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBoundsBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuSizeBuilder;
use LINE\LINEBot\TemplateActionBuilder\LocationTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

class LineBotService
{
    protected $http_client;
    protected $bot;
    protected $reply_token;   // 回覆需要傳送 reply_token
    protected $user_id;       // 使用者 id，用來取得個人資料 ( 非自己的 )
    // protected $rich_menu_id;  // 用來取得 rich menu

    public function __construct()
    {
        $this->http_client = new CurlHTTPClient(config('line.token'));
        $this->bot = new LINEBot($this->http_client, ['channelSecret' => config('line.secret')]);
        // getRichMenuId() 回傳 LINE\LINEBot\Response 物件
        // $this->rich_menu_id = $this->bot->getRichMenuId(config('line.user'));
    }

    /**
     * 處理使用者傳送的訊息
     * @param $response
     * @param $signature
     */
    public function handle($response, $signature)
    {
        try {
            // 轉為 Event 物件
            $events = $this->bot->parseEventRequest($response, $signature);

            if (!is_array($events)) throw new Exception('Empty $events');

            foreach ($events as $event) {
                $this->reply_token = $event->getReplyToken();
                $this->user_id = $event->getUserId();

                // 取得事件類別
                $type = $event->getType();
                switch ($type) {
                    // 收到訊息
                    case 'message':
                        // 訊息種類
                        $msg_type = $event->getMessageType();
                        // 文字訊息
                        if ($msg_type == 'text') {
                            $input = $event->getText();
                            $this->show($input);
                        }
                        break;

                    // 使用者點選按鈕等
                    case 'postback':
                        $feedback = $event->getPostbackData();
                        $this->bot->replyMessage($this->reply_token, new TextMessageBuilder('你按了 ' . $feedback));
                }
            }

        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }

    }

    public function show($text)
    {
        switch ($text) {
            case '文字':
                $result = $this->bot->replyMessage($this->reply_token, new TextMessageBuilder('文字'));
                if (!$result->isSucceeded()) {
                    throw new Exception('文字顯示失敗 : ' . $result->getRawBody());
                }
                break;

            case '圖片':
                $img_builder = new ImageMessageBuilder(env('IMG_URL'), env('IMG_URL'));

                $result = $this->bot->replyMessage($this->reply_token, $img_builder);
                if (!$result->isSucceeded()) {
                    throw new Exception('圖片顯示失敗 : ' . $result->getRawBody());
                }
                break;

            case '確認視窗':
                $actions = [];
                // 建立動作 ( 按鈕呈現 ) -> 可得知使用者點擊什麼
                $actions[] = new PostbackTemplateActionBuilder('Yes', 'Yes');
                $actions[] = new PostbackTemplateActionBuilder('No', 'No');

                $result = $this->bot->replyMessage($this->reply_token,
                    new TemplateMessageBuilder('顯示文字', new ConfirmTemplateBuilder('請選擇', $actions)));
                if (!$result->isSucceeded()) {
                    throw new Exception('確認視窗顯示失敗 : ' . $result->getRawBody());
                }
                break;

            case '幻燈片':
                $columns = [];
                for ($i = 0; $i < 3; $i++) {
                    $actions = [];
                    // 建立動作 ( 按鈕呈現 ) -> URL
                    $actions[] = new UriTemplateActionBuilder('點我到紅褲', 'https://www.red-digital.com/');
                    $actions[] = new PostbackTemplateActionBuilder('點我', '點我');
                    // 幻燈片模板
                    $column = new CarouselColumnTemplateBuilder('標題', '描述', env('IMG_URL'), $actions);
                    $columns[] = $column;
                }
                // 組合幻燈片
                $carousel = new CarouselTemplateBuilder($columns);
                $tpl_builder = new TemplateMessageBuilder('顯示文字', $carousel);

                $result = $this->bot->replyMessage($this->reply_token, $tpl_builder);
                if (!$result->isSucceeded()) {
                    throw new Exception('幻燈片顯示失敗 : ' . $result->getRawBody());
                }
                break;

            case '圖片幻燈片':
                $columns = [];
                for ($i = 0; $i < 3; $i++) {
                    // 建立動作 -> 附加按鈕
                    $action = new UriTemplateActionBuilder('點我到紅褲', 'https://www.red-digital.com/');
                    // 圖片幻燈片模板
                    $column = new ImageCarouselColumnTemplateBuilder(env('IMG_URL'), $action);
                    $columns[] = $column;
                }
                // 組合圖片幻燈片
                $img_carousel = new ImageCarouselTemplateBuilder($columns);
                $tpl_builder = new TemplateMessageBuilder('顯示文字', $img_carousel);

                $result = $this->bot->replyMessage($this->reply_token, $tpl_builder);
                if (!$result->isSucceeded()) {
                    throw new Exception('幻燈片顯示失敗 : ' . $result->getRawBody());
                }
                break;
        }
    }

    public function createRichMenu($skip_create_flow = false)
    {
        if (!$skip_create_flow) {
            // bounds
            // x, y -> 水平、垂直偏移
            // width, height -> 所佔大小
            $bound_text = new RichMenuAreaBoundsBuilder(0, 0, 833,843);
            $bound_url = new RichMenuAreaBoundsBuilder(833, 0, 833,843);
            $bound_position = new RichMenuAreaBoundsBuilder(1666, 0, 833,843);

            // actions
            $action_text = new MessageTemplateActionBuilder('文字', '安安尼好');
            $action_url = new UriTemplateActionBuilder('網址', 'https://medium.com/@augustus0818/line-bot-rich-menu-aa5fa67ac6ae');
            $action_position = new LocationTemplateActionBuilder('位置');

            // area
            $areas = [];
            $areas[] = new RichMenuAreaBuilder($bound_text, $action_text);
            $areas[] = new RichMenuAreaBuilder($bound_url, $action_url);
            $areas[] = new RichMenuAreaBuilder($bound_position, $action_position);

            // size
            $size = new RichMenuSizeBuilder(843, 2500);

            $rich_menu = new RichMenuBuilder($size, true, 'practice', '功能選單', $areas);
            $this->bot->createRichMenu($rich_menu);
            // 建立完成會生成一組 rich_menu_id -> 回傳 {"richMenuId":"richmenu-4cd4e33fbf949dab4b6bda390e64517d"}
            // Log::info($this->bot->createRichMenu($rich_menu)->getRawBody());
        }


        Log::info($this->bot->getRichMenuId(config('line.user'))->getRawBody());
        exit();

        $this->bot->uploadRichMenuImage(config('line.rich_menu_id'), 'rich_menu_img/line-rich-menu-test-image.png', 'image/png');
        // link 完之後就可以用 getRichMenuId() 取得 richMenuId
        $this->bot->linkRichMenu(config('line.user'), config('line.rich_menu_id'));
    }

}