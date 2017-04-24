<?php

function qrcode()
{
    //L、M、Q、H表示二维码的复杂度，逐级递增
    $level='L';
    //二维码保存路劲
    $path="./Public/qrcode/test.jpg";
    $code = new \Service\Qrcode();
    $name = time() . rand(1000, 9999);
    $img_url = "http://jradm.86tudi.cn/Public/qrcode/" . $name . '.png';
    $code::png("https://eduv2.wzcxfq.com/index.php/Wx/getopenid?shopid=1&unique=123", './Public/qrcode/' . $name . '.png',$level);
}
