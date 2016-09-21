<?php

/**
 * 12XueSocietyService
 * TaskQueue.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/8/28
 * Time: 下午3:59
 * 心怀教育梦－烟台网格软件技术有限公司
 */

/**任务队列
 * Class TaskQueue
 */
abstract class TaskQueue
{
    /**
     * @param $queueName
     */
    function  TaskQueue(){}

    /**task async callback
     * @param $v
     */
    abstract  function  AsyncTaskCallback($v);

    /**
     * @param $task
     * @return mixed
     */
    abstract  function  PushTask($task);

    /**清除task
     * @return mixed
     */
    abstract  function Clear();
}