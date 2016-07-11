<?php
namespace Swoole;

use Swoole\Network\Server;

class Event
{
    /**
     * @var IFace\Queue
     */
	protected $_queue;
    protected $_handles = array();
    protected $config;
    protected $async = false;

    function __construct($config)
    {
        $this->config = $config;
        //同步模式，直接执行函数
        if (isset($config['async']) and $config['async'])
        {
            $class = $config['type'];
            if (!class_exists($class))
            {
                throw new Exception\NotFound("class $class not found.");
            }
            $this->_queue = new $class($config);
            $this->async = true;
        }
    }

    /**
     * 投递事件
     * @return mixed
     * @throws Exception\NotFound
     */
	function dispatch()
	{
        $_args = func_get_args();
        $function = $_args[0];
		/**
		 * 同步，直接在引发事件时处理
         */
        if (!$this->async)
        {
            if (!is_callable($function))
            {
                throw new Exception\NotFound("function $function not found.");
            }
            return call_user_func_array($function, array_slice($_args, 1));
        }
        /**
         * 异步，将事件压入队列
         */
        else
        {
            return $this->_queue->push($_args);
        }
	}

    /**
     * 运行工作进程
     */
	function runWorker($worker_num = 1)
    {
        if (empty($this->config['logger']))
        {
            $logger = new Log\EchoLog(array('display' => true));
        }
        else
        {
            /**
             * Swoole\Log
             */
            $logger = \Swoole::$php->log($this->config['logger']);
        }

        while (true)
        {
            $event = $this->_queue->pop();
            if ($event)
            {
                $function = $event[0];
                if (!is_callable($function))
                {
                    $logger->info('function [' . $function . '] not found.');
                }
                else
                {
                    $params = array_slice($event, 1);
                    call_user_func_array($function, $params);
                }
            }
            else
            {
                usleep(100000);
            }
        }
	}
}
