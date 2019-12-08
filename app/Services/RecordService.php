<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class RecordService
{
    protected $httpClient;
    protected $lineBot;
    protected $replyToken;

    public function __construct()
    {
        $this->httpClient = new CurlHTTPClient(config('line.token'));
        $this->lineBot = new LINEBot($this->httpClient, ['channelSecret' => config('line.secret')]);
    }

    public function handle(array $events)
    {
        // Log::info(print_r($events, true));

        // validation
        $validator = Validator::make($events, [
            'type'          => 'required|string|in:message,postback',
            'replyToken'    => 'required|string|size:32',
            // 巢狀陣列可用 . 取值
            'source.userId' => 'required|string',
            'timestamp'     => 'required|digits:13',
            'postback.data' => 'required_if:type,postback|string|in:song,singer,album',
            'message.type'  => 'required_if:type,message|string|in:text',
            'message.id'    => 'required_if:type,message|string|size:14',
            'message.text'  => 'required_if:type,message|string',
        ]);
        if ($validator->fails()) throw new Exception($validator->errors()->first());

        $flat = Arr::dot($events);

        switch ($flat['type']) {
            case 'postback':
                $this->create($flat);
                break;
            case 'message':
                $this->edit($flat['replyToken'], $flat['message.id'], $flat['message.text']);
                break;
        }

    }

    public function create(array $args = [])
    {

    }

    public function edit(string $replyToken, string $msgId, string $text)
    {

    }
}