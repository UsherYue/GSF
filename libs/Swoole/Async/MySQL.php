<?php
namespace Swoole\Async;

class MySQL {
	public $cl_db_name;
	protected $configmd5;
	/**
	 * max connections for mysql client
	 * @var int $pool_size
	 */
	protected $pool_size;
	/**
	 * number of current connection
	 * @var int $connection_num
	 */
	protected $connection_num = 0;
	/**
	 * idle connection
	 * @var array $idle_pool
	 */
	protected $idle_pool = array();
	/**
	 * work connetion
	 * @var array $work_pool
	 */
	protected $work_pool = array();
	/**
	 * database configuration
	 * @var array $config
	 */
	protected $config = array();
	/**
	 * wait connection
	 * @var \SplQueue
	 */
	protected $wait_queue;

    /**
     * 是否有swoole异步MySQL客户端
     * @var bool
     */
    protected $haveSwooleAsyncMySQL = false;

	/**
	 * @param array $config
	 * @param int $pool_size
	 * @throws \Exception
	 */
	public function __construct(array $config, $pool_size = 100) {
		$this->configmd5 = md5(json_encode($config));
		if (empty($config['host']) ||
			empty($config['database']) ||
			empty($config['user']) ||
			empty($config['password'])
		) {
			throw new \Exception("require host, database, user, password config.");
		}
		if (!function_exists('swoole_get_mysqli_sock')) {
			throw new \Exception("require swoole_get_mysqli_sock function.");
		}
		if (empty($config['port'])) {
			$config['port'] = 3306;
		}
		$this->config = $config;
		$this->pool_size = $pool_size;
        $this->wait_queue = new \SplQueue();
        $this->haveSwooleAsyncMySQL = function_exists('swoole_mysql_query');
	}

	public function setPoolSize($pool_size) {
		if ($this->pool_size == $pool_size) {
			return 0;
		}
		$this->pool_size = $pool_size;
		while ($this->pool_size < $this->connection_num) {
			$conn = array_pop($this->idle_pool);
			if (!$conn) {
				break;
			}
			$this->removeConnection($conn);
		}
		return 1;
	}

	/**
	 * create mysql connection
	 */
    protected function createConnection()
    {
        $config = $this->config;
        $db = new \mysqli;
        $db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
        if ($db->connect_error)
        {
            return [
                $db->connect_errno,
                $db->connect_error
            ];
        }
        if (!empty($config['charset']))
        {
            $db->set_charset($config['charset']);
        }
        $db_sock = swoole_get_mysqli_sock($db);
        //内置客户端不需要加入EventLoop
        if (!$this->haveSwooleAsyncMySQL)
        {
            swoole_event_add($db_sock, array(
                $this,
                'onSQLReady'
            ));
        }
        else
        {
            $db->sock = $db_sock;
        }
        //保存到空闲连接池中
        $this->idle_pool[$db_sock] = array(
            'object' => $db,
            'socket' => $db_sock,
        );
        //增加计数
        $this->connection_num++;
        return 0;
    }

	/**
	 * remove mysql connection
	 * @param $db
     * @return bool
	 */
    protected function removeConnection($db)
    {
        if (isset($this->work_pool[$db['socket']]))
        {
            #不能删除正在工作的连接
            return false;
        }
        if (!$this->haveSwooleAsyncMySQL)
        {
            swoole_event_del($db['socket']);
            $db['object']->close();
        }
        if (isset($this->idle_pool[$db['socket']]))
        {
            unset($this->idle_pool[$db['socket']]);
        }
        $this->connection_num--;
    }

    /**
     * @param $db
     * @param null $_result
     * @return bool
     */
    public function onSQLReady($db, $_result = null)
    {
        $db_sock = $this->haveSwooleAsyncMySQL ? $db->sock : $db;
		$task = empty($this->work_pool[$db_sock]) ? null : $this->work_pool[$db_sock];
        //SQL返回了错误
        if ($_result === false)
        {
            //连接已关闭
            if (empty($task) or ($this->haveSwooleAsyncMySQL and $db->_connected == false))
            {
                unset($this->work_pool[$db_sock]);
                $this->removeConnection($task['mysql']);

                //创建连接成功
                if ($this->createConnection() === 0)
                {
                    $this->doQuery($task['sql'], $task['callback']);
                }
                //连接建立失败，加入到等待队列中
                else
                {
                    $this->wait_queue->push(array(
                        'sql' => $task['sql'],
                        'callback' => $task['callback'],
                    ));
                }
                return;
            }
        }

		/**
		 * @var \mysqli $mysqli
		 */
		$mysqli = $task['mysql']['object'];
		$callback = $task['callback'];

        if ($this->haveSwooleAsyncMySQL)
        {
            call_user_func($callback, $mysqli, $_result);
        }
        else
        {
            $mysqli->_affected_rows = $mysqli->affected_rows;
            $mysqli->_insert_id = $mysqli->insert_id;
            $mysqli->_errno = $mysqli->errno;
            $mysqli->_error = $mysqli->error;

            if ($_sql_result = $mysqli->reap_async_query())
            {
                if ($_sql_result instanceof \mysqli_result)
                {
                    $result = $_sql_result->fetch_all();
                }
                else
                {
                    $result = $_sql_result;
                }
                call_user_func($callback, $mysqli, $result);
                if (is_object($result))
                {
                    mysqli_free_result($result);
                }
            }
            else
            {
                call_user_func($callback, $mysqli, false);
            }
        }

        //release mysqli object
        unset($this->work_pool[$db_sock]);
        if ($this->pool_size < $this->connection_num)
        {
            //减少连接数
            $this->removeConnection($task['mysql']);
        }
        else
        {
            deQueue:
            $this->idle_pool[$task['mysql']['socket']] = $task['mysql'];
            $queue_count = count($this->wait_queue);
            //fetch a request from wait queue.
            if ($queue_count > 0)
            {
                $task_n = count($this->idle_pool);
                if ($task_n > $queue_count)
                {
                    $task_n = $queue_count;
                }
                for ($i = 0; $i < $task_n; $i++)
                {
                    $new_task = $this->wait_queue->shift();
                    $this->doQuery($new_task['sql'], $new_task['callback']);
                }
            }
        }
	}

	/**
	 * @param string $sql
	 * @param callable $callback
     * @return bool
	 */
    public function query($sql, callable $callback)
    {
        //no idle connection
        if (count($this->idle_pool) == 0)
        {
            //创建新的连接
            if ($this->connection_num < $this->pool_size)
            {
                $r = $this->createConnection();
                if ($r)
                {
                    return $r;
                }
                $this->doQuery($sql, $callback);
            }
            //连接数达到最大值，添加到等待队列中
            else
            {
                $this->wait_queue->push(array(
                    'sql' => $sql,
                    'callback' => $callback,
                ));
            }
        }
        else
        {
            $this->doQuery($sql, $callback);
        }
        return 0;
    }

	/**
	 * @param string $sql
	 * @param callable $callback
	 */
    protected function doQuery($sql, callable $callback)
    {
        deQueue:
        //remove from idle pool
        $db = array_pop($this->idle_pool);

        /**
         * @var \mysqli $mysqli
         */
        $mysqli = $db['object'];

        if ($this->haveSwooleAsyncMySQL)
        {
            $result = swoole_mysql_query($mysqli, $sql, array($this, 'onSQLReady'));
        }
        else
        {
            $result = $mysqli->query($sql, MYSQLI_ASYNC);
        }

        if ($result === false)
        {
            if ($mysqli->errno == 2013 or $mysqli->errno == 2006 or (isset($mysqli->_errno) and $mysqli->_errno == 2006))
            {
                $mysqli->close();
                unset($mysqli);
                $this->connection_num --;
                //创建连接成功
                if ($this->createConnection() === 0)
                {
                    goto deQueue;
                }
            }
            else
            {
                $this->wait_queue->push(array(
                    'sql' => $sql,
                    'callback' => $callback,
                ));
                return;
            }
        }

		$task['sql'] = $sql;
		$task['callback'] = $callback;
		$task['mysql'] = $db;
		//join to work pool
		$this->work_pool[$db['socket']] = $task;
	}

	function getKey() {
		#return $this->config['host'] . ':' . $this->config['port'];
		return $this->configmd5;
	}

	function isFree() {
        return (!$this->work_pool && count($this->wait_queue) == 0) ? true : false;
	}

	function getionNum() {
		return $this->connection_num;
	}

	function close() {
		#echo "destruct\n";
		foreach ($this->idle_pool as $conn) {
			$this->removeConnection($conn);
		}
	}

	function __destruct() {
		$this->close();
	}


}