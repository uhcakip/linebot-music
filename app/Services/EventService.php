<?php

namespace App\Services;

use App\Repos\RecordRepo;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class EventService
{
    protected $recordRepo;
    protected $musicService;
    protected $messageService;

    public function __construct(RecordRepo $recordRepo, MusicService $musicService, MessageService $messageService)
    {
        // 注入
        $this->recordRepo     = $recordRepo;
        $this->musicService   = $musicService;
        $this->messageService = $messageService;
    }

    /**
     * 處理 follow 事件
     *
     * @param FollowEvent $event
     */
    public function handleFollowEvent(FollowEvent $event)
    {
        $record = $this->recordRepo->getRecords(['user' => $event->getUserId()], false)->first();

        if (!$record) {
            $this->recordRepo->create([
                'user' => $event->getUserId(),
                'type' => 'track' // 預設搜尋範圍為歌曲
            ]);
        }
    }

    /**
     * 處理 message 事件
     *
     * @param MessageEvent $event
     * @return FlexMessageBuilder|TextMessageBuilder
     */
    public function handleMessageEvent(MessageEvent $event)
    {
        if (!$event instanceof TextMessage) {
            return $this->messageService->createTextMessage('請輸入文字');
        }

        $record  = $this->recordRepo->getRecords(['user' => $event->getUserId()], false)->first();
        $keyword = $event->getText();

        // 透過文字變更搜範圍
        foreach (RichMenuService::TYPES as $type => $typeCh) {
            if ($keyword === '變更搜尋範圍至' . $typeCh) {
                $this->recordRepo->update($record, ['type' => $type]);
                return $this->messageService->createTextMessage('已將搜尋範圍變更至' . $typeCh);
            }
        }

        $results = $this->musicService->searchByKeyword($record->type, $keyword);

        if (!$results) {
            return $this->messageService->createTextMessage('找不到相關的音樂資訊');
        }

        switch ($record->type) {
            case RichMenuService::ARTIST: return $this->messageService->createArtistFlexMessage($results);
            case RichMenuService::TRACK:  return $this->messageService->createTrackFlexMessage($results);
            case RichMenuService::ALBUM:  return $this->messageService->createAlbumFlexMessage($results);
        }
    }

    /**
     * 處理 postback 事件
     *
     * @param PostbackEvent $event
     * @return AudioMessageBuilder|FlexMessageBuilder|TextMessageBuilder
     */
    public function handlePostbackEvent(PostbackEvent $event)
    {
        $record = $this->recordRepo->getRecords(['user' => $event->getUserId()], false)->first();
        $data   = json_decode($event->getPostbackData(), true);
        $type   = $data['type'];

        // 點選 rich menu
        if ($data['area'] === RichMenuService::RICH_MENU) {
            if (isset(RichMenuService::TYPES[$type]) && $record->type !== $type) { // 變更搜尋範圍
                $this->recordRepo->update($record, ['type' => $type]);
                exit;
            }
        }

        // 點選訊息元件
        if ($data['area'] === MessageService::FLEX_MESSAGE) {
            $notFoundMsg = $this->messageService->createTextMessage('找不到相關的音樂資訊');

            switch ($type) {
                case MessageService::ALBUMS_OF_ARTIST: // 顯示歌手專輯
                    $albums = $this->musicService->getAlbumsByArtistId($data['artistId']);
                    return $albums ? $this->messageService->createAlbumFlexMessage($albums) : $notFoundMsg;

                case MessageService::TRACKS_IN_ALBUM: // 顯示專輯歌曲
                    $tracks = $this->musicService->getTracksByAlbumId($data['albumId']);
                    return $tracks ? $this->messageService->createTrackFlexMessage($tracks, $data) : $notFoundMsg;

                case MessageService::PREVIEW: // 試聽
                    $musicUrl = storeTrack($data['trackId'], $data['previewUrl']);
                    return $this->messageService->createAudioMessage($musicUrl);
            }
        }
    }
}
