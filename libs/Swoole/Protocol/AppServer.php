<?php
namespace Swoole\Protocol;

use Swoole;

require_once LIBPATH . '/function/cli.php';

class AppServerException extends \Exception
{

}

class AppServer extends HttpServer
{
    protected $router_function;
    protected $apps_path;
    /**
     * @var array
     */
    private $callback = [];

    /**
     * @param $name
     * @param $arguments
     */
    public function  __call($name, $arguments)
    {
        if (strtolower($name) == 'on') {
            $this->callback[$arguments[0]] = $arguments[1];
        }
    }

    //启动的时候进入   swoole c扩展中处理
    function onStart($serv)
    {
        //var_dump($this->config);
        parent::onStart($serv);
        //mvc path
        if (empty($this->apps_path)) {

            if (!empty($this->config['apps']['apps_path'])) {
                $this->apps_path = $this->config['apps']['apps_path'];
            } else {
                throw new AppServerException("AppServer require apps_path");
            }
        }
        //创建swoole对象
        $php = Swoole::getInstance();
        //增加钩子函数
        $php->afterRequest(function () {
            $php = Swoole::getInstance();
            //模板初始化
            if (!empty($php->tpl)) {
                $php->tpl->clear_all_assign();
            }
            //还原session
            if (!empty($php->session)) {
                $php->session->open = false;
                $php->session->readonly = false;
            }
        });
        //attach event
        if (!empty($this->callback['start']) && is_callable($this->callback['start'])) {
            call_user_func($this->callback['start']);
        }
    }

    /**在请求的时候调用
     * @param Swoole\Request $request
     * @return Swoole\Response
     */
    function onRequest(Swoole\Request &$request)
    {
        //收到http请求
        return Swoole::getInstance()->handlerServer($request);
    }
}