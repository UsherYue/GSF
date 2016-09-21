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
 * global $php;
 * global $redis;
 * $php->loadModule('redis');
 * $redis->isConnect();
 * $redis->isConnect();
 * $redis->isConnect();
 * $redis->isConnect();
 * $redis->close();
 * $redis->isConnect();
 * $redis->isConnect();
 * $redis->connect();
 * $redis->isConnect();
 * $redis->isConnect();
 * die(1);
 *
 * global $php;
 * global $redis;
 * $php->loadModule('redis');
 * Cache::SetCache('sss',11111);
 * var_dump($redis->isConnect());
 * var_dump(Cache::GetCache('sss'));
 * $redis->close();
 * var_dump(Cache::IsConnect());
 * var_dump(Cache::GetCache('sss'));
 * var_dump(Cache::IsConnect());
 *
 * global $php;
 * global $redis;
 * $php->loadModule('redis');
 * Cache::LPush('a',1);
 * Cache::Close();
 * var_dump(Cache::IsConnect());
 * var_dump(Cache::LPop('a'));
 * var_dump(Cache::IsConnect());
 */
class Cache
{

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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->get($key);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->get($key);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $bExists = Swoole::getInstance()->redis->exists($key);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $bSuccess = Swoole::getInstance()->redis->set($key, json_encode($data))
                && Swoole::getInstance()->redis->expire($key, $lifttime);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->del($key);
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
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->rPush($key, $val);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->rPop($key);
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
            //lock
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->lPop($key);
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
            $lock = new swoole_lock(SWOOLE_MUTEX);
            $lock->lock();
            (!Cache::IsConnect()) && Cache::Connect();
            $lock->unlock();
            $result = Swoole::getInstance()->redis->lPush($key, $val);
        }
        return $result;
    }

}