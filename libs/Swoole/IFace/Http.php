<?php
namespace Swoole\IFace;

interface Http
{
    function header($k, $v);

    function status($code);

    function response($content);

    function redirect($url, $mode = 301);

    function finish($content = null);

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null,
        $httponly = null);
}