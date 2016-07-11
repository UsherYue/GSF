<?php
if (Swoole::$php->factory_key == 'master' and empty(\Swoole::$php->config['cache']['master']))
{
    Swoole::$php->config['cache']['master'] = array('type' => 'FileCache', 'cache_dir' => WEBPATH . '/cache/filecache');
}
return Swoole\Factory::getCache(Swoole::$php->factory_key);