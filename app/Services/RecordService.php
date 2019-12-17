<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class RecordService
{
    protected $messageService;
    protected $recordRepo;

    public function __construct(RecordRepo $recordRepo, MessageService $messageService)
    {
        // 注入
        $this->recordRepo = $recordRepo;
        $this->messageService = $messageService;
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
        $record = $this->recordRepo->getRecords(['user' => $flat['source.userId'], 'status' => 'pending',], false)->first();

        switch ($flat['type']) {
            case 'postback':
                if (!$record) {
                    $this->recordRepo->create($flat);
                    exit;
                }
                // 變更搜尋範圍
                if ($record->type !== $flat['postback.data']) {
                    $this->recordRepo->update($record, $flat);
                }
                break;
            case 'message':
                if (!$record) {
                    $this->messageService->replyMessage($flat['replyToken'], $this->messageService->createTextMsg('請先點選搜尋範圍ㄛ'));
                }
                $flexMsg = $this->messageService->createFlexMsg($record->type, getMusicInfo($record->type, $flat));
                $this->messageService->replyMessage($flat['replyToken'], $flexMsg);
                break;
        }
    }
}