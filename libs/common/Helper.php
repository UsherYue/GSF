<?php
/**
 * GridSwooleFramework
 * Helper.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/5
 * Time: 下午5:27
 * 心怀教育梦－烟台网格软件技术有限公司
 */
/*
 * 获取配置
 */
use App\Model;
use App\Model\BaseModel;
use Swoole\Client\CURL;



/**获取post.x  get.x  提供一个filter函数可以用来过滤数据
 * @param $prm
 * @param $filter
 */
function I($prm,$filter){





    return true;
}

/**
 * 直接通过表名字创建BaseModel
 * @param $table
 */
function M($table_name,$db_key='master'){
    $include=APPPATH."models/$table_name.php";
    if(file_exists($include)){
        require_once $include ;
        $className='\\App\\Model\\'.$table_name;
        if(class_exists($className)){
            return new $className(Swoole::getInstance()->model->swoole, $db_key);
        }
    }
    //load virtual
    $virtualModel = new BaseModel(Swoole::getInstance()->model->swoole, $db_key);
    $virtualModel->table = $table_name;
    return $virtualModel;
}

/**发送get请求
 * @param $url
 * @return string
 */
function http_get($url){
    $curl=new CURL();
    $data=$curl->get($url);
    return $data;
}

/**发送post请求
 * @param $url
 * @param $postForm
 * @param null $ip
 * @param int $timeout
 * @return mixed
 */
function http_post($url,$postForm, $ip = null, $timeout = 10){
    $curl=new CURL() ;
    $data=$curl->post($url, $postForm, $ip, $timeout );
    return data;
}

/**创建进程
 * @param $func
 * @return swoole_process
 */
function CreateProcess($func){
    $process=new swoole_process($func);
    //启动检测进程
    $process->start() ;
    return $process;
}

/**
 * @param $arr
 * @return bool
 */
function is_assoc($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}

