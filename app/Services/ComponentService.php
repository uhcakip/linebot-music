<?php

namespace App\Services;

use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

class ComponentService
{
    const MAIN_COLOR = '#ffffff';
    
    /**
     * 建立文字元件 ( lg )
     *
     * @param string $name
     * @return BoxComponentBuilder
     */
    public function createLgText(string $name)
    {
        $text = new TextComponentBuilder($name);
        $text->setSize('lg')
             ->setColor(self::MAIN_COLOR)
             ->setWeight('bold');

        $box = new BoxComponentBuilder('vertical', [$text]);
        $box->setOffsetTop('8px');

        return $box;
    }

    /**
     * 建立文字元件 ( sm )
     *
     * @param string $name
     * @return BoxComponentBuilder
     */
    public function createSmText(string $name)
    {
        $text = new TextComponentBuilder($name);
        $text->setSize('sm')
             ->setColor('#ebebeb');

        $box = new BoxComponentBuilder('baseline', [$text], null, 'lg');
        $box->setOffsetTop('10px');

        return $box;
    }

    /**
     * 建立歌手、專輯按鈕元件
     *
     * @param string $hint
     * @param string $postbackData
     * @return BoxComponentBuilder
     */
    public function createBtn(string $hint, string $postbackData)
    {
        $text = new TextComponentBuilder($hint);
        $text->setColor(self::MAIN_COLOR)
             ->setAlign('center')
             ->setOffsetTop('7.5px');

        $pba = new PostbackTemplateActionBuilder('btn', $postbackData);
        $box = new BoxComponentBuilder('vertical', [$text], null, 'sm', 'xxl', $pba);
        $box->setHeight('40px')
            ->setBorderWidth('1px')
            ->setBorderColor(self::MAIN_COLOR)
            ->setCornerRadius('4px')
            ->setOffsetTop('7px');

        return $box;
    }

    /**
     * 建立歌曲按鈕元件
     *
     * @param array $postbackData
     * @return BoxComponentBuilder
     */
    public function createTrackBtn(array $postbackData)
    {
        $boxes   = [];
        $actions = [
            [
                'actionType' => new PostbackTemplateActionBuilder('btn', writeJson($postbackData)),
                'text'       => '試聽'
            ],
            [
                'actionType' => new UriTemplateActionBuilder('url', $postbackData['previewUrl']),
                'text'       => '前往下載'
            ]
        ];

        foreach ($actions as $k => $action) {
            $text = new TextComponentBuilder($action['text']);
            $text->setColor(self::MAIN_COLOR)
                 ->setPosition('absolute')
                 ->setAlign('center')
                 ->setOffsetTop('7.5px')
                 ->setOffsetStart('25%')
                 ->setOffsetEnd('25%');

            $box = new BoxComponentBuilder('vertical', [$text], $k + 1, 'sm', 'lg', $action['actionType']);
            $box->setHeight('40px')
                ->setBorderWidth('1px')
                ->setBorderColor(self::MAIN_COLOR)
                ->setCornerRadius('4px')
                ->setOffsetStart('-10px');

            $boxes[] = $box;
        }

        $boxWrap = new BoxComponentBuilder('horizontal', $boxes, null, 'sm', 'xxl');
        $boxWrap->setHeight('40px')
                ->setBorderColor(self::MAIN_COLOR)
                ->setCornerRadius('4px');

        return $boxWrap;
    }

    /**
     * 建立音樂資訊元件 ( 文字 + 按鈕 )
     *
     * @param ComponentBuilder[] $components
     * @return BoxComponentBuilder
     */
    public function createMusic(array $components)
    {
        $box = new BoxComponentBuilder('vertical', $components);
        $box->setPosition('absolute')
            ->setBackgroundColor('#111111cc')
            ->setOffsetBottom('0px')
            ->setOffsetStart('0px')
            ->setOffsetEnd('0px')
            ->setPaddingAll('20px')
            ->setPaddingTop('8px');

        return $box;
    }

    /**
     * 建立圖片元件
     *
     * @param string $url
     * @return ImageComponentBuilder
     */
    public function createImg(string $url)
    {
        return new ImageComponentBuilder($url, null, null, null, 'top', 'full', '2:3', 'cover');
    }

    /**
     * 組成 body ( 文字 + 按鈕 + 圖片 )
     *
     * @param ComponentBuilder[] $components
     * @return BoxComponentBuilder
     */
    public function createBody(array $components)
    {
        $box = new BoxComponentBuilder('vertical', $components);
        $box->setPaddingAll('0px');

        return $box;
    }

    /**
     * 組成 bubble ( container )
     *
     * @param BoxComponentBuilder $body
     * @return BubbleContainerBuilder
     */
    public function createBubble(BoxComponentBuilder $body)
    {
        return new BubbleContainerBuilder(null, null, null, $body);
    }

    /**
     * 組成 carousel ( container )
     *
     * @param BubbleContainerBuilder[] $bubbles
     * @return CarouselContainerBuilder
     */
    public function createCarousel(array $bubbles)
    {
        return new CarouselContainerBuilder($bubbles);
    }
}
