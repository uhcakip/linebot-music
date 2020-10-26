<?php

namespace App\Services;

use App\Exceptions\CustomException;
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
     * @throws CustomException
     */
    public function handle(BaseEvent $event)
    {
        $handleTypes = ['postback', 'message', 'follow'];
        $eventType   = $event->getType();

        if (!in_array($eventType, $handleTypes)) {
            throw new CustomException(buildLogMsg('不需處理的事件類別', writeJson(objToArr($event))));
        }

        $this->event  = $event;
        $this->record = $this->recordRepo->getRecords(['user' => $this->event->getUserId()], false)->first();

        // 依照事件類別 call 對應的 function
        $handleFunction = 'handle' . ucfirst($eventType);
        return $this->$handleFunction();
    }

    /**
     * 處理 follow 事件
     *
     * @throws Exception
     */
    public function handleFollow()
    {
        if (!$this->event instanceof FollowEvent) {
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson($this->event)));
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
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson(objToArr($this->event))));
        }

        $musicData  = explode('|', $this->event->getPostbackData());
        $searchType = $musicData[0];

        // 重複點選相同的搜尋範圍
        if ($this->record->type === $searchType) {
            exit;
        }

        // 變更搜尋範圍
        if (isset($this->searchTypes[$searchType])) {
            $this->recordRepo->update($this->record, ['type' => $searchType]);
            exit;
        }

        // 點選 flex message 元件
        switch ($searchType) {
            case 'find_album': // 顯示歌手專輯
                $albums = $this->musicService->getAlbums($musicData[1]);
                return $albums ? $this->messageService->createAlbumFlex($albums) : $this->notFoundMsg;

            case 'find_track': // 顯示專輯歌曲
                $tracks = $this->musicService->getTracks($musicData[1]);
                return $tracks ? $this->messageService->createFindTrackFlex($musicData, $tracks) : $this->notFoundMsg;

            case 'preview': // 試聽
                $musicUrl = saveMusic($musicData[1], $musicData[2]);
                return $this->messageService->createAudio($musicUrl);
        }

        throw new CustomException(buildLogMsg('訊息建立失敗', writeJson(objToArr($this->event))));
    }

    /**
     * 處理 message 事件
     *
     * @throws Exception
     */
    public function handleMessage()
    {
        if (!$this->event instanceof MessageEvent) {
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson(objToArr($this->event))));
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
