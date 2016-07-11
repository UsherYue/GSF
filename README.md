GSF 1.0.1.1 Beta版本     
==================
Build By usher.yue<br/> 
基于Swoole框架的封装扩展和完善,Swoole由于其文档太少,难度对于PHP程序员来说过于大,很多php程序员敬而远之。<br/> 
GSF框架就是为了让PHP程序员更简单的使用Swoole来开发自己的应用程序。<br/> 
====

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