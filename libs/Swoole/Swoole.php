<?php
//加载核心的文件
require_once __DIR__ . '/Loader.php';
require_once __DIR__ . '/ModelLoader.php';
require_once __DIR__ . '/PluginLoader.php';

use Swoole\Exception\NotFound;

/**
 * Swoole系统核心类，外部使用全局变量$php引用
 * Swoole框架系统的核心类，提供一个swoole对象引用树和基础的调用功能
 *
 * @package    SwooleSystem
 * @author     Tianfeng.Han
 * @subpackage base
 * @property \Swoole\Database    $db
 * @property \Swoole\IFace\Cache $cache
 * @property \Swoole\Upload      $upload
 * @property \Swoole\Event       $event
 * @property \Swoole\Session     $session
 * @property \Swoole\Template    $tpl
 * @property \redis              $redis
 * @property \MongoClient        $mongo
 * @property \Swoole\Config      $config
 * @property \Swoole\Http\PWS    $http
 * @property \Swoole\Log         $log
 * @property \Swoole\Auth        $user
 * @property \Swoole\URL         $url
 * @property \Swoole\Limit       $limit
 * @method \Swoole\Database      db
 * @method \MongoClient          mongo
 * @method \redis                redis
 * @method \Swoole\IFace\Cache   cache
 * @method \Swoole\URL           url
 * @method \Swoole\Platform\Linux os
 */
class Swoole
{
    //所有全局对象都改为动态延迟加载
    //如果希望启动加载,请使用Swoole::load()函数

    /**
     * @var Swoole\Protocol\HttpServer
     */
    public $server;
    public $protocol;

    /**
     * @var Swoole\Request
     */
    public $request;

    public $config;

    /**
     * @var Swoole\Response
     */
    public $response;

    static public $app_path;
    static public $controller_path = '';

    /**
     * @var Swoole\Http\ExtServer
     */
    protected $ext_http_server;

    /**
     * 可使用的组件
     */
    static $modules = array(
        'redis' => true,  //redis
        'mongo' => true,  //mongodb
        'db' => true,  //数据库
        'codb' => true, //并发MySQLi客户端
        'tpl' => true, //模板系统
        'cache' => true, //缓存
        'event' => true, //异步事件
        'log' => true, //日志
        'upload' => true, //上传组件
        'user' => true,   //用户验证组件
        'session' => true, //session
        'http' => true, //http
        'url' => true, //urllib
        'limit' => true, //频率限制组件
    );

    /**
     * 允许多实例的模块
     * @var array
     */
    static $multi_instance = array(
        'cache' => true,
        'db' => true,
        'mongo' => true,
        'redis' => true,
        'url' => true,
        'log' => true,
        'codb' => true,
    );

    static $default_controller = array('controller' => 'page', 'view' => 'index');

    static $charset = 'utf-8';
    static $debug = false;

    static $setting = array();
    public $error_call = array();
    /**
     * Swoole类的实例
     * @var Swoole
     */
    static public $php;
    public $pagecache;

    /**
     * 对象池
     * @var array
     */
    protected $objects = array();

    /**
     * 传给factory
     */
    public $factory_key = 'master';

    /**
     * 发生错误时的回调函数
     */
    public $error_callback;

    public $load;

    /**
     * @var \Swoole\ModelLoader
     */
    public $model;
    public $env;

    protected $hooks = array();
    protected $router_function;

    const HOOK_INIT  = 1; //初始化
    const HOOK_ROUTE = 2; //URL路由
    const HOOK_CLEAN = 3; //清理

    private function __construct()
    {
        if (!defined('DEBUG')) define('DEBUG', 'on');

        $this->env['sapi_name'] = php_sapi_name();
        //如果 接口类型不是cli
        if ($this->env['sapi_name'] != 'cli')
        {
            Swoole\Error::$echo_html = true;
        }
        //定义APPSPATH
        if (defined('APPSPATH'))
        {
            self::$app_path = APPSPATH;
        }
        elseif (defined('WEBPATH'))
        {
            //app path
            self::$app_path = WEBPATH . '/apps';
            define('APPSPATH', self::$app_path);
        }
        else
        {
            Swoole\Error::info("core error", __CLASS__ . ": Swoole::\$app_path and WEBPATH empty.");
        }

        //将此目录作为App命名空间的根目录
        Swoole\Loader::addNameSpace('App', self::$app_path . '/classes');

        $this->load = new Swoole\Loader($this);
        $this->model = new Swoole\ModelLoader($this);
        $this->config = new Swoole\Config;
        $this->config->setPath(self::$app_path . '/configs');

        //路由钩子，URLRewrite
        $this->addHook(Swoole::HOOK_ROUTE, 'swoole_urlrouter_rewrite');
        //mvc
        $this->addHook(Swoole::HOOK_ROUTE, 'swoole_urlrouter_mvc');
        //设置路由函数
        $this->router(array($this, 'urlRoute'));
    }

    /**
     * 初始化
     * @return Swoole
     */
    static function getInstance()
    {
        if (!self::$php)
        {
            self::$php = new Swoole;
        }
        return self::$php;
    }

    /**
     * 获取资源消耗
     * @return array
     */
    function runtime()
    {
        // 显示运行时间
        $return['time'] = number_format((microtime(true)-$this->env['runtime']['start']),4).'s';

        $startMem =  array_sum(explode(' ',$this->env['runtime']['mem']));
        $endMem   =  array_sum(explode(' ',memory_get_usage()));
        $return['memory'] = number_format(($endMem - $startMem)/1024).'kb';
        return $return;
    }
    /**
     * 压缩内容
     * @return null
     */
    function gzip()
    {
        //不要在文件中加入UTF-8 BOM头
        //ob_end_clean();
        ob_start("ob_gzhandler");
        #是否开启压缩
        if (function_exists('ob_gzhandler'))
        {
            ob_start('ob_gzhandler');
        }
        else
        {
            ob_start();
        }
    }

    /**
     * 初始化环境
     * @return null
     */
    function __init()
    {
        #DEBUG
        if (defined('DEBUG') and DEBUG == 'on')
        {
            #捕获错误信息
//            set_error_handler('swoole_error_handler');
            #记录运行时间和内存占用情况
            $this->env['runtime']['start'] = microtime(true);
            $this->env['runtime']['mem'] = memory_get_usage();
        }
        $this->callHook(self::HOOK_INIT);
    }

    /**
     * 执行Hook函数列表
     * @param $type
     */
    protected function callHook($type)
    {
        if (isset($this->hooks[$type]))
        {
            foreach ($this->hooks[$type] as $f)
            {
                if (!is_callable($f))
                {
                    trigger_error("SwooleFramework: hook function[$f] is not callable.");
                    continue;
                }
                $f();
            }
        }
    }

    /**
     * 清理
     */
    function __clean()
    {
        $this->env['runtime'] = array();
        $this->callHook(self::HOOK_CLEAN);
    }

    /**
     * 增加钩子函数
     * @param $type
     * @param $func
     */
    function addHook($type, $func)
    {
        $this->hooks[$type][] = $func;
    }

    /**
     * 在请求之前执行一个函数
     * @param callable $callback
     */
    function beforeRequest(callable $callback)
    {
        $this->addHook(self::HOOK_INIT, $callback);
    }

    /**
     * 在请求之后执行一个函数
     * @param callable $callback
     */
    function afterRequest(callable $callback)
    {
        $this->addHook(self::HOOK_CLEAN, $callback);
    }

    function __get($lib_name)
    {
        //如果不存在此对象，从工厂中创建一个
        if (empty($this->$lib_name))
        {
            //载入组件
            $this->$lib_name = $this->loadModule($lib_name);
        }
        return $this->$lib_name;
    }

    /**
     * 加载内置的Swoole模块
     * @param $module
     * @param $key
     * @return mixed
     */
    protected function loadModule($module, $key = 'master')
    {
        $object_id = $module . '_' . $key;
        if (empty($this->objects[$object_id]))
        {
            $this->factory_key = $key;
            $user_factory_file = self::$app_path . '/factory/' . $module . '.php';
            //尝试从用户工厂构建对象
            if (is_file($user_factory_file))
            {
                $object = require $user_factory_file;
            }
            //系统默认
            else
            {
                $system_factory_file = LIBPATH . '/factory/' . $module . '.php';
                //组件不存在，抛出异常
                if (!is_file($system_factory_file))
                {
                    throw new NotFound("module [$module] not found.");
                }
                $object = require $system_factory_file;
            }
            $this->objects[$object_id] = $object;
        }
        return $this->objects[$object_id];
    }

    function __call($func, $param)
    {
        //swoole built-in module
        if (isset(self::$multi_instance[$func]))
        {
            if (empty($param[0]) or !is_string($param[0]))
            {
                throw new Exception("module name cannot be null.");
            }
            return $this->loadModule($func, $param[0]);
        }
        //尝试加载用户定义的工厂类文件
        elseif(is_file(self::$app_path . '/factory/' . $func . '.php'))
        {
            $object_id = $func . '_' . $param[0];
            //已创建的对象
            if (isset($this->objects[$object_id]))
            {
                return $this->objects[$object_id];
            }
            else
            {
                $this->factory_key = $param[0];
                $object = require self::$app_path . '/factory/' . $func . '.php';
                $this->objects[$object_id] = $object;
                return $object;
            }
        }
        else
        {
            throw new Exception("call an undefine method[$func].");
        }
    }

    /**
     * 设置路由器
     * @param $function
     */
    function router($function)
    {
        $this->router_function = $function;
    }

    function urlRoute()
    {
        if (empty($this->hooks[self::HOOK_ROUTE]))
        {
            echo Swoole\Error::info('MVC Error!',"UrlRouter hook is empty");
            return false;
        }

        $uri = strstr($_SERVER['REQUEST_URI'], '?', true);
        if ($uri === false)
        {
            $uri = $_SERVER['REQUEST_URI'];
        }
        $uri = trim($uri, '/');

        $mvc = array();

        //URL Router
        foreach($this->hooks[self::HOOK_ROUTE] as $hook)
        {
            if(!is_callable($hook))
            {
                trigger_error("SwooleFramework: hook function[$hook] is not callable.");
                continue;
            }
            $mvc = $hook($uri);
            //命中
            if($mvc !== false)
            {
                break;
            }
        }
        return $mvc;
    }

    /**
     * 设置应用程序路径
     * @param $dir
     */
    static function setAppPath($dir)
    {
        if (is_dir($dir))
        {
            self::$app_path = $dir;
        }
        else
        {
            \Swoole\Error::info("fatal error", "app_path[$dir] is not exists.");
        }
    }

    /**
     * 设置应用程序路径
     * @param $dir
     */
    static function setControllerPath($dir)
    {
        if (is_dir($dir))
        {
            self::$controller_path = $dir;
        }
        else
        {
            \Swoole\Error::info("fatal error", "controller_path[$dir] is not exists.");
        }
    }

    function handlerServer(Swoole\Request $request)
    {
        //创建一个response
        $response = new Swoole\Response();
        //初始化全局var
        $request->setGlobal();


        //处理静态请求
        if (!empty($this->server->config['apps']['do_static']) and $this->server->doStaticRequest($request, $response))
        {
            return $response;
        }

        $php = Swoole::getInstance();

        //将对象赋值到控制器
        $php->request = $request;
        $php->response = $response;
        // var_dump(  $php->request);

        try
        {
            try
            {
                ob_start();
                /*---------------------处理MVC----------------------*/
                $response->body = $php->runMVC();
                $response->body .= ob_get_contents();
                ob_end_clean();
            }
            catch(Swoole\ResponseException $e)
            {
                if ($request->finish != 1)
                {
                    $this->server->httpError(500, $response, $e->getMessage());
                }
            }
        }
        catch (\Exception $e)
        {
            $this->server->httpError(500, $response, $e->getMessage()."<hr />".nl2br($e->getTraceAsString()));
        }

        //重定向
        if (isset($response->head['Location']) and ($response->http_status < 300 or $response->http_status > 399))
        {
            $response->setHttpStatus(301);
        }
        return $response;
    }

    function runHttpServer($host = '0.0.0.0', $port = 9501, $config = array())
    {
        define('SWOOLE_SERVER', true);
        $this->ext_http_server = $this->http = new Swoole\Http\ExtServer();
        $server = new swoole_http_server($host, $port);
        $server->set($config);
        if (!empty($config['document_root']))
        {
            $this->ext_http_server->document_root = trim($config['document_root']);
        }
        $server->on('Request', array($this->http, 'onRequest'));
        $server->start();
    }

    /**
     * 运行MVC处理模型
     */
    function runMVC()
    {

        $mvc = call_user_func($this->router_function);
//        echo '<pre>';
//        var_dump($mvc);
        //var_dump($this->router_function[0]->config->config['rewrite']);
//        echo '</pre>';
        if ($mvc === false)
        {
            $this->http->status(404);
            return Swoole\Error::info('MVC Error', "url route fail!");
        }
        //check controller name
        if (!preg_match('/^[a-z0-9_]+$/i', $mvc['controller']))
        {
            return Swoole\Error::info('MVC Error!',"controller[{$mvc['controller']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
        //check view name
        if (!preg_match('/^[a-z0-9_]+$/i', $mvc['view']))
        {
            return Swoole\Error::info('MVC Error!',"view[{$mvc['view']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
        //check app name
        if (isset($mvc['app']) and !preg_match('/^[a-z0-9_]+$/i',$mvc['app']))
        {
            return Swoole\Error::info('MVC Error!',"app[{$mvc['app']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
        $this->env['mvc'] = $mvc;

        //使用命名空间，文件名必须大写
        $controller_class = '\\App\\Controller\\'.ucwords($mvc['controller']);
        if (self::$controller_path)
        {
            $controller_path = self::$controller_path . '/' . ucwords($mvc['controller']) . '.php';
        }
        else
        {
            $controller_path = self::$app_path . '/controllers/' . ucwords($mvc['controller']) . '.php';
        }

        if (class_exists($controller_class, false))
        {
            goto do_action;
        }
        else
        {
            if (is_file($controller_path))
            {
                require_once $controller_path;
                goto do_action;
            }
        }

        //file not found
        $this->http->status(404);
        return Swoole\Error::info('MVC Error', "Controller <b>{$mvc['controller']}</b>[{$controller_path}] not exist!");

        do_action:

        $this->request = new \Swoole\Request();
        $this->request->initWithLamp();

        //服务器模式下，尝试重载入代码
        if (defined('SWOOLE_SERVER'))
        {
            $this->reloadController($mvc, $controller_path);
        }
        $controller = new $controller_class($this);
        if (!method_exists($controller, $mvc['view']))
        {
            $this->http->status(404);
            return Swoole\Error::info('MVC Error!'.$mvc['view'],"View <b>{$mvc['controller']}->{$mvc['view']}</b> Not Found!");
        }

        $param = empty($mvc['param']) ? null : $mvc['param'];
        $method = $mvc['view'];
        // var_dump($param);
        //doAction
        $return = $controller->$method($param);
        //保存Session
        if (defined('SWOOLE_SERVER') and $this->session->open and $this->session->readonly === false)
        {
            $this->session->save();
        }
        //响应请求
        if (!empty($controller->is_ajax))
        {
            $this->http->header('Cache-Control', 'no-cache, must-revalidate');
            $this->http->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $this->http->header('Content-Type', 'application/json');
            $return = json_encode($return);
        }
        if (defined('SWOOLE_SERVER'))
        {
            return $return;
        }
        else
        {
            echo $return;
        }
    }

    function reloadController($mvc, $controller_file)
    {
        if (extension_loaded('runkit') and $this->server->config['apps']['auto_reload'])
        {
            clearstatcache();
            $fstat = stat($controller_file);
            //修改时间大于加载时的时间
            if(isset($this->env['controllers'][$mvc['controller']]) && $fstat['mtime'] > $this->env['controllers'][$mvc['controller']]['time'])
            {
                runkit_import($controller_file, RUNKIT_IMPORT_CLASS_METHODS|RUNKIT_IMPORT_OVERRIDE);
                $this->env['controllers'][$mvc['controller']]['time'] = time();
            } else {
                $this->env['controllers'][$mvc['controller']]['time'] = time();
            }
        }
    }
}


/**修改rewite 路由 支持pathinfo
 * @param $uri
 * @return bool
 */
function swoole_urlrouter_rewrite(&$uri)
{
    $rewrite = Swoole::$php->config['rewrite'];

    if (empty($rewrite) or !is_array($rewrite))
    {
        return false;
    }
    $match = array();
    $uri_for_regx = '/'.$uri;
    /////////////////
    foreach($rewrite as $rule)
    {

        if (preg_match('#'.$rule['regx'].'#i', $uri_for_regx, $match))
        {
            //如果匹配边界没有/ 那么不匹配
            $subRegx=preg_replace('#'.$rule['regx'].'#i',"",$uri_for_regx);
            if($subRegx{0}!='/'&&$subRegx!=""){
                continue ;
            }
           // var_dump( $subRegx);
            if (isset($rule['get']))
            {
                $p = explode(',', $rule['get']);
                foreach ($p as $k => $v)
                {
                    if (isset($match[$k + 1]))
                    {
                        $_GET[$v] = $match[$k + 1];
                    }
                }
            }else{
                //默认参数处理PATH INFO  当不设置get的时候进入此处
                $pathInfo=preg_replace('#'.$rule['regx'].'#i',"",$uri_for_regx);
                $uriRewriteMVC=preg_replace('#'.$pathInfo.'#i',"",$uri_for_regx);
              //  echo $pathInfo.'<br/>';
             //   echo '#'.$rule['regx'].'#i'.'<br/>';
                //pathinfo 存在
                if($pathInfo!=""){
                    //$uriRewriteMVC结尾不允许却少/
                    $endDelimiter=$uri_for_regx{strlen($uriRewriteMVC)-1};
                    if($endDelimiter=="" and $pathInfo{0}==''){

                        return false ;
                    }
                    //修改正则表达式路由问题
                    if($pathInfo{0}=='/'){
                        $pathInfo=substr($pathInfo,1,strlen($pathInfo)-1);
                    }
                    //填充参数到get 奇数直接填充 空白
                    $prmBuildArr=explode('/',$pathInfo);
                    $prmBuildLen=count($prmBuildArr);
//                    var_dump($prmBuildArr);
                    for($i=0;$i<$prmBuildLen;$i+=2){
                        if($i+1<$prmBuildLen){
                            $_GET[$prmBuildArr[$i]]=$prmBuildArr[$i+1];
                        }else{
                            $_GET[$prmBuildArr[$i]]="";
                        }
                    }

                }
            }
            return $rule['mvc'];
        }
    }
    return false;
}

/**修改普通路由方式支持
 * @param $uri
 * @return array
 */
function swoole_urlrouter_mvc(&$uri)
{

    $array = Swoole::$default_controller;
    if (!empty($_GET["c"]))
    {
        $array['controller'] = $_GET["c"];
    }
    if (!empty($_GET["v"]))
    {
        $array['view'] = $_GET["v"];
    }
    $request = explode('/', $uri, 3);
    if (count($request) < 2)
    {
        return $array;
    }
    $array['controller'] = $request[0];
    $array['view'] = $request[1];
    if (isset($request[2]))
    {
        $request[2] = trim($request[2], '/');
        $_id = str_replace('.html', '', $request[2]);
        if (is_numeric($_id))
        {
            $_GET['id'] = $_id;
        }
        else
        {
            $requestPrm=str_replace('.html', '', $request[2]);
            //////////填充参数到get 奇数直接填充 空白//////////////
            $prmBuildArr=explode('/', $requestPrm);
            $prmBuildLen=count($prmBuildArr);
            for($i=0;$i<$prmBuildLen;$i+=2){
                if($i+1<$prmBuildLen){
                    $_GET[$prmBuildArr[$i]]=$prmBuildArr[$i+1];
                }else{
                    $_GET[$prmBuildArr[$i]]="";
                }
            }
            ////////////////////////////////////////
            Swoole\Tool::$url_key_join = '-';
            Swoole\Tool::$url_param_join = '-';
            Swoole\Tool::$url_add_end = '.html';
            Swoole\Tool::$url_prefix = WEBROOT . "/{$request[0]}/$request[1]/";
            Swoole\Tool::url_parse_into($request[2], $_GET);
        }
        $_REQUEST = array_merge($_REQUEST, $_GET);
    }
    return $array;
}
