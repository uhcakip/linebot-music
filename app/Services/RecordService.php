<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Repos\RecordRepo;
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
    protected $notFoundMsg;

    public function __construct(RecordRepo $recordRepo, MessageService $messageService, MusicService $musicService)
    {
        // 注入
        $this->recordRepo     = $recordRepo;
        $this->messageService = $messageService;
        $this->musicService   = $musicService;

        $this->notFoundMsg = $this->messageService->createText('找不到相關的音樂資訊');
    }

    /**
     * 接收 event 物件，傳給對應的處理 function
     *
     * @param BaseEvent $event
     * @return mixed
     */
    public function handle(BaseEvent $event)
    {
        $handleTypes = ['postback', 'message', 'follow'];
        $eventType   = $event->getType();

        if (!in_array($eventType, $handleTypes)) {
            //throw new CustomException(buildLogMsg('不需處理的事件類別', writeJson(objToArr($event))));
            exit;
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
     * @throws CustomException
     */
    public function handleFollow()
    {
        if (!$this->event instanceof FollowEvent) {
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson($this->event)));
        }

        if (!$this->record) {
            // 預設搜尋範圍為歌曲
            $this->recordRepo->create(['user' => $this->event->getUserId(), 'type' => 'track']);
        }
    }

    /**
     * 處理 postback 事件
     *
     * @return \LINE\LINEBot\MessageBuilder\AudioMessageBuilder|\LINE\LINEBot\MessageBuilder\FlexMessageBuilder|\LINE\LINEBot\MessageBuilder\TextMessageBuilder
     * @throws CustomException
     */
    public function handlePostback()
    {
        if (!$this->event instanceof PostbackEvent) {
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson(objToArr($this->event))));
        }

        $postbackData = json_decode($this->event->getPostbackData(), true);
        $searchType   = $postbackData['type'];

        // 點選 rich menu 變更搜尋範圍
        if (isset(RichMenuService::SEARCH_TYPES[$searchType])) {
            if ($this->record->type !== $searchType) {
                $this->recordRepo->update($this->record, ['type' => $searchType]);
            }
            exit;
        }

        // 點選 flex message 元件
        switch ($searchType) {
            case 'findAlbum': // 顯示歌手專輯
                $albums = $this->musicService->getAlbums($postbackData['artistId']);
                return $albums
                     ? $this->messageService->createAlbumFlex($albums)
                     : $this->notFoundMsg;

            case 'findTrack': // 顯示專輯歌曲
                $tracks = $this->musicService->getTracks($postbackData['albumId']);
                return $tracks
                     ? $this->messageService->createFindTrackFlex($postbackData, $tracks)
                     : $this->notFoundMsg;

            case 'preview': // 試聽
                $musicUrl = saveMusic($postbackData['trackId'], $postbackData['previewUrl']);
                return $this->messageService->createAudio($musicUrl);
        }

        throw new CustomException(buildLogMsg('訊息建立失敗', writeJson(objToArr($this->event))));
    }

    /**
     * 處理 message 事件
     *
     * @return \LINE\LINEBot\MessageBuilder\TextMessageBuilder|\LINE\LINEBot\MessageBuilder\FlexMessageBuilder
     * @throws CustomException
     */
    public function handleMessage()
    {
        if (!$this->event instanceof MessageEvent) {
            throw new CustomException(buildLogMsg('變數型態錯誤', writeJson(objToArr($this->event))));
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
