<?php

/**
 * 绑定webSocket客户端连接
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\controller;

use think\Request;
use GatewayClient\Gateway;

class Bind extends Base
{
    /**
     * 创建绑定
     */
    public function create()
    {
        // 客户端连接标识
        $client_id = Request::instance()->post('client_id');

        if (strlen($client_id) !== 20) 
        {
            return json([
                'code' => 204,
                'msg'  => 'client_id is invalid'
            ]);
        } 
        else 
        {
            // 用户ID绑定到当前socket连接
            Gateway::bindUid($client_id, $this->uid);

            return json([
                'code' => 200,
                'msg'  => 'ok'
            ]);
        }
    }
}