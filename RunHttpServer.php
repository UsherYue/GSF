<?php
/**
 * GridSwooleFramework
 * RunHttpServer.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/6/27
 * Time: 下午4:05
 * 心怀教育梦－烟台网格软件技术有限公司
 */
//    编写PHP7+代码后需要运行在>=php7.0.0获取性能提升
//    if (version_compare("7.0.0", PHP_VERSION, ">")>0) {
//        die("PHP Version 7.0.0 or greater is required!!!");
//屏蔽警告
error_reporting(E_ERROR);
use Swoole\Network\Server;
use Swoole\Log\EchoLog;
use Swoole\Config;
use Swoole\Protocol\WebServer;
define('DEAMON',false);
//MVC define 加载MVC扩展插件
define('MVCAPP',true) ;
//定义web路径
define('WEBPATH', realpath(__DIR__ ));
//config file 自定义
define('CONFIGFILE',WEBPATH.'/config/http_config.ini');
//Server Log File
define('LOGFILE',WEBPATH . '/logs/server.log');
//定义APPPATH
define('APPPATH',WEBPATH . '/apps/');
//包含配置文件
require WEBPATH . '/libs/lib_config.php';
//全局配置
require __DIR__ . '/apps/configs/global.php';

//关闭debug
Config::$debug = true;
//设置PID文件的存储路径
Server::setPidFile(__DIR__ . '/logs/http_server.pid');
/**
 * 启动app server  独立于nginx apache
 * php http_server.php start|stop|reload   //配置默认路由
 */
Server::start(function(){
    $server = WebServer::create(CONFIGFILE);
    //设置app的configs
    $server->setAppPath(APPPATH);
    $server->setLogger(new EchoLog(LOGFILE));
    //作为守护进程  生产环境开启 设置DEAMON=true
    if(DEAMON){
        $server->daemonize();
    }
    //启动任务
    //$Task=Task::StartTask();
    //启动服务

    $server->run(array('worker_num' =>1, 'react_num'=>2, 'max_request' => 500000, 'log_file' => LOGFILE));
});

