<?php

/**
 * 用法：
 * load_trait('controller/Jump');
 * class index
 * {
 *     use \traits\controller\Jump;
 *     public function index(){
 *         $this->error();
 *         $this->redirect();
 *     }
 * }
 */

namespace tpr\traits\controller;

use tpr\framework\Config;
use tpr\framework\exception\HttpResponseException;
use tpr\framework\Request;
use tpr\framework\Response;
use tpr\framework\response\Redirect;
use tpr\framework\Tool;
use tpr\framework\Url;
use tpr\framework\View as ViewTemplate;

trait Jump
{
    protected $return_type;

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param mixed $msg 提示信息
     * @param string $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param integer $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function success($msg = 'success', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $code = 1;
        if (is_numeric($msg)) {
            $code = $msg;
            $msg = '';
        }
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : Url::build($url);
        }
        $result = [
            'code' => $code,
            'msg'  => $this->msg($msg),
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();
        if ('html' == strtolower($type)) {
            $result = ViewTemplate::instance(Config::get('template'), Config::get('view_replace_str'))
                ->fetch(Config::get('dispatch_success_tmpl'), $result);
        }
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param mixed $msg 提示信息
     * @param string $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param integer $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return void
     * @throws \tpr\framework\Exception
     */
    protected function error($msg = 'error', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $code = 0;
        if (is_numeric($msg)) {
            $code = $msg;
            $msg = '';
        }
        if (is_null($url)) {
            $url = Request::instance()->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : Url::build($url);
        }
        $result = [
            'code' => $code,
            'msg'  => $this->msg($msg),
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();
        if ('html' == strtolower($type)) {
            $result = ViewTemplate::instance(Config::get('template'), Config::get('view_replace_str'))
                ->fetch(Config::get('dispatch_error_tmpl'), $result);
        }
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param integer $code 返回的code
     * @param mixed $msg 提示信息
     * @param string $type 返回数据格式
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $this->msg($msg),
            'time' => $_SERVER['REQUEST_TIME'],
            'data' => $data,
        ];
        $type = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * URL重定向
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array|integer $params 其它URL参数
     * @param integer $code http code
     * @param array $with 隐式传参
     * @return void
     */
    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        $response = new Redirect($url);
        if (is_integer($params)) {
            $code = $params;
            $params = [];
        }
        $response->code($code)->params($params)->with($with);
        throw new HttpResponseException($response);
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     * @throws \tpr\framework\Exception
     */
    protected function getResponseType()
    {
        $isAjax = Request::instance()->isAjax();
        return $isAjax ? c('default_ajax_return', 'json') : c('default_return_type');
    }

    /**
     * @param int $code
     * @param string $message
     * @param array $header
     */
    protected function wrong($code = 500, $message = '', $header = [])
    {
        $this->response([], $code, $message, $header);
    }

    protected function response($data = [], $code = 200, $message = 'success', array $header = [])
    {
        if ($code != 200 && empty($message)) {
            $message = c('code.' . strval($code), '');
        }
        $result = [
            'code' => $code,
            'msg'  => $this->msg($message),
            'time' => $_SERVER['REQUEST_TIME'],
            'data' => $data,
        ];
        $result = Tool::checkData2String($result);
        $this->ajaxReturn($result, $header);
    }

    protected function ajaxReturn($result = [], array $header = [])
    {
        $type = !empty($this->return_type) ? $this->return_type : c('default_ajax_return', 'json');
        $type = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }

    private function msg($message = '')
    {
        if(!empty($message)){
            $message = lang($message);
        }

        if(!is_string($message)){
            $message = '';
        }

        return $message;
    }
}
