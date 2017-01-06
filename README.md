GSF unstable v0.1
=================
Build By usher.yue<br/> 
基于Swoole框架的封装扩展和完善,Swoole由于其文档太少,难度对于PHP程序员来说过于大,很多php程序员敬而远之。<br/> 
GSF框架就是为了让PHP程序员更简单的使用Swoole来开发自己的应用程序。<br/> 
====
# 入口文件代码
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
	//关闭debug
	Config::$debug = false;
	//设置PID文件的存储路径
	Server::setPidFile(__DIR__ . '/logs/http_server.pid');

	/**
	 * 启动app server  独立于nginx apache
	 * php http_server.php start|stop|reload   //配置默认路由
	 */
	Server::start(function()
	{
  	  $server = WebServer::create(CONFIGFILE);
 	   //设置app的configs
  	  $server->setAppPath(APPPATH);
  	  $server->setLogger(new EchoLog(LOGFILE));
   	 //作为守护进程  生产环境开启 设置DEAMON=true
   	 if(DEAMON){
   	     $server->daemonize();
   	 }
    //启动任务
    //$Task=Task::StartHomeworkCompletionCalcTask();
    //kill task
    //$Task->kill();
    //启动服务
    $server->run(array('worker_num' =>1, 'react_num'=>2, 'max_request' => 500000, 'log_file' => LOGFILE));
	});

