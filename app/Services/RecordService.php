<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class RecordService
{
    protected $messageService;
    protected $musicService;
    protected $recordRepo;

    public function __construct(RecordRepo $recordRepo, MessageService $messageService, MusicService $musicService)
    {
        // 注入
        $this->recordRepo = $recordRepo;
        $this->messageService = $messageService;
        $this->musicService = $musicService;
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
            // regex -> 因 pipe 衝突，需寫成 array 形式
            'postback.data' => ['required_if:type,postback', 'string', 'regex:/^track$|^artist$|^album$|^preview$|^artist\|.+|^album\|.+/'],
            'message.type'  => 'required_if:type,message|string|in:text',
            'message.id'    => 'required_if:type,message|string|size:14',
            'message.text'  => 'required_if:type,message|string',
        ]);
        if ($validator->fails()) throw new Exception($validator->errors()->first());

        $flat = Arr::dot($events);
        $replyToken = $flat['replyToken'];
        $record = $this->recordRepo->getRecords(['user' => $flat['source.userId'], 'status' => 'pending'], false)->first();

        switch ($flat['type']) {
            case 'postback':
                if (!$record) {
                    $this->recordRepo->create($flat);
                    exit;
                }

                $data = $flat['postback.data'];

                // 變更搜尋範圍
                if (in_array($data, ['track', 'artist', 'album']) && $record->type !== $data) {
                    $this->recordRepo->update($record, $flat);
                    exit;
                }

                if (Str::contains($data, 'artist|')) {
                    $id = explode('|', $data)[1];
                    $music = $this->musicService->getAlbumByArtistId($id);
                    $flexMsg = $this->messageService->createFlexMsg('album', $music);
                    $response = $this->messageService->replyMessage($replyToken, $flexMsg);
                    if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                }

                if (Str::contains($data, 'album|')) {
                    $id = explode('|', $data)[1];
                    $music = $this->musicService->getTrackByAlbumId($id);
                    $flexMsg = $this->messageService->createTrackFlexMsg($data, $music);
                    $response = $this->messageService->replyMessage($replyToken, $flexMsg);
                    if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                }

                break;

            case 'message':
                if (!$record) {
                    $this->messageService->replyMessage($replyToken, $this->messageService->createTextMsg('請先點選搜尋範圍ㄛ'));
                    exit;
                }
                // reply flex message
                $music = $this->musicService->getMusicByKeyword($record->type, $flat);
                $flexMsg = $this->messageService->createFlexMsg($record->type, $music);
                $response = $this->messageService->replyMessage($replyToken, $flexMsg);
                if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                break;
        }
    }
}