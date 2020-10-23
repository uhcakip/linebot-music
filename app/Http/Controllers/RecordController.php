<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
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
        $this->lineBot    = new LINEBot($this->httpClient, ['channelSecret' => config('bot.line_secret')]);
    }

    public function handle(Request $request)
    {
        try {
            $event = $this->lineBot->parseEventRequest( // 回傳對應的 event 物件 ( array )
                $request->getContent(),
                $request->header(HTTPHeader::LINE_SIGNATURE)
            );

            $event    = Arr::first($event);
            $replyMsg = $this->recordService->handle($event);
            $response = $this->lineBot->replyMessage($event->getReplyToken(), $replyMsg);

            if (!$response->isSucceeded()) {
                throw new CustomException($response->getRawBody());
            }

        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
