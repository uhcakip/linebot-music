<?php

namespace App\Services;

use App\Repos\RecordRepo;
use Exception;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\Event\BaseEvent;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\PostbackEvent;

class RecordService
{
    protected $recordRepo;
    protected $messageService;
    protected $musicService;

    protected $event;
    protected $record;
    protected $searchTypes;
    protected $notFoundMsg;

    public function __construct(RecordRepo $recordRepo, MessageService $messageService, MusicService $musicService)
    {
        // 注入
        $this->recordRepo     = $recordRepo;
        $this->messageService = $messageService;
        $this->musicService   = $musicService;

        $this->searchTypes = config('bot.type');
        $this->notFoundMsg = $this->messageService->createText('找不到相關的音樂資訊');
    }

    /**
     * 接收 Event 物件，傳給對應的處理 function
     *
     * @param BaseEvent $event
     * @return mixed
     */
    public function handle(BaseEvent $event)
    {
        $eventTypes = ['postback', 'message'];

        foreach ($eventTypes as $eventType) {
            if ($event->getType() === $eventType) {
                $this->event  = $event;
                $this->record = $this->recordRepo->getRecords(['user' => $this->event->getUserId()], false)->first();

                // 依照事件類別 call 對應的 function
                $handleFunction = 'handle' . ucfirst($eventType);
                return $this->$handleFunction();
            }
        }
    }

    /**
     * 處理 follow 事件
     *
     * @throws Exception
     */
    public function handleFollow()
    {
        if (!$this->event instanceof FollowEvent) {
            throw new Exception(buildLogMsg('變數型態錯誤', print_r($this->event, true)));
        }

        if (!$this->record) {
            $this->recordRepo->create(['user' => $this->event->getUserId()]);
        }
    }

    /**
     * 處理 postback 事件
     *
     * @throws Exception
     */
    public function handlePostback()
    {
        if (!$this->event instanceof PostbackEvent) {
            throw new Exception(buildLogMsg('變數型態錯誤', print_r($this->event, true)));
        }

        Log::info(buildLogMsg('handlePostback()', $this->event->getPostbackData()));

        $data       = explode('|', $this->event->getPostbackData());
        $searchType = $data[0];

        // 重複點選相同的搜尋範圍
        if ($this->record->type === $searchType) {
            exit;
        }

        // 變更搜尋範圍
        if (isset($this->searchTypes[$searchType])) {
            $this->recordRepo->update($this->record, ['type' => $searchType]);
            return $this->messageService->createText('已將搜尋範圍變更至 [ ' . $this->searchTypes[$searchType] . ' ]');
        }

        // 點選 flex message 元件
        switch ($searchType) {
            case 'find_album': // 顯示歌手專輯
                $albums = $this->musicService->getAlbums($data[1]);
                return $albums ? $this->messageService->createAlbumFlex($albums) : $this->notFoundMsg;

            case 'find_track': // 顯示專輯歌曲
                $tracks = $this->musicService->getTracks($data[1]);
                return $tracks ? $this->messageService->createFindTrackFlex($data, $tracks) : $this->notFoundMsg;

            case 'preview': // 試聽
                $musicUrl = saveMusic($data[1], $data[2]);
                return $this->messageService->createAudio($musicUrl);
        }

        throw new Exception(buildLogMsg('訊息建立失敗', print_r($this->event, true)));
    }

    /**
     * 處理 message 事件
     *
     * @throws Exception
     */
    public function handleMessage()
    {
        if (!$this->event instanceof MessageEvent) {
            throw new Exception(buildLogMsg('變數型態錯誤', print_r($this->event, true)));
        }

        if (!$this->record->type) {
            return $this->messageService->createText('請先點選搜尋範圍');
        }

        if (!$this->event instanceof TextMessage) {
            return $this->messageService->createText('請輸入文字');
        }

        $recordType   = $this->record->type;
        $searchResult = $this->musicService->getResult($recordType, $this->event->getText());

        if (!$searchResult) {
            return $this->notFoundMsg;
        }

        // 依照搜尋範圍 call 對應的 function
        $createFunction = 'create' . ucfirst($recordType) . 'Flex';
        return $this->messageService->$createFunction($searchResult);
    }
}
