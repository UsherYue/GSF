<?php
namespace Swoole\Database;

use Swoole;

/**
 * 数据库代理服务，实现读写分离
 * @package Swoole\Database
 */
class Proxy
{
    /**
     * 强制发往主库
     * @var bool
     */
    public $forceMaster = false;
    protected $config;

    /**
     * @var Swoole\Database
     */
    protected $slaveDB;

    /**
     * @var Swoole\Database
     */
    protected $masterDB;

    const DB_MASTER = 1;
    const DB_SLAVE = 2;

    function __construct($config)
    {
        if (empty($config['slaves'])) {
            throw new LocalProxyException("require slaves options.");
        }
        $this->config = $config;
    }

    protected function getDB($type = self::DB_SLAVE)
    {
        //强制发送主库
        if ($this->forceMaster) {
            goto master;
        }
        //只读的语句
        if ($type == self::DB_SLAVE) {
            if (empty($this->slaveDB)) {
                //连接到从库
                $config = $this->config;
                //从从库中随机选取一个
                $server = Swoole\Tool::getServer($config['slaves']);
                unset($config['slaves'], $config['use_proxy']);
                $config['host'] = $server['host'];
                $config['port'] = $server['port'];
                $this->slaveDB = $this->connect($config);
                //从库检查
                $this->checkConnecting(self::DB_SLAVE);
            }
            return $this->slaveDB;
        } else {
            master:
            if (empty($this->masterDB)) {
                //连接到主库
                $config = $this->config;
                unset($config['slaves'], $config['use_proxy']);
                $this->masterDB = $this->connect($config);
                //主库检查
                $this->checkConnecting(self::DB_MASTER);
            }
            return $this->masterDB;
        }
    }

    /**读写分离链接检查
     * @param int $type
     */
    protected function  checkConnecting($type = self::DB_MASTER)
    {
        CreateProcess(function (\swoole_process $worder) use ($type) {
            swoole_timer_tick(2000, function () use ($type) {
                switch ($type) {
                    case self::DB_MASTER: {
                        !empty($this->masterDB) && $this->masterDB->check_status();
                    }
                    case self::DB_SLAVE: {
                        !empty($this->slaveDB) && $this->slaveDB->check_status();
                    }
                }
            });
        });
    }

    function query($sql)
    {
        $command = substr($sql, 0, 6);
        //只读的语句
        if (strcasecmp($command, 'select') === 0) {
            $db = $this->getDB(self::DB_SLAVE);
        } else {
            //删除强制发送主库的查询
            //$this->forceMaster = true;
            $db = $this->getDB(self::DB_MASTER);
        }
        return $db->query($sql);
    }

    /**
     * @return bool
     */
    function start(){
        return $this->getDB(self::DB_MASTER)->start();
    }

    /**
     * @return bool
     */
    function commit(){
        return $this->getDB(self::DB_MASTER)->commit();
    }

    /**
     * @return bool
     */
    function rollback(){
        return $this->getDB(self::DB_MASTER)->rollback();
    }


    protected function connect($config)
    {
        $db = new Swoole\Database($config);
        $db->connect();
        return $db;
    }

    function __call($method, $args)
    {
        $db = $this->getDB(false);
        return call_user_func_array(array($db, $method), $args);
    }
}

class LocalProxyException extends \Exception
{

}