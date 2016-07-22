GSF 1.0.1.1 Beta版本     
==================
Build By usher.yue<br/> 
基于Swoole框架的封装扩展和完善,Swoole由于其文档太少,难度对于PHP程序员来说过于大,很多php程序员敬而远之。<br/> 
GSF框架就是为了让PHP程序员更简单的使用Swoole来开发自己的应用程序。<br/> 
====
# 入口文件代码

	/**
	 *                            _ooOoo_
 	 *                           o8888888o
	 *                           88" . "88
	 *                           (| -_- |)
	 *                            O\ = /O
 	 *                        ____/`---'\____
	 *                      .   ' \\| |// `.
 	 *                       / \\||| : |||// \
	 *                     / _||||| -:- |||||- \
	 *                       | | \\\ - /// | |
 	 *                     | \_| ''\---/'' | |
 	 *                      \ .-\__ `-` ___/-. /
	 *                   ___`. .' /--.--\ `. . __
	 *                ."" '< `.___\_<|>_/___.' >'"".
	 *               | | : `- \`.;`\ _ /`;.`/ - ` : | |
	 *                 \ \ `-. \_ __\ /__ _/ .-` / /
	 *         ======`-.____`-.___\_____/___.-`____.-'======
	 *                            `=---='
	 *         .............................................
	 *                  佛祖保佑             永无BUG
 	*/
	//    编写PHP7+代码后需要运行在>=php7.0.0获取性能提升  
	if (version_compare("7.0.0", PHP_VERSION, ">")>0) {  
	       die("PHP Version 7.0.0 or greater is required!!!");  
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


# linux 内核参数调整
###内核参数调整
	ulimit设置
	ulimit -n 要调整为100000甚至更大	
		
	命令行下执行 ulimit -n 100000即可修改。	如果不能修改，需要设置 /etc/security/	limits.conf，加入
	* soft nofile 262140
	* hard nofile 262140
	root soft nofile 262140
	root hard nofile 262140
	* soft core unlimited
	* hard core unlimited
	root soft core unlimited
	root hard core unlimited	
	内核设置

	net.unix.max_dgram_qlen = 100

	swoole使用unix socket dgram来做进程间通信，如果请求量很大，需要调整此参数。系统默认为10，可以设置为100或者更大。
	或者增加worker进程的数量，减少单个worker进程分配的请求量。
	net.core.wmem_max

	修改此参数增加socket缓存区的内存大小

	net.ipv4.tcp_mem  =   379008       505344  758016
	net.ipv4.tcp_wmem = 4096        16384   4194304
	net.ipv4.tcp_rmem = 4096          87380   419430
	net.core.wmem_default = 8388608
	net.core.rmem_default = 8388608
	net.core.rmem_max = 16777216
	net.core.wmem_max = 16777216
	net.ipv4.tcp_tw_reuse

	是否socket reuse，此函数的作用是Server重启时可以快速重新使用监听的端口。如果没有设置此参数，会导致server重启时发生端口未及时释放而启动失败

	net.ipv4.tcp_tw_recycle

	使用socket快速回收，短连接Server需要开启此参数

	消息队列设置

	当使用消息队列作为进程间通信方式时，需要调整此内核参数

	kernel.msgmnb = 4203520，消息队列的最大字节数
	kernel.msgmni = 64，最多允许创建多少个消息队列
	kernel.msgmax = 8192，消息队列单条数据最大的长度
	FreeBSD/MacOS

	sysctl -w net.local.dgram.maxdgram=8192
	sysctl -w net.local.dgram.recvspace=200000 修改Unix Socket的buffer区尺寸
	开启CoreDump

	设置内核参数

	kernel.core_pattern = /data/core_files/core-%e-%p-%t
	通过ulimit -c命令查看当前coredump文件的限制

	ulimit -c
	如果为0，需要修改/etc/security/limits.conf，进行limit设置。

	开启core-dump后，一旦程序发生异常，会将进程导出到文件。对于调查程序问题有很大的帮助
	其他重要配置

	net.ipv4.tcp_syncookies=1
	net.ipv4.tcp_max_syn_backlog=81920
	net.ipv4.tcp_synack_retries=3
	net.ipv4.tcp_syn_retries=3
	net.ipv4.tcp_fin_timeout = 30
	net.ipv4.tcp_keepalive_time = 300
	net.ipv4.tcp_tw_reuse = 1
	net.ipv4.tcp_tw_recycle = 1
	net.ipv4.ip_local_port_range = 20000 65000
	net.ipv4.tcp_max_tw_buckets = 200000
	net.ipv4.route.max_size = 5242880
	查看配置是否生效

	如：修改net.unix.max_dgram_qlen = 100后，通过

	cat /proc/sys/net/unix/max_dgram_qlen
	如果修改成功，这里是新设置的值。
