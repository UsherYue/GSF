<?php
$array= [
    /**
     * 完全正则限定rewrite路由
     */
    [
        'regx' => '^/aaa/bbb/?', //路由重写
        'mvc'  => array('controller' => 'page', 'view' => 'index'),//映射到 controller index方法
    ]
];
return $array;

