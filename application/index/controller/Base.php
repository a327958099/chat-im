<?php

/**
 * 统一过滤器
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\controller;

use think\Db;
use think\Config;
use think\Request;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Response;

class Base extends Controller
{
    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->uid = Session::get('uid');

        // 验证会话
        if(empty($this->uid))
        {
            if(Request::instance()->isAjax())
            {   
                // 异步请求
                Response::create([
                    'code'  => 401,
                    'msg'   => '登录超时，请重新登录'
                ], 
                'json')->send();
            }
            else
            {
                // 同步请求
                header('location: /index/user/index');
            }

            exit();
        }
    }
}