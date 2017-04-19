<?php
namespace Mynote\Controller;
use Think\Controller;

//批量下载图片
class Batch_downController extends Controller
{
    public $datasec = array ();
    public $ctrl_dir = array ();
    public $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    public $old_offset = 0;

    public function unix2_dostime($unixtime = 0){
        $timearray = ($unixtime == 0) ? getdate () : getdate($unixtime);
        if ($timearray ['year'] < 1980){
            $timearray ['year'] = 1980;
            $timearray ['mon'] = 1;
            $timearray ['mday'] = 1;
            $timearray ['hours'] = 0;
            $timearray ['minutes'] = 0;
            $timearray ['seconds'] = 0;
        }
        return (($timearray ['year'] - 1980) << 25) | ($timearray ['mon'] << 21) | ($timearray ['mday'] << 16) | ($timearray ['hours'] << 11) | ($timearray ['minutes'] << 5) | ($timearray ['seconds'] >> 1);
    }
    public function add_file($data, $name, $time = 0){
        $name = str_replace('\\', '/', $name);

        $dtime = dechex($this->unix2_dostime($time));
        $hexdtime = '\x' . $dtime [6] . $dtime [7] . '\x' . $dtime [4] . $dtime [5] . '\x' . $dtime [2] . $dtime [3] . '\x' . $dtime [0] . $dtime [1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr = "\x50\x4b\x03\x04";
        $fr .= "\x14\x00";
        $fr .= "\x00\x00";
        $fr .= "\x08\x00";
        $fr .= $hexdtime;

        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata)- 4), 2);
        $c_len = strlen($zdata);
        $fr .= pack('V', $crc);
        $fr .= pack('V', $c_len);
        $fr .= pack('V', $unc_len);
        $fr .= pack('v', strlen($name));
        $fr .= pack('v', 0);
        $fr .= $name;

        $fr .= $zdata;
        $fr .= pack('V', $crc);
        $fr .= pack('V', $c_len);
        $fr .= pack('V', $unc_len);

        $this->datasec [] = $fr;

        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";
        $cdrec .= "\x14\x00";
        $cdrec .= "\x00\x00";
        $cdrec .= "\x08\x00";
        $cdrec .= $hexdtime;
        $cdrec .= pack('V', $crc);
        $cdrec .= pack('V', $c_len);
        $cdrec .= pack('V', $unc_len);
        $cdrec .= pack('v', strlen($name));
        $cdrec .= pack('v', 0);
        $cdrec .= pack('v', 0);
        $cdrec .= pack('v', 0);
        $cdrec .= pack('v', 0);
        $cdrec .= pack('V', 32);

        $cdrec .= pack('V', $this->old_offset);
        $this->old_offset += strlen($fr);

        $cdrec .= $name;

        $this->ctrl_dir[] = $cdrec;
    }
    public function add_path($path, $l = 0){
        $d = @opendir($path);
        $l = $l > 0 ? $l : strlen($path) + 1;
        while($v = @readdir($d)){
            if($v == '.' || $v == '..'){
                continue;
            }
            $v = $path . '/' . $v;
            if(is_dir($v)){
                $this->add_path($v, $l);
            } else {
                $this->add_file(file_get_contents($v), substr($v, $l));
            }
        }
    }
    public function file(){
        $data = implode('', $this->datasec);
        $ctrldir = implode('', $this->ctrl_dir);
        return $data . $ctrldir . $this->eof_ctrl_dir . pack('v', sizeof($this->ctrl_dir)) . pack('v', sizeof($this->ctrl_dir)) . pack('V', strlen($ctrldir)) . pack('V', strlen($data)) . "\x00\x00";
    }

    public function add_files($files){
        foreach($files as $file){
            if (is_file($file)){
                $data = implode("", file($file));
                $this->add_file($data, $file);
            }
        }
    }
    public function output($file){
        $fp = fopen($file, "w");
        fwrite($fp, $this->file ());
        fclose($fp);
    }

    //触发下载，直接调用
    public function batch_down(){
        $info=M(C('NCL_REG_GET'),'',C('NCL'));
        $rid=I('get.id');
        //从数据库获取需要下载的图片
        $qrcodes=$info->WHERE(['reg_id'=>$rid])->SELECT();
        if(!empty($qrcodes)){
            $dfile = tempnam('./Public/batch', 'tmp');//产生一个临时文件，用于缓存下载文件，（最后需要删除）
            $filename = '二维码图片.zip'; //下载的默认文件名，（可自定义）
            //将从数据库里获取的图片，循环放到数组里
            foreach($qrcodes as $key=>$value){
                $image[]=array(
                    'image_src'=>$value['imgurl'],
                    'image_name'=>pathinfo($value['imgurl'],PATHINFO_BASENAME)
                );
            }
            //实例:
//                $image = array(
//                    array('image_src' => 'http://adm.nongchanlian.com/Public/img/code/code20170458e778351b6e9.png', 'image_name' => '图片1.jpg'),
//                    array('image_src' => 'http://adm.nongchanlian.com/Public/img/code/code20170458e7783520d2a.png', 'image_name' => 'pic/图片2.jpg'),
//                );

            foreach($image as $v){
                $this->add_file($this->getcurl($v['image_src']), $v['image_name']);
                // 添加打包的图片，第一个参数是图片内容，第二个参数是压缩包里面的显示的名称, 可包含路径
                // 或是想打包整个目录 用 $zip->add_path($image_path);
            }
            $this->output($dfile);
            // 下载文件
            ob_clean();
            header('Pragma: public');
            header('Last-Modified:'.gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control:no-store, no-cache, must-revalidate');
            header('Cache-Control:pre-check=0, post-check=0, max-age=0');
            header('Content-Transfer-Encoding:binary');
            header('Content-Encoding:none');
            header('Content-type:multipart/form-data');
            header('Content-Disposition:attachment; filename="'.$filename.'"'); //设置下载的默认文件名
            header('Content-length:'. filesize($dfile));
            $fp = fopen($dfile, 'r');
            while(connection_status() == 0 && $buf = @fread($fp, 8192)){
                echo $buf;
            }
            fclose($fp);
            @unlink($dfile);
            @flush();
            @ob_flush();
        }else{
            $this->error('下载失败');
        }
    }


    public function getcurl($url, $type = 'get', $arr = '')
    {
        $ch = curl_init();
        //设置1表示存入变量，设置0的话表示直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        //禁用后cURL将终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            echo '错误' . curl_errno($ch);
        }
        curl_close($ch);
        return $res;
    }


}