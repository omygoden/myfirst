<?php
namespace Mynote\Controller;
use Think\Controller;

//批量下载图片333
class many_img_downController extends Controller
{
    public function many_img_down(){
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

// Support CORS
// header("Access-Control-Allow-Origin: *");
// other CORS headers if any...
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }

        if ( !empty($_REQUEST[ 'debug' ]) ) {
            $random = rand(0, intval($_REQUEST[ 'debug' ]) );
            if ( $random === 0 ) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }

// header("HTTP/1.0 500 Internal Server Error");
// exit;


// 5 minutes execution time
        @set_time_limit(5 * 60);

// Uncomment this one to fake upload time
        usleep(5000);

// Settings
// $targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
        $targetDir = './Public/many_uploadimg/upload_tmp'; //缓存路径（最后需删除）
        $uploadDir = './Public/many_uploadimg/upload';  //图片上传路径（可放服务器也可放oss）

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds


// Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

// Create target dir
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir);
        }

// Get a file name
        if (isset($_REQUEST["name"])) {
            $fileName = time().rand(100,999).'.'.pathinfo($_REQUEST["name"],PATHINFO_EXTENSION);
        } elseif (!empty($_FILES)) {
            $fileName = time().rand(100,999).'.'.pathinfo($_FILES["file"]["name"],PATHINFO_EXTENSION);
        } else {
            $fileName = uniqid("file_");
        }

        $md5File = @file('md5list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $md5File = $md5File ? $md5File : array();

        if (isset($_REQUEST["md5"]) && array_search($_REQUEST["md5"], $md5File ) !== FALSE ) {
            die('{"jsonrpc" : "2.0", "result" : null, "id" : "id", "exist": 1}');
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

// Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;


// Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }


// Open temp file
        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");

        $index = 0;
        $done = true;
        for( $index = 0; $index < $chunks; $index++ ) {
            if ( !file_exists("{$filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }
        if ( $done ) {
            if (!$out = @fopen($uploadPath, "wb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }

            if ( flock($out, LOCK_EX) ) {
                for( $index = 0; $index < $chunks; $index++ ) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }

                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }

                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }

                flock($out, LOCK_UN);
            }
            @fclose($out);
        }
        $uploadPath=C('MYURL').ltrim($uploadPath,'./');
//        die('{"jsonrpc" : "2.0", "error" : {"code": 1, "message": '.$uploadPath.'}, "id" : "id"}');
        //返回图片地址（上传一张图片执行一次，多张图片一次性上传，将是循环执行）
        echo json_encode(['code'=>'1001','result'=>$uploadPath]);
    }
}
?>
<html>
<!--必备库-->
<script src="__PUBLIC__/admin/upload_imgs/jquery.js"></script>
<link rel="stylesheet" type="text/css" href="__PUBLIC__/admin/upload_imgs/diyUpload/css/webuploader.css">
<link rel="stylesheet" type="text/css" href="__PUBLIC__/admin/upload_imgs/diyUpload/css/diyUpload.css">
<script type="text/javascript" src="__PUBLIC__/admin/upload_imgs/diyUpload/js/webuploader.html5only.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/admin/upload_imgs/diyUpload/js/diyUpload.js"></script>

<div class="form-group" id="boxs">

    <label class="col-sm-2 control-label">商品详情图片</label>
<!--    页面加载的时候将会把内容加载到 id='box' 的div里-->
    <div id="box" style="margin-left:15px">
        <div id="test"><img src="__PUBLIC__/admin/img/webuploader.png"
                            style="height:150px;width: 150px"></div>
    </div>
<!--    这个div主要用来显示更新时候的图片，以便于删除操作-->
    <div class="parentFileBox" style="width: 100%">
        <ul class="fileBoxUl">
            <foreach name="goods.detail_imgs" item="detail">
                <li id="fileBox_WU_FILE_0" class="diyUploadHover">
                    <div class="viewThumb"><img class="old_img" src="<{$detail.img}>"></div>
                    <div class="diyCancel" id="imgs<{$detail.id}>" onclick="del_goods_img('<{$detail.id}>',this.id);"></div>
                </li>
            </foreach>
        </ul>
    </div>

</div>

<script>
    //加载图片容器
    $('#test').diyUpload({
        url: '__MODULE__/indexadm.php/Public/many_uploadimg',
        success: function (data) {
            if (data.code == '1001') {
                $('#boxs').append('<input class="detail" name="detail_imgs[]" value="' + data.result + '" type="hidden" >');
            } else {
                var d = data.error;
                layer.alert(d.message);
            }
        },
        error: function (err) {
//            console.info( err );
        },
//        设置参数
        accept: {
            title: "Images",
            extensions: "gif,jpg,jpeg,bmp,png",
            mimeTypes: "image/gif,image/jpg,image/jpeg,image/bmp,image/png"
        }
    });

    //删除图片操作
    function del_goods_img(id,imgid){
        var check=layer.confirm('是否确定删除？');
        if(!check){
            return false;
        }
        $.post('__MODULE__/indexadm.php/Goods/del_goods_img',{gid:id},function(data){
            if(data.code=='1001'){
                $('#'+imgid).parent().remove();
                layer.alert(data.result);
            }else{
                if(data.status=='0'){
                    layer.alert(data.info);
                }else{
                    layer.alert(data.result);
                }
            }
        });
    }

</script>
</html>
