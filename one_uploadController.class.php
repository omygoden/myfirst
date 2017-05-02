<?php

/**
 *上传图片
 */
function uploadimg(){
    if(!empty($_FILES)){
        $config = array(
            'maxSize'       =>  0, //上传的文件大小限制 (0-不做限制)
            'exts'          =>  array('jpg','jpeg','png'), //允许上传的文件后缀
            'rootPath'      =>  './Public/uploadimg/' //保存根路径
        );
        $upload=new \Think\Upload($config);
        $res=$upload->upload($_FILES);
        $res=$res['uploadimg'];
        if($res){
            $img_url=C('MYURL').ltrim($upload->rootPath,'./').$res['savepath'].$res['savename'];
            /*
             * 如果需要上传到oss则是：$oss->addossimg($upload->rooPth.$res['savepath'].$res['savename']);
             *
             */
            $this->ajaxReturn(['code'=>'1001','result'=>$img_url]);
        }else{
            $this->ajaxReturn(['code'=>'1002','result'=>'上传失败']);
        }
    }
}
?>
<html>
<div class="form-group">
    <label class="col-sm-2 control-label">*商品标题图片</label>
    <div class="col-sm-10">
        <div class="btn-file">
            <label for="uploadimg" class="btn btn-primary" >上传图片</label>
            <input id="uploadimg" onchange="upload()" style="display:none" type="file" name="uploadimg" >
            <input id="title_img" type="hidden" name="title_img" >
            <!--<span class="help-block m-b-none">请上传图片大小为400*400px</span>-->
        </div>
        <div  class="product_img" id="img">
            <img  src="__PUBLIC__/admin/img/webuploader.png" style="height:150px;width: 150px">
        </div>
    </div>
</div>

<script src="__PUBLIC__/admin/js/ajaxfileupload.js"></script>
<script>
    //注意：id名如果和onchange函数名相同的话，将会报错。
    function upload(){
        var tourl='__MODULE__/Mypublic/uploadimg';
        $.ajaxFileUpload({
            url:tourl,//需要链接到服务器地址
            secureuri:false,
            fileElementId:'uploadimg',//文件选择框的id属性file的id
            dataType:'json',
            success:function(data){
//                console.log(data);
                if(data.code=='1001'){
                    var html='<img  style="width:15rem" src="'+data.result+'" />';
                    $('#img').html(html);
                    $('#title_img').val(data.result);
                }else{
                    if(data.status=='0'){
                        layer.alert(data.info);
                    }else{
                        layer.alert(data.result);
                    }
                }
            }
        });
    }
</script>
</html>
