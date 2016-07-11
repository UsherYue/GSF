<?php
if (empty(Swoole::$php->config['upload']))
{
    $config = Swoole::$php->config['upload'];
}
else
{
    throw new Exception("require upload config");
}
$upload = new Swoole\Upload($config);
return $upload;
