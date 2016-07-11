<?php
//主库
$db['master'] = array(
    'type'       => Swoole\Database::TYPE_MYSQLi,
    'host'       => "172.16.200.204",
    'port'       => 3306,
    'dbms'       => 'mysql',
    'engine'     => 'MyISAM',
    'user'       => "root",
    'passwd'     => "rjgrid",
    'name'       => "12xue_weibo",
    'charset'    => "utf8",
    'setname'    => true,
    'persistent' => false, //MySQL长连接
    'use_proxy'  => true,  //启动读写分离Proxy
    'slaves'     => array(
        array('host' => '172.16.200.205', 'port' => '3306', 'weight' => 100)
    ),
);
//从库
$db['slave'] = array(
    'type'       => Swoole\Database::TYPE_MYSQLi,
    'host'       => "172.16.200.205",
    'port'       => 3306,
    'dbms'       => 'mysql',
    'engine'     => 'MyISAM',
    'user'       => "root",
    'passwd'     => "rjgrid",
    'name'       => "12xue_weibo",
    'charset'    => "utf8",
    'setname'    => true,
    'persistent' => false, //MySQL长连接
);
return $db;