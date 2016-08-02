<?php
/**
 * PHPProject
 * BaseController.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/6/28
 * Time: 下午3:41
 * 心怀教育梦－烟台网格软件技术有限公司
 */
namespace App\Controller;

use Swoole;

trait XssClean
{
    /**xss过滤
     * @param $val
     * @return mixed
     */
    public function RemoveXSS($data)
    {
        // Fix &entity\n;
        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);

        // we are done...
        return $data;
    }
}

trait HtmlEscape
{
    /**xss过滤
     * @param $val
     * @return mixed
     */
    public function Escape($data)
    {
        return htmlspecialchars($data);
    }
}


class BaseController extends Swoole\Controller
{

    /**
     * xss
     */
    use XssClean;
    use HtmlEscape;

    /**
     * @param $key
     * @return null
     */
    function Get($key, $default = "")
    {
        return !(isset($this->request->get[$key]) && $this->request->get[$key] != "") ? $default : $this->request->get[$key];
    }

    /**
     * @param $key
     * @return null
     */
    function Post($key, $default = "")
    {
        return !(isset($this->request->post[$key]) && $this->request->post[$key] != "") ? $default : $this->request->post[$key];
    }

    /**
     * @param $key
     * @return null
     */
    function GetRequestHeader($key, $default = "")
    {
        return !(isset($this->request->head[$key]) && $this->request->head[$key] != "") ? $default : $this->request->head[$key];
    }

    /**
     * @param $key
     * @param $value
     */
    function SetResponseHeader($key, $value)
    {
        $this->response->setHeader($key, $value);
    }

    /**
     * @return mixed
     */
    function Method()
    {
        return $this->request->server['REQUEST_METHOD'];
    }

    /**
     * @return mixed
     */
    function  RemoteIP()
    {
        return $this->request->server['SWOOLE_CONNECTION_INFO']['remote_ip'];
    }

    /**
     * @return mixed
     */
    function  RemotePort()
    {
        return $this->request->server['SWOOLE_CONNECTION_INFO']['remote_port'];
    }

    /**在请求之前
     * @return bool
     */
    public function BeforeRequest()
    {


    }

    /**在请求之后
     * @return bool
     */
    public function AfterRequest()
    {


    }

    /**结束输出
     * @param null $content
     */
    public function Finish($content = null)
    {
        if (is_string($content) || $content == null) {
            $this->http->finish($content);
        } elseif (is_object($content) || is_array($content) || is_assoc($content)) {
            $this->http->finish(json_encode($content, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @param $url
     */
    public function  Redirect($url, $mode = 301)
    {
        $this->http->redirect($url, $mode);
    }

    /**
     * 过滤xss
     */
    public function   __xssDo()
    {
        switch (strtolower($this->Method())) {
            case 'get':
                foreach ($this->request->get as $k => &$v) {
                    $v = $this->RemoveXSS($v);
                }
                break;
            case 'post':
                foreach ($this->request->post as $k => &$v) {
                    $v = $this->RemoveXSS($v);
                }
                break;
        }
    }

    /**
     * 过滤xss
     */
    public function   __escapeDo()
    {
        switch (strtolower($this->Method())) {
            case 'get':
                foreach ($this->request->get as $k => &$v) {
                    $v = $this->Escape($v);
                }
                break;
            case 'post':
                foreach ($this->request->post as $k => &$v) {
                    $v = $this->Escape($v);
                }
                break;
        }
    }


}