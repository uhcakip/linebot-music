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
    protected $richMenuService;
    protected $messageService;
    protected $musicService;

    // var
    protected $eventObj;
    protected $record;
    protected $notFoundMsg;

    public function __construct(RecordRepo $recordRepo, RichMenuService $richMenuService, MessageService $messageService, MusicService $musicService)
    {
        // 注入
        $this->recordRepo = $recordRepo;
        $this->richMenuService = $richMenuService;
        $this->messageService = $messageService;
        $this->musicService = $musicService;

        // 404 message
        $this->notFoundMsg = $this->messageService->createText('找不到相關的音樂資訊');
    }

    /**
     * 接收 Event 物件，傳給對應的處理 function
     *
     * @param BaseEvent $eventObj
     * @return mixed
     * @throws Exception
     */
    public function handle(BaseEvent $eventObj)
    {
        // Log::info(print_r($eventObj, true));
        $this->eventObj = $eventObj;
        $this->record = $this->recordRepo->getRecords(['user' => $this->eventObj->getUserId()], false)->first();

        $eventType = $this->eventObj->getType();
        if (!in_array($eventType, ['postback', 'message'])) {
            throw new Exception('Type of event should be postback or message');
        }

        // 依照事件類別 call 對應的 function
        $handleFun = 'handle' . ucfirst($eventType);
        return $this->$handleFun();
    }

    /**
     * 處理 follow 事件 ( 連結 rich menu )
     *
     * @throws Exception
     */
    public function handleFollow()
    {
        if (!$this->eventObj instanceof FollowEvent) {
            throw new Exception('Varaible eventObj should be an instance of FollowEvent');
        }

        // verify user and link to a specfic rich menu ...

    }

    /**
     * 處理 postback 事件
     *
     * @throws Exception
     */
    public function handlePostback()
    {
        if (!$this->eventObj instanceof PostbackEvent) {
            throw new Exception('Varaible eventObj should be an instance of PostbackEvent');
        }

        $data = explode('|', $this->eventObj->getPostbackData());

        if (!$this->record) {
            $this->recordRepo->create([
                'user' => $this->eventObj->getUserId(),
                'type' => $data[0]
            ]);
            exit;
        }

        // 重複點選相同的搜尋範圍 ( Rich Menu )
        if ($this->record->type === $data[0]) {
            exit;
        }

        // 變更搜尋範圍 ( Rich Menu )
        if (in_array($data[0], array_keys(config('bot.type')))) {
            $this->recordRepo->update($this->record, ['type' => $data[0]]);
            exit;
        }

        switch ($data[0]) {
            // 點選按鈕「顯示歌手專輯」
            case 'find_album':
                $albums = $this->musicService->getAlbums($data[1]);
                return $albums ? $this->messageService->createAlbumFlex($albums) : $this->notFoundMsg;
            // 點選按鈕「顯示專輯歌曲」
            case 'find_track':
                $tracks = $this->musicService->getTracks($data[1]);
                return $tracks ? $this->messageService->createFindTrackFlex($data, $tracks) : $this->notFoundMsg;
            // 點選按鈕「試聽」
            case 'preview':
                $musicUrl = saveMusic($data[1], $data[2]);
                return $this->messageService->createAudio($musicUrl);
        }

        return $this->messageService->createText('發生了一些錯誤 QQ');
    }

    /**
     * 處理 message 事件
     *
     * @throws Exception
     */
    public function handleMessage()
    {
        if (!$this->eventObj instanceof MessageEvent) {
            throw new Exception('Varaible eventObj should be an instance of MessageEvent');
        }

        if (!$this->record) {
            return $this->messageService->createText('請先點選搜尋範圍');
        }

        // 輸入文字以外的關鍵字
        if (!$this->eventObj instanceof TextMessage) {
            return $this->messageService->createText('請輸入文字');
        }

        if (!$result = $this->musicService->getResult($this->record->type, $this->eventObj->getText())) {
            return $this->notFoundMsg;
        }

        // 依照搜尋範圍 call 對應的 function
        $createFun = 'create' . ucfirst($this->record->type) . 'Flex';
        return $this->messageService->$createFun($result);
    }
}