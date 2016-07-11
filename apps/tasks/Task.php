<?php
/**
 * 定时任务
 * Task.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/6
 * Time: 下午2:56
 * 心怀教育梦－烟台网格软件技术有限公司
 */
const DEFAULT_CHECK_INTERVAL=2000;
class Task{

    public static function StartTask($interval=DEFAULT_CHECK_INTERVAL){
            //创建并且启动进程
            $process=CreateProcess(function(swoole_process $worker) use($interval) {
                //间隔指定时间判断是否过了半夜
                swoole_timer_tick($interval,function(){
                    //今天凌晨
                    $todoayMidnight=strtotime(date('Y-m-d',strtotime('+1 day')));
                    $now=time();
                    //时间已到开始计算
                    if($todoayMidnight<=$now){
                        echo 'doing.....';
                    }else{
                        echo 'undo......' ;
                    }
                });
            });
          return $process ;
    }


}