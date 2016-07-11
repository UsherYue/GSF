<?php
$cache['session'] = array(
    'type' => 'FileCache',
    'cache_dir' => realpath(__DIR__).'/../cache/filecache/',
);
$cache['master'] = array(
    'type' => 'FileCache',
    'cache_dir' => realpath(__DIR__).'/../cache/filememcache/',
);
return $cache;