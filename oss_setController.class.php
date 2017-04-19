<?php
namespace Mynote\Controller;
use Think\Controller;

//批量下载图片
class oss_uploadController extends Controller
{
    protected $oss_appid='7wDuicrZeSm3LidN';
    protected $oss_secret='5NMNsAF0mT1xfU19e3gviuBI8YKQBP';
    protected $oss_url='http://ceshimain.oss-cn-hangzhou.aliyuncs.com'; //oss地址
    //image=$_FILES
    public function addossimg($image,$type=false){
        //引入OSS类文件
        $success = new \Service\OssClient($this->oss_appid,$this->oss_secret,$this->oss_url);
        //默认为空，随机命名，否则使用原文件名
        if($type==false){
            $ext=pathinfo($image['name'],PATHINFO_EXTENSION);
            $img_name=time().uniqid().'.'.$ext;
        }else{
            $img_name=$image['name'];
        }
        //先建一个图片存放容器，lingsui为上传路径
        $object = 'lingsui/'.$img_name;
        //临时文件，也可直接写服务器上某个路径下面的图片地址，比如./Public/test.jpg
        $filePath = $image['tmp_name'];
        //限制大小,也可做其他限制
        if($image['size']>2*1024*1024){
            return '1002';
        }
        try {
            //ceshimain为bucket，即存放文件的区域，必备，区域只能有一个，但区域下面可以建多个文件夹，比如上面的lingsui文件夹
            $success->uploadFile('ceshimain',$object,$filePath);
            //最后返回图片地址
            return $this->oss_url.'/lingsui/'.$img_name;
//			return '1001';
        } catch (Exception $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return '1003';
        }
    }

    /**
     * 333
     * 删除图片
     * @param $object 图片地址
     */
    public function delossimg($object)
    {
        $success = new \Service\OssClient($this->oss_appid,$this->oss_secret,$this->oss_url);
        try {
            $success->deleteObject('ceshimain', $object);
            return '1001';
        } catch (Exception $e) {
            return '1002';
        }
    }

}
