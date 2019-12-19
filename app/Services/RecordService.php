<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LINE\LINEBot\Response;

class RecordService
{
    protected $recordRepo;
    protected $messageService;
    protected $musicService;

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
            'postback.data' => ['required_if:type,postback', 'string', 'regex:/^track$|^artist$|^album$|^preview\|.+|^find_album\|.+|^find_track\|.+/'],
            'message.type'  => 'required_if:type,message|string|in:text',
            'message.id'    => 'required_if:type,message|string|size:14',
            'message.text'  => 'required_if:type,message|string',
        ]);
        if ($validator->fails()) throw new Exception($validator->errors()->first());

        $flat = Arr::dot($events);
        $replyToken = $flat['replyToken'];
        $record = $this->recordRepo->getRecords(['user' => $flat['source.userId']], false)->first();

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

                $dataArr = explode('|', $data);

                // 顯示歌手專輯
                if (Str::contains($data, 'find_album|')) {
                    $albums = $this->musicService->getAlbums($dataArr[1]);
                    $response = $this->messageService->reply(
                        $replyToken,
                        $this->messageService->createAlbumFlex($albums)
                    );
                }
                // 顯示專輯歌曲
                if (Str::contains($data, 'find_track|')) {
                    $tracks = $this->musicService->getTracks($dataArr[1]);
                    $response = $this->messageService->reply(
                        $replyToken,
                        $this->messageService->createFindTrackFlex($dataArr, $tracks)
                    );
                }
                // 試聽
                if (Str::contains($data, 'preview|')) {
                    $musicUrl = saveMusic($dataArr[1], $dataArr[2]);
                    $response = $this->messageService->reply(
                        $replyToken,
                        $this->messageService->createAudio($musicUrl)
                    );
                }

                // handle response
                if (!isset($response)) {
                    throw new Exception('Undefined response !');
                }
                if (!$response->isSucceeded()) {
                    throw new Exception($response->getRawBody());
                }

                break;

            case 'message':
                if (!$record) {
                    $this->messageService->reply(
                        $replyToken,
                        $this->messageService->createText('請先點選搜尋範圍')
                    );
                    exit;
                }

                if (!$result = $this->musicService->getResult($record->type, $flat)) {
                    $this->messageService->reply(
                        $replyToken,
                        $this->messageService->createText('找不到相關的音樂資訊')
                    );
                    exit;
                }

                // 依照指定的搜尋範圍 call 對應的 function
                $funName = 'create' . ucfirst($record->type) . 'Flex';
                $response = $this->messageService->reply(
                    $replyToken,
                    $this->messageService->$funName($result)
                );

                if (!$response->isSucceeded()) {
                    throw new Exception($response->getRawBody());
                }

                break;
        }
    }
}