<?php

namespace App\Services;

use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class ComponentService
{
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
            ->setColor(config('line.main_color'))
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
        $box->setOffsetTop('15px');

        return $box;
    }

    /**
     * 建立按鈕元件
     *
     * @param string $hint
     * @param string $data
     * @return BoxComponentBuilder
     */
    public function createBtn(string $hint, string $data)
    {
        $postbackAction = new PostbackTemplateActionBuilder('btn', $data);

        $text = new TextComponentBuilder($hint);
        $text->setColor(config('line.main_color'))
            ->setAlign('center')
            ->setOffsetTop('7.5px');

        $box = new BoxComponentBuilder('vertical', [$text], null, 'sm', 'xxl', $postbackAction);
        $box->setHeight('40px')
            ->setBorderWidth('1px')
            ->setBorderColor(config('line.main_color'))
            ->setCornerRadius('4px')
            ->setOffsetTop('7px');

        return $box;
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
            ->setPaddingAll('25px')
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