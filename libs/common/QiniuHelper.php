<?php
/**
 *   QiniuHelper JavaScript PHP七牛上传Token封装
 */

use Qiniu\Auth;
use  Qiniu\Processing\PersistentFop;
/**
 * Class QiniuHelper
 */
class QiniuHelper{
    private     $ACCESS_KEY = null;
    private     $SECRET_KEY = null;
    public function __construct($ACCESS_KEY,$SECRET_KEY){
        $this->ACCESS_KEY=$ACCESS_KEY ;
        $this->SECRET_KEY=$SECRET_KEY ;
    }
    //获取普通的上传Token返回给前端用
    public  function  UploadToken($bucket,$expires=3600,$policy=null){
        if(!$this->ACCESS_KEY||!$this->SECRET_KEY||!$bucket){
            throw new Exception("KEY错误!") ;
        }
        $auth = new Auth($this->ACCESS_KEY,$this->SECRET_KEY);
        $jsonResult['uptoken']=$auth->uploadToken($bucket,null,$expires,$policy);
        return json_encode($jsonResult);
    }
    //获取七牛视频上传预先转码的  UploadToken ,集成 上传  转码
    //时间默认 3600s
    public   function  GetVideoTranscodeToken($bucket,$notifyUrl,$expires=3600,$outputFormat='mp4',$videoEncoder="libx264",$audioEncoder='libfaac'){
        $PERSISTENT_OPS ="avthumb/".strtolower($outputFormat)."/acodec/$audioEncoder/vcodec/$videoEncoder";
        echo  $PERSISTENT_OPS;
        $policky['persistentNotifyUrl'] =$notifyUrl  ;
        $policky['persistentOps']=$PERSISTENT_OPS ;
        return $this->UploadToken($bucket,$expires,$policky);
    }
    //对于转码后的视频获取截图 参数  拼接在 转码后的视频URL中
    //缩略图 png 或者jpg
    //vframe/jpg/offset/2/w/480/h/360
    public  function GetVideoThumbPrm($thumbFormat='jpg',$thumbFrameOffset=2,$thumbWidth=480,$thumbHeight=360){
        return  "vframe/$thumbFormat/offset/$thumbFrameOffset/w/$thumbWidth/h/$thumbHeight" ;
    }
    //获取转码后的视频信息
    public function GetAvinfo($videoUrl){
        $requestUrl=$videoUrl .'?avinfo' ;
        return $this->SimpleCurlGet($requestUrl) ;
    }
    //简单的GET请求
    public function SimpleCurlGet($url){
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_URL,$url) ;
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl) ;
        return  $output ;
    }
    //获取私有链接下载
    //待完善
    public  function GetPrivateDownloadURL($url){
        if(!$this->ACCESS_KEY||!$this->SECRET_KEY){
            throw new Exception("KEY错误!") ;
        }
        $auth = new Auth($this->ACCESS_KEY,$this->SECRET_KEY);
        return $auth->privateDownloadUrl($url);
    }
    //获取七牛 文档上传的预先转码的UploadToken  集成上传 转码 到 PDF于一体。
    public   function GetDocTranscodeToken($bucket,$notifyUrl,$expires=3600,$outputFormat='pdf'){
        $PERSISTENT_OPS="yifangyun_preview/v2";
        $policky['persistentNotifyUrl'] =$notifyUrl  ;
        $policky['persistentOps']=$PERSISTENT_OPS ;
        return   $this->UploadToken($bucket,$expires,$policky);;
    }
    //对普通上传的文档进行触发预处理
    // 返回 false  和 预处理ID
    public function PreprocessDoc($bucket,$key,$notifyUrl,$pipeline='transcode'){
        $fops = "yifangyun_preview/v2";
        return  $this->__preprocess($bucket,$key,$fops,$notifyUrl,$pipeline);
    }
    //视频预处理
    public function PreprocessMultiMedia($bucket,$key,$notifyUrl,$pipeline='transcode'){
        $fops ="avthumb/mp4/acodec/libfaac/vcodec/libx264";
        return  $this->__preprocess($bucket,$key,$fops,$notifyUrl,$pipeline);
    }

    //$id   查询转码结果 利用转码的ID
    //返回字段中的json
    // key 转码后的文件名字
    //hash	是	云处理结果保存在服务端的唯一hash标识。  可以做秒传 利用MD5计算
    //code	是	状态码，0（成功），1（等待处理），2（正在处理），3（处理失败），4（通知提交失败）。
    public static function Status($id){
        return PersistentFop::status($id);
    }

    //预处理返回结果
    private function __preprocess($bucket,$key,$fops,$notifyUrl,$pipeline){
        if(!$this->ACCESS_KEY||!$this->SECRET_KEY){
            throw new Exception("KEY错误!") ;
        }
        $auth = new Auth($this->ACCESS_KEY,$this->SECRET_KEY);
        $pfop = new PersistentFop($auth, $bucket, $pipeline, $notifyUrl);
        list($id, $err) = $pfop->execute($key, $fops);
        if ($err != null) {
            var_dump($err);
            return false ;
        } else {
            return $id ;
        }
    }
}