<?php

/**
 * 极光推送
 * @param $title 推送标题
 * @param $content 推送内容
 * @param string $vid 用户vid
 * @throws \Service\JPush\InvalidArgumentException极光推送
 */
function send($title, $content, $vid = '')
{
    //一般情况下，安卓和ios的appid和appsecret是一样的只要调用一次就够了，但要是不一样的情况，则需要调用两次
    $client = new \JPush\Client('bd75fd03c8f655b6a4abadd8', 'c2989566a6ae5e45ecf37929');//安卓
    $client2 = new \JPush\Client('c33e16756e7eee2bc3d8de9a', '56cf92da0ab0caaa5235dc02');//ios

    $result = $client
        ->push()
        ->setPlatform(array('android', 'ios'))
        ->options(['apns_production' => true]);
    $result2 = $client2
        ->push()
        ->setPlatform(array('android', 'ios'))
        ->options(['apns_production' => true]);
    //options参数，true表示生产环境，文档里写的默认是生产环境是有错误的，必须手动设置

    //vid为空的话表示群发
    if (empty($vid)) {
        $result->addAllAudience();
        $result2->addAllAudience();
    } else {
        $result->addAlias(trim($vid));
        $result2->addAlias(trim($vid));
    }

    //ios端是没有标题的，只有安卓端才有标题
    $res = $result
        ->iosNotification($content, [
            'sound' => 'sound',
            'badge' => '+1',
            'extras' => [
                'key' => 'value'
            ]
        ])
        ->androidNotification($content, [
            'title' => $title,
            'extras' => [
                'key' => 'value'
            ]
        ])
        ->send();

    $res2 = $result2
        ->iosNotification($content, [
            'sound' => 'sound',
            'badge' => '+1',
            'extras' => [
                'key' => 'value'
            ]
        ])
        ->androidNotification($content, [
            'title' => $title,
            'extras' => [
                'key' => 'value'
            ]
        ])
        ->send();
    return true;
//        var_dump($res);
}
