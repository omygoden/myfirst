<?php

/**
 * 极光推送
 * @param $title 推送标题
 * @param $content 推送内容
 * @param string $vid 用户vid
 * @throws \Service\JPush\InvalidArgumentException极光推送
 */
function send($title,$content,$vid=''){
    //一般情况下，安卓和ios的appid和appsecret是一样的
    $client = new \Service\JPush\JPush('bd75fd03c8f655b6a4abadd8', 'c2989566a6ae5e45ecf37929');//安卓
    $client2 = new \Service\JPush\JPush('c33e16756e7eee2bc3d8de9a', '56cf92da0ab0caaa5235dc02');//ios
    $result = $client->push();
    $result2 = $client2->push();
    $result->setPlatform(array('android','ios'));
    $result2->setPlatform(array('android','ios'));
    //为空的话表示群发
    if(empty($vid)){
        $result->addAllAudience();
        $result2->addAllAudience();
    }else{
        $result->addAlias(trim($vid));
        $result2->addAlias(trim($vid));
    }
    //安卓推送
    $res=$result->addAndroidNotification($content, $title, 1, array("title" =>$title, "content" =>$content))
        ->addIosNotification($content, $title, '+1', true, 'iOS category', array("key1" => "value1", "key2" => "value2"))
        ->setMessage($content, $title, 'type', array("key1" => "value1", "key2" => "value2"))
        ->send();
    //ios推送
    $res2=$result2->addAndroidNotification($content, $title, 1, array("title" =>$title, "content" =>$content))
        ->addIosNotification($content, $title, '+1', true, 'iOS category', array("key1" => "value1", "key2" => "value2"))
        ->setMessage($content, $title, 'type', array("key1" => "value1", "key2" => "value2"))
        ->send();
    return true;
//        var_dump($res);
}
