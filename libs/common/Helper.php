<?php
/**
 * PHPProject
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

/**获取配置
 * @param $key
 * @return bool
 */
function C($key)
{
    $obj = createModel('Config')->get($key)->get();
    if ($obj == null) {
        return false;
    } else {
        if (count($obj) == 0) {
            return false;
        } else {
            return $obj['value'];
        }
    }
}

/**获取post.x  get.x  提供一个filter函数可以用来过滤数据
 * @param $prm
 * @param $filter
 */
function I($prm, $filter)
{


    return true;
}

/**
 * 直接通过表名字创建BaseModel
 * @param $table
 */
function M($table_name, $db_key = '')
{
    $include = APPPATH . "models/$table_name.php";
    if (file_exists($include)) {
        require_once $include;
        $className = '\\App\\Model\\' . $table_name;
        if (class_exists($className)) {
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
function http_get($url)
{
    $curl = new CURL();
    $data = $curl->get($url);
    return $data;
}

/**删除数组
 * @param $arr
 * @param $offset
 */
function array_remove(&$arr, $offset)
{
    array_splice($arr, $offset, 1);
}

/**发送post请求
 * @param $url
 * @param $postForm
 * @param null $ip
 * @param int $timeout
 * @return mixed
 */
function http_post($url, $postForm, $ip = null, $timeout = 10)
{
    $curl = new CURL();
    $data = $curl->post($url, $postForm, $ip, $timeout);
    return $data;
}

/**创建进程
 * @param $func
 * @return swoole_process
 */
function CreateProcess($func)
{
    $process = new swoole_process($func);
    //启动检测进程
    $process->start();
    return $process;
}

/**
 * @param $arr
 * @return bool
 */
function is_assoc($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * @param $value
 */
function Dumps($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
}

/**从html文本中提取元素
 * @param $tagname
 * @param $html
 */
function GetDomByTagname($tagname, $html)
{
    preg_match_all("/<\s*" . $tagname . "\s+(([^><]+){1})?\s*>(.*)<\s*\/\s*" . $tagname . "\s*>/uU", $html, $match, PREG_SET_ORDER);
    $count = 0;
    $result = [];
    foreach ($match as $item) {
        preg_match_all("/\w+=\s*[\"']\s*[\S _-]*\s*[\"']\s*/U", $item[1], $subMatch, PREG_SET_ORDER);
        foreach ($subMatch as $v) {
            $tmpStr = trim($v[0]);
            $kv = explode("=", $tmpStr);
            $result[$item[3]][] = [$kv[0] => preg_replace('/[\'""]/', "", trim($kv[1]))];
        }
    }
    return $result;
}



/**key具有唯一性  根据micro second和key产生一个 近乎不重复的值
 * @param $key
 * @return string
 */
function GetUniqueString($key)
{
    return md5(uniqid($key . mt_rand(1, 1000000000) . time()));
}

//
///**获取img src
// * @param $html
// * @param $func
// */
function GetImgSrc($html,$func){
    //最小化匹配
    $c1 = preg_match_all('/<\s*img\s.*?>/', $html, $m1);
    for($i=0; $i<$c1; $i++) {
        $c2 = preg_match_all('/(src)\s*=\s*(?:(?:(["\'])(.*?)(?=\2))|([^\/\s]*))/', $m1[0][$i], $m2);
        for($j=0; $j<$c2; $j++) {
            $src = !empty($m2[4][$j]) ? $m2[4][$j] : $m2[3][$j];
            $func($src);
        }
    }
}

/**获取毫秒级时间戳
 * @return float
 */
function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0d',(floatval($t1)+floatval($t2))*1000);
}
//$str="< img src='xxxx.jpg'/><img src='xxxx.jpg'/>";
//GetImgSrc($str,function($src){
//    echo $src;
//});
//



?>
