<?php
namespace Swoole;
/**
 * 客户端工具
 * 获取客户端IP、操作系统、浏览器，以及HTTP操作等功能
 * @author Administrator
 * @package SwooleSystem
 * @subpackage tools
 */
class Client
{
	/**
	 * 跳转网址
	 * @param $url
	 * @return unknown_type
	 */
	public static function redirect($url,$mode=302)
	{
		Http::redirect($url, $mode);
        return;
	}
	/**
	 * 发送下载声明
	 * @return unknown_type
	 */
	static function download($mime,$filename)
	{
        header("Content-type: $mime");
        header("Content-Disposition: attachment; filename=$filename");
	}

    /**
     * 获取客户端IP
     * @return string
     */
    static function getIP()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) and strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown"))
        {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) and strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown"))
        {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        if (isset($_SERVER["REMOTE_ADDR"]))
        {
            return $_SERVER["REMOTE_ADDR"];
        }
        return "";
    }

	/**
	 * 获取客户端浏览器信息
	 * @return string
	 */
	static function getBrowser()
	{
		if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(myie[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Netscape[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Opera[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(NetCaptor[^;^^()]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(TencentTraveler)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Firefox[0-9/\.^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(MSN[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Lynx[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Konqueror[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(WebTV[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(msie[^;^)^(]*)|i" ) );
		else if( $Browser = self::matchbrowser( $_SERVER["HTTP_USER_AGENT"], "|(Maxthon[^;^)^(]*)|i" ) );
		else $Browser = '其它';
		return $Browser;
	}
	/**
	 * 获取客户端操作系统信息
	 * @return unknown_type
	 */
	static function getOS()
	{
		$os="";
		$Agent = $_SERVER["HTTP_USER_AGENT"];
		if (eregi('win',$Agent) and strpos($Agent, '95')) $os="Windows 95";
		elseif (eregi('win 9x',$Agent) and strpos($Agent, '4.90')) $os="Windows ME";
		elseif (eregi('win',$Agent) and ereg('98',$Agent)) $os="Windows 98";
		elseif (eregi('win',$Agent) and eregi('nt 5.0',$Agent)) $os="Windows 2000";
		elseif (eregi('win',$Agent) and eregi('nt 5.1',$Agent)) $os="Windows XP";
		elseif (eregi('win',$Agent) and eregi('nt 5.2',$Agent)) $os="Windows 2003";
		elseif (eregi('win',$Agent) and eregi('nt',$Agent)) $os="Windows NT";
		elseif (eregi('win',$Agent) and ereg('32',$Agent)) $os="Windows 32";
		elseif (eregi('linux',$Agent)) $os="Linux";
		elseif (eregi('unix',$Agent)) $os="Unix";
		elseif (eregi('sun',$Agent) and eregi('os',$Agent)) $os="SunOS";
		elseif (eregi('ibm',$Agent) and eregi('os',$Agent)) $os="IBM OS/2";
		elseif (eregi('Mac',$Agent) and eregi('PC',$Agent)) $os="Macintosh";
		elseif (eregi('PowerPC',$Agent)) $os="PowerPC";
		elseif (eregi('AIX',$Agent)) $os="AIX";
		elseif (eregi('HPUX',$Agent)) $os="HPUX";
		elseif(eregi('NetBSD',$Agent)) $os="NetBSD";
		elseif (eregi('BSD',$Agent)) $os="BSD";
		elseif (ereg('OSF1',$Agent)) $os="OSF1";
		elseif (ereg('IRIX',$Agent)) $os="IRIX";
		elseif (eregi('FreeBSD',$Agent)) $os="FreeBSD";
		if ($os=='') $os = "Unknown";
		return $os;
	}
	private static function matchbrowser( $Agent, $Patten )
	{
		if( preg_match( $Patten, $Agent, $Tmp ) )
		{
			return $Tmp[1];
		}
		else
		{
			return false;
		}
	}
	static function requestMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
}