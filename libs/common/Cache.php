<?php
/**
 * 12XueSocietyService
 * CacheService.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/27
 * Time: 下午11:04
 * 心怀教育梦－烟台网格软件技术有限公司
 */


/**mutex
 * @var null
 */
//全局锁
class Cache
{
     public  static  $RedisconnectMutex=null;
    /**
     * @return bool
     */
    public static function  IsConnect()
    {
        global $redis;
        return $redis->isConnect();
    }

    /**
     * @return bool|void
     */
    public static function Connect()
    {
        global $redis;
        return $redis->connect();
    }

    /**
     * @return bool|void
     */
    public static function Close()
    {
        global $redis;
        return $redis->close();
    }

    /**
     * @return mixed
     */
    public static function  GetConfig()
    {
        global $redis;
        return $redis->config;
    }

    /**
     * @param $uid
     * @return mixed
     */
    public static function GetCache($key)
    {
        $result = Swoole::getInstance()->redis->get($key);
        if (!$result) {
            //断线重连
            //try lock
            if(Cache::$RedisconnectMutex->trylock()){
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->get($key);
            }
        }
        return json_decode($result, true);
    }

    /**
     * @param $uid
     * @return mixed
     */
    public static function GetCacheNormal($key)
    {
        $result = Swoole::getInstance()->redis->get($key);
        if (!$result) {
            //断线重连
            //try lock
            if(Cache::$RedisconnectMutex->trylock()){
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->get($key);
            }
        }
        return $result;
    }

    /**key是否存在
     * @param $key
     */
    public static function  Exists($key)
    {
        $bExists = Swoole::getInstance()->redis->exists($key);
        if (!$bExists) {
            //断线重连
            //try lock
            if(Cache::$RedisconnectMutex->trylock()){
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $bExists = Swoole::getInstance()->redis->exists($key);
            }
        }
        return $bExists;
    }


    /**
     * @param $uid
     * @param $data
     * @param int $lifttime
     * @return bool
     */
    public static function  SetCache($key, $data, $lifttime = 300)
    {
        $bSuccess = Swoole::getInstance()->redis->set($key, json_encode($data))
            && Swoole::getInstance()->redis->expire($key, $lifttime);
        if (!$bSuccess) {
            //断线重连
            //try lock
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $bSuccess = Swoole::getInstance()->redis->set($key, json_encode($data))
                    && Swoole::getInstance()->redis->expire($key, $lifttime);
            }
        }
        return $bSuccess;
    }

    /**
     * @param $key
     * @return int
     */
    public static function  Del($key)
    {
        $result = Swoole::getInstance()->redis->del($key);
        if (!$result) {
            //断线重连
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->del($key);
            }
        }
        return $result;
    }

    /**
     * @param $key
     * @param $val
     */
    public static function  RPush($key, $val)
    {
        $result = Swoole::getInstance()->redis->rPush($key, $val);
        if (!$result) {
            //断线重连
            //lock
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->rPush($key, $val);
            }
        }
        return $result;
    }

    /**
     * @param $key
     * @param $val
     */
    public static function  RPop($key)
    {
        $result = Swoole::getInstance()->redis->rPop($key);
        if (!$result) {
            //断线重连
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->rPop($key);
            }
        }
        return $result;
    }

    /**
     * @param $key
     * @param $val
     */
    public static function  LPop($key)
    {
        $result = Swoole::getInstance()->redis->lPop($key);
        if (!$result) {
            //断线重连
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->lPop($key);
            }
        }
        return $result;
    }

    /**
     * @param $key
     * @param $val
     */
    public static function  LPush($key, $val)
    {
        $result = Swoole::getInstance()->redis->lPush($key, $val);
        if (!$result) {
            //断线重连
            //lock
            if(Cache::$RedisconnectMutex->trylock()) {
                (!Cache::IsConnect()) && Cache::Connect();
                Cache::$RedisconnectMutex->unlock();
                $result = Swoole::getInstance()->redis->lPush($key, $val);
           }
        }
        return $result;
    }
}
//设置mutex
Cache::$RedisconnectMutex=new swoole_lock(SWOOLE_MUTEX);