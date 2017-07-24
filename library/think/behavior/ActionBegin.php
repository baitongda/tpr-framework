<?php

// +----------------------------------------------------------------------
// | TPR [ Design For Api Develop ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2017 http://hanxv.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axios <axioscros@aliyun.com>
// +----------------------------------------------------------------------

namespace think\behavior;

use think\cache\CacheRequest;
use think\exception\ClassNotFoundException;
use think\Loader;
use think\Request;
use traits\controller\Jump;

Loader::import('controller/Jump', TRAIT_PATH, EXT);

class ActionBegin
{
    use Jump;
    public $param;
    public $request;
    public $module;
    public $controller;
    public $action;
    public $mca;

    public function __construct()
    {
        $this->request = Request::instance();
        $this->param = $this->request->param();
        $this->module = strtolower($this->request->module());
        $this->controller = strtolower($this->request->controller());
        $this->action = $this->request->action();
        $this->mca = $this->module.'/'.$this->controller.'/'.$this->action;
        $this->request->mca = $this->mca;
    }

    public function run()
    {
        $this->filter();
        $this->middleware();
        $this->cache();
    }

    private function filter()
    {
        $validate_config = c('validate.'.$this->mca);

        if (!empty($validate_config)) {
            try {
                $Validate = validate($validate_config[0]);
            } catch (ClassNotFoundException $e) {
                throw new ClassNotFoundException('class not exists:'.$validate_config[0], __CLASS__);
            }

            if (isset($validate_config[1])) {
                $check = $Validate->hasScene($validate_config[1]) ? $Validate->scene($validate_config[1])->check($this->param) : true;
            } else {
                $check = $Validate->check($this->param);
            }

            if (!$check) {
                $this->wrong(400, lang($Validate->getError()));
            }
        } else {
            $class = Loader::parseClass($this->module, 'validate', $this->controller, false);
            if (class_exists($class)) {
                $Validate = Loader::validate($this->controller, 'validate', false, $this->module);
                $check = $Validate->hasScene($this->action) ? $Validate->scene($this->action)->check($this->param) : true;
                if (!$check) {
                    $this->wrong(400, lang($Validate->getError()));
                }
            }
        }
    }

    private function cache()
    {
        $cache = CacheRequest::get($this->request);
        if (!empty($cache)) {
            $this->response($cache);
        }
    }

    private function middleware()
    {
        $middleware_config = c('middleware.before', []);
        if (!empty($middleware_config)) {
            if (isset($middleware_config[$this->mca])) {
                $middleware_config = $middleware_config[$this->mca];
                try {
                    $Middleware = validate($middleware_config[0]);
                } catch (ClassNotFoundException $e) {
                    throw new ClassNotFoundException('class not exists:'.$middleware_config[0], __CLASS__);
                }

                call_user_func_array([$Middleware, $middleware_config[1]], [$this->request]);
            } else {
                $class = Loader::parseClass(strtolower($this->module), 'middleware', strtolower($this->controller), false);
                if (class_exists($class)) {
                    $Middleware = Loader::validate($this->controller, 'middleware', false, $this->module);
                    call_user_func_array([$Middleware, 'before'], [$this->request]);
                }
            }
        }
    }
}
