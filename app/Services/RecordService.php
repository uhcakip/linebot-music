<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
                    $flexMsg = $this->messageService->createAlbumFlex($albums);
                    $response = $this->messageService->reply($replyToken, $flexMsg);
                    if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                }

                // 顯示專輯歌曲
                if (Str::contains($data, 'find_track|')) {
                    $tracks = $this->musicService->getTracks($dataArr[1]);
                    $flexMsg = $this->messageService->createTrackFlex($dataArr, $tracks);
                    $response = $this->messageService->reply($replyToken, $flexMsg);
                    if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                }

                // 試聽
                if (Str::contains($data, 'preview|')) {
                    $musicUrl = saveMusic($dataArr[1], $dataArr[2]);
                    $response = $this->messageService->reply($replyToken, $this->messageService->createAudio($musicUrl));
                    if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                }

                break;

            case 'message':
                if (!$record) {
                    $this->messageService->reply($replyToken, $this->messageService->createText('請先點選搜尋範圍ㄛ'));
                    exit;
                }
                // reply flex message
                $msg = $this->messageService->createText('找ㄅ到相關音樂資訊得死');
                $result = $this->musicService->getResult($record->type, $flat);
                if (!$result) {
                    $this->messageService->reply($replyToken, $msg);
                    exit;
                }
                if ($record->type === 'track') {
                    $msg = $this->messageService->createTrackFlex([], $result);
                }
                if ($record->type === 'artist') {
                    $msg = $this->messageService->createArtistFlex($result);
                }
                if ($record->type === 'album') {
                    $msg = $this->messageService->createAlbumFlex($result);
                }
                $response = $this->messageService->reply($replyToken, $msg);
                if (!$response->isSucceeded()) throw new Exception($response->getRawBody());
                break;
        }
    }
}