<?php
/**
 * 12XueSocietyService
 * CacheService.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/27
 * Time: 下午11:04
 * 心怀教育梦－烟台网格软件技术有限公司
 */

/**缓存服务
 * Class Cache
 */
class Cache
{

    /**
     * @param $uid
     * @return mixed
     */
    public static function GetCache($key)
    {
        return json_decode(Swoole::getInstance()->redis->get($key), true);
    }

    /**可以是否存在
     * @param $key
     */
    public  static  function  Exists($key){
        return Swoole::getInstance()->redis->exists($key);
    }

    /**
     * 切换数据库
     */
    public  static function  select($index){
        return Swoole::getInstance()->redis->select($index);
    }

    /**
     * @param $uid
     * @param $data
     * @param int $lifttime
     * @return bool
     */
    public static function  SetCache($key, $data, $lifttime = 300)
    {
        return Swoole::getInstance()->redis->set($key, json_encode($data))
        && Swoole::getInstance()->redis->expire($key, $lifttime);
    }

    /**
     * @param $key
     * @return int
     */
    public  static  function  Del($key){
        return   Swoole::getInstance()->redis->del($key);
    }

    /**
     * @param $key
     * @param $val
     */
    public  static function  RPush($key,$val){
        return Swoole::getInstance()->redis->rPush($key,$val);
    }

    /**
     * @param $key
     * @param $val
     */
    public  static function  RPop($key){
        return Swoole::getInstance()->redis->rPop($key);
    }

}