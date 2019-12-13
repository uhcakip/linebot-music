<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class RecordService
{
    protected $recordRepo;
    protected $httpClient;
    protected $lineBot;
    protected $replyToken;

    public function __construct(RecordRepo $recordRepo)
    {
        // 注入
        $this->recordRepo = $recordRepo;
        // line
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
            'postback.data' => 'required_if:type,postback|string|in:track,artist,album',
            'message.type'  => 'required_if:type,message|string|in:text',
            'message.id'    => 'required_if:type,message|string|size:14',
            'message.text'  => 'required_if:type,message|string',
        ]);
        if ($validator->fails()) throw new Exception($validator->errors()->first());

        $flat = Arr::dot($events);

        switch ($flat['type']) {
            case 'postback':
                $flat['status'] = 'pending';

                $record = $this->recordRepo->getRecords([
                    'user'   => $flat['source.userId'],
                    'status' => $flat['status']
                ], false)->first();

                if ($record) $this->recordRepo->edit($flat);
                else $this->recordRepo->create($flat);

                break;

            case 'message':
                $flat['status'] = 'completed';

                $this->recordRepo->edit($flat);

                break;
        }

    }
}