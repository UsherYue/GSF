<?php

/**
 * 12XueSocietyService
 * TaskQueue.php Created by usher.yue.
 * User: usher.yue
 * Date: 17/1/4
 * Time: 21:31
 * 心怀教育梦－烟台网格软件技术有限公司
 */

class TaskQueue
{
    /**
     * TaskQueue constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param bool|false $async
     * @return swoole_process
     */
    public static function Start($async = false)
    {
        if ($async) {
            return CreateProcess(function (swoole_process $worker) {
                Swoole::getInstance()->event->runWorker(2);
            });
        } else {
            Swoole::getInstance()->event->runWorker(2);
        }

    }

    /**
     * @param $userFunc
     * @param array $param
     * @return mixed
     * @throws \Swoole\Exception\NotFound
     */
    public static function SendEvent($userFunc, $param = [])
    {
        $eventData[]=$userFunc;
        $eventData=array_merge($eventData,$param);
        return Swoole::getInstance()->event->dispatch($eventData);
    }

}