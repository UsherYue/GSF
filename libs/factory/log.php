<?php
global $php;
if (empty($php->config['log'][$php->factory_key]))
{
    throw new Swoole\Exception\Factory("log->{$php->factory_key} is not found.");
}
$conf = $php->config['log'][$php->factory_key];
if (empty($conf['type']))
{
    $conf['type'] = 'EchoLog';
}
$class = 'Swoole\\Log\\' . $conf['type'];
$log = new $class($conf);
if (!empty($conf['level']))
{
    $log->setLevel($conf['level']);
}
return $log;