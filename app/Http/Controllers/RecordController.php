<?php

namespace App\Http\Controllers;

use App\Services\RecordService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class RecordController extends Controller
{
    protected $recordService;

    protected $httpClient;
    protected $lineBot;

    public function __construct(RecordService $recordService)
    {
        // 注入
        $this->recordService = $recordService;

        $this->httpClient = new CurlHTTPClient(config('bot.line_token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('bot.line_secret')]);
    }

    public function handle(Request $request)
    {
        try {
            // 回傳對應的 Event 物件 ( array )
            $event = $this->lineBot->parseEventRequest(
                $request->getContent(),
                $request->header(HTTPHeader::LINE_SIGNATURE)
            );
            $eventObj = Arr::first($event);
            $replyMsg = $this->recordService->handle($eventObj);
            $response = $this->lineBot->replyMessage($eventObj->getReplyToken(), $replyMsg);
            if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }
}
