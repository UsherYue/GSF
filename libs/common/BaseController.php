<?php
/**
 * GridSwooleFramework
 * BaseController.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/6/28
 * Time: 下午3:41
 * 心怀教育梦－烟台网格软件技术有限公司
 */
namespace App\Controller;
use Swoole;

class BaseController extends  Swoole\Controller{

    /**
     * @param $key
     * @return null
     */
    function Get($key,$default=""){
        return (!isset($this->request->get[$key]))?$default:$this->request->get[$key];
    }

    /**
     * @param $key
     * @return null
     */
    function Post($key,$default=""){
        return (!isset($this->request->post[$key]))?$default:$this->request->post[$key];
    }

    /**
     * @param $key
     * @return null
     */
    function GetRequestHeader($key,$default=""){
        return (!isset($this->request->head[$key]))?$default:$this->request->head[$key];
    }

    /**
     * @param $key
     * @param $value
     */
    function SetResponseHeader($key,$value){
        $this->response->setHeader($key,$value);
    }

    /**
     * @return mixed
     */
    function Method(){
        return $this->request->server['REQUEST_METHOD'];
    }

    /**
     * @return mixed
     */
    function  RemoteIP(){
        return $this->request->server['SWOOLE_CONNECTION_INFO']['remote_ip'];
    }

    /**
     * @return mixed
     */
    function  RemotePort(){
        return $this->request->server['SWOOLE_CONNECTION_INFO']['remote_port'];
    }
}