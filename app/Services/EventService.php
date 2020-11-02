<?php

namespace App\Services;


use App\Exceptions\CustomException;
use App\Repos\RecordRepo;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\PostbackEvent;

class EventService
{
    protected $recordRepo;
    protected $messageService;
    protected $musicService;
    protected $notFoundMsg;

    public function __construct(RecordRepo $recordRepo, MessageService $messageService, MusicService $musicService)
    {
        // 注入
        $this->recordRepo = $recordRepo;
        $this->messageService = $messageService;
        $this->musicService = $musicService;

        $this->notFoundMsg = $this->messageService->createText('找不到相關的音樂資訊');
    }

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

    public function handlePostbackEvent(PostbackEvent $event)
    {
        $record = $this->recordRepo->getRecords(['user' => $event->getUserId()], false)->first();
        $pbData = json_decode($event->getPostbackData(), true);
        $type = $pbData['type'];
        $notFoundMsg = $this->messageService->createText('找不到相關的音樂資訊');

        // 點選 rich menu 變更搜尋範圍
        if (isset(RichMenuService::TYPES[$type])) {
            if ($record->type !== $type) {
                $this->recordRepo->update($record, ['type' => $type]);
            }
            exit;
        }

        // 點選 flex message 元件
        switch ($type) {
            case 'AlbumsOfArtist': // 顯示歌手專輯
                $albums = $this->musicService->getAlbums($pbData['artistId']);
                return $albums ? $this->messageService->createAlbumFlex($albums) : $notFoundMsg;

            case 'tracksInAlbum': // 顯示專輯歌曲
                $tracks = $this->musicService->getTracks($pbData['albumId']);
                return $tracks ? $this->messageService->createFindTrackFlex($pbData, $tracks) : $notFoundMsg;

            case 'preview': // 試聽
                $musicUrl = saveMusic($pbData['trackId'], $pbData['previewUrl']);
                return $this->messageService->createAudio($musicUrl);

            default:
                throw new CustomException(buildLogMsg('訊息建立失敗', writeJson(objToArr($event))));
        }
    }

    public function handleMessageEvent(MessageEvent $event)
    {
        if (!$event instanceof TextMessage) {
            return $this->messageService->createText('請輸入文字');
        }

        $record = $this->recordRepo->getRecords(['user' => $event->getUserId()], false)->first();
        $results = $this->musicService->searchByKeyword($record->type, $event->getText());

        if (!$results) {
            return $this->messageService->createText('找不到相關的音樂資訊');
        }

        switch ($record->type) {
            case 'artist':
                // 統整歌手資訊
                $artists = collect($results)->map(function ($result) {
                    if (count($result['images'] ?? []) <= 0) {
                        return [];
                    }

                    $artistImg = collect($result['images'])->whereIn('width', [300, 500])->first() ?: collect($result['images'])->where('width', 160)->first();
                    $artistImg = $artistImg['url'];
                    $artistId = $result['id'];
                    $artistName = $result['name'];
                    $postbackData = ['area' => 'flexMessage', 'type' => 'AlbumsOfArtist'] + compact('artistId');
                    return compact('artistName', 'artistImg', 'postbackData');
                });

                //Log::info(buildLogMsg('歌手資訊', print_r($artists->all(), true)));
                $take = $artists->count() > 5 ? 5 : $artists->count();
                return $this->messageService->createArtistFlex($artists->filter()->take($take)->all());

            case 'track':
                $tracks = collect($results)->map(function ($result) {
                    if (count($result['album']['images'] ?? []) <= 0 || !in_array('TW', $result['available_territories'] ?? [])) {
                        return [];
                    }

                    $albumImg = collect($result['album']['images'])->whereIn('width', [300, 500])->first() ?: collect($result['album']['images'])->where('width', 160)->first();
                    $albumImg = $albumImg['url'];
                    $trackId = $result['id'];
                    $trackName = $result['name'];
                    $artistName = $result['album']['artist']['name'];
                    $previewUrl = getPreviewUrl($result['url']);
                    $postbackData = ['area' => 'flexMessage', 'type' => 'preview'] + compact('trackId', 'previewUrl');
                    return compact('trackName', 'artistName', 'albumImg', 'postbackData');
                });

                //Log::info(buildLogMsg('歌曲資訊', print_r($tracks->all(), true)));
                $take = $tracks->count() > 5 ? 5 : $tracks->count();
                return $this->messageService->createTrackFlex($tracks->filter()->take($take)->all());

            case 'album':
                // 統整專輯資訊
                $albums = collect($results)->map(function ($result) {
                    if (count($result['images'] ?? []) <= 0 || !in_array('TW', $result['available_territories'] ?? [])) {
                        return [];
                    }

                    $albumImg = collect($result['images'])->whereIn('width', [300, 500])->first() ?: collect($result['images'])->where('width', 160)->first();
                    $albumImg = $albumImg['url'];
                    $albumId = $result['id'];
                    $albumName = $result['name'];
                    $artistName = $result['artist']['name'];
                    $postbackData = ['area' => 'flexMessage', 'type' => 'tracksInAlbum'] + compact('albumId', 'artistName', 'albumImg');

                    return compact('albumId', 'albumName', 'albumImg', 'artistName', 'postbackData');
                });

                //Log::info(buildLogMsg('專輯資訊', print_r($albums->all(), true)));
                $take = $albums->count() > 5 ? 5 : $albums->count();
                return $this->messageService->createAlbumFlex($albums->filter()->take($take)->all());
        }
    }
}