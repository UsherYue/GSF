<?php
global $php;

$config = $php->config['event'][$php->factory_key];
if (empty($config) or empty($config['type']))
{
    throw new Exception("require event[$php->factory_key] config.");
}
return new Swoole\Event($config);