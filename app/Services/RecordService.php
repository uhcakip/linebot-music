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

    // var
    protected $flat;
    protected $replyToken;
    protected $record;

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
        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $this->flat = Arr::dot($events);
        $this->replyToken = $this->flat['replyToken'];
        $this->record = $this->recordRepo->getRecords(['user' => $this->flat['source.userId']], false)->first();

        // 依照事件類別 call 對應的 function
        $handleFunName = 'handle' . ucfirst($this->flat['type']);
        $this->$handleFunName();
    }

    public function handlePostback()
    {
        if (!$this->record) {
            $this->recordRepo->create($this->flat);
            exit;
        }

        $data = explode('|', $this->flat['postback.data']);

        // 點選 Rich Menu 變更搜尋範圍
        if (in_array($data[0], ['track', 'artist', 'album']) && $this->record->type !== $data[0]) {
            $this->recordRepo->update($this->record, $this->flat);
            exit;
        }

        switch ($data[0]) {
            // 點選按鈕「顯示歌手專輯」
            case 'find_album':
                $albums = $this->musicService->getAlbums($data[1]);
                $response = $this->messageService->reply(
                    $this->replyToken,
                    $this->messageService->createAlbumFlex($albums)
                );
                break;
            // 點選按鈕「顯示專輯歌曲」
            case 'find_track':
                $tracks = $this->musicService->getTracks($data[1]);
                $response = $this->messageService->reply(
                    $this->replyToken,
                    $this->messageService->createFindTrackFlex($data, $tracks)
                );
                break;
            // 點選按鈕「試聽」
            case 'preview':
                $musicUrl = saveMusic($data[1], $data[2]);
                $response = $this->messageService->reply(
                    $this->replyToken,
                    $this->messageService->createAudio($musicUrl)
                );
                break;
        }

        // handle response
        if (!isset($response)) {
            throw new Exception('Response is undefined');
        }
        if (!$response->isSucceeded()) {
            throw new Exception($response->getRawBody());
        }
    }

    public function handleMessage()
    {
        if (!$this->record) {
            $this->messageService->reply(
                $this->replyToken,
                $this->messageService->createText('請先點選搜尋範圍')
            );
            exit;
        }

        if (!$result = $this->musicService->getResult($this->record->type, $this->flat['message.text'])) {
            $this->messageService->reply(
                $this->replyToken,
                $this->messageService->createText('找不到相關的音樂資訊')
            );
            exit;
        }

        // 依照搜尋範圍 call 對應的 function
        $funName = 'create' . ucfirst($this->record->type) . 'Flex';
        $response = $this->messageService->reply(
            $this->replyToken,
            $this->messageService->$funName($result)
        );

        if (!$response->isSucceeded()) {
            throw new Exception($response->getRawBody());
        }
    }

}