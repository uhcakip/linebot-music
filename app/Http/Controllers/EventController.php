<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Services\EventService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class EventController extends Controller
{
    protected $eventService;

    protected $httpClient;
    protected $lineBot;

    public function __construct(EventService $eventService)
    {
        // 注入
        $this->eventService = $eventService;

        $this->httpClient = new CurlHTTPClient(config('bot.line_token'));
        $this->lineBot    = new LINEBot($this->httpClient, ['channelSecret' => config('bot.line_secret')]);
    }

    public function handle(Request $request)
    {
        try {
            $body    = $request->getContent();
            $header  = $request->header(HTTPHeader::LINE_SIGNATURE);
            $event   = Arr::first($this->lineBot->parseEventRequest($body, $header));
            $replyed = false;

            switch ($event->getType()) {
                case 'message':
                    $replyMsg = $this->eventService->handleMessageEvent($event);
                    $replyed  = $this->lineBot->replyMessage($event->getReplyToken(), $replyMsg);
                    break;

                case 'postback':
                    $replyMsg = $this->eventService->handlePostbackEvent($event);
                    $replyed  = $this->lineBot->replyMessage($event->getReplyToken(), $replyMsg);
                    break;

                case 'follow':
                    $this->eventService->handleFollowEvent($event);
                    break;

                default:
                    //throw new CustomException(buildLogMsg('不需處理的事件類別', writeJson(objToArr($event))));
                    exit;
            }

            if ($replyed && !$replyed->isSucceeded()) {
                throw new CustomException(buildLogMsg('訊息建立失敗', $replyed->getRawBody()));
            }

        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
