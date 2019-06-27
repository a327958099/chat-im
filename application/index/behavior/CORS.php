<?php

/**
 * 统一行为过滤器/跨域
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\behavior;

use think\Request;

class CORS {

    /**
     * 初始化/处理跨域
     */
    public function appInit(&$params)
    {
        header("Access-Control-Allow-Credentials: true");
        header('Access-Control-Allow-Origin:*');     
        header('Access-Control-Allow-Methods:*');  
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, token, timestamp');

        if( Request::instance()->isOptions() )
        {
            exit();
        }
    }
}