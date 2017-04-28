<?php
namespace Mynote\Controller;
use Think\Controller;

//批量图片上传
class PublicController extends Controller{
    /**
     * 编辑器图片上传
     */
    public function editor_upload(){
        $config = array(
            'exts' => array('jpg', 'jpeg','png', 'gif', 'ico'), //允许上传的文件后缀
            'rootPath' => './Public/upload/', //保存根路径
        );
        $img = new \Think\Upload($config);
        $arimg = $img->uploadOne($_FILES['fileDataFileName']);
        if ($arimg) {
            $imgs = $img->rootPath . $arimg['savepath'] . $arimg['savename'];
//            $smlimg = new \Think\Image();
//            $smlimg->open($imgs);
////            $smlimg->thumb(120, 120);
//            $small = $img->rootPath . $arimg['savepath'] . 'small' . $arimg['savename'];
//            $smlimg->save($small);
//            $smallimg = $small;

            //file_path必须写完整的路径名称，否则js端将获取不到图片，这样js将会把图片路径保存成数据流格式
            echo json_encode(['success'=>'true','msg'=>'success','file_path'=>C('MYURL').ltrim($imgs,'./')]);
        } else {
            echo json_encode(['success'=>'false','msg'=>'fail']);
        }
    }
}
?>
<html>
<link rel="stylesheet" type="text/css" href="__PUBLIC__/admin/css/simditor.css" />
<script src="__PUBLIC__/admin/js/jquery.min.js?v=2.1.4"></script>
<script type="text/javascript" src="__PUBLIC__/admin/js/module.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/admin/js/hotkeys.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/admin/js/uploader.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/admin/js/simditor.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/admin/js/simditor-fullscreen.js"></script>
<div class="col-sm-10">
    <textarea type="text" class="form-control" name='detail' id="detail"></textarea>
</div>
<script>
    var editor = new Simditor({
        textarea: $('#detail'),
        toolbar: [
            'title','bold','italic','underline','strikethrough','fontScale','color','ol','ul','blockquote','code','table','hr','indent','outdent','alignment','image','fullscreen'
        ],
        defaultImage: 'images/image.jpg',
        upload:{
            url: '__MODULE__/Public/editor_upload',
            params: null,
            fileKey: 'fileDataFileName',
            connectionCount: 5,
            leaveConfirm: '正在上传...'
        }
    });
    //显示时候使用
    var content='<{$goods.detail}>';
    editor.setValue(content);
</script>
</html>
