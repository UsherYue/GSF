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
class TaskQueue
{
    private $queueName='';
    /**
     * @param $queueName
     */
    function  TaskQueue($queueName){
        $this->queueName=$queueName;
    }

    /**task callback
     * @param $v
     */
    function  TaskCallback($v){

    }

    /**
     * @param $k
     * @param $v
     */
    function LPush($v){

    }


    /**
     * @param $k
     */
    function LPop(){

    }

    /**
     * @param $k
     * @param $v
     */
    function RPush($v){

    }

    /**
     * @param $k
     */
    function RPop(){

    }

    /**clear queue
     * @param $k
     */
    function Clear(){

    }
}