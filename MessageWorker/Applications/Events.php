<?php
// +----------------------------------------------------------------------
// | Websocket消息业务逻辑
// +----------------------------------------------------------------------
// | License: v1.0.1
// +----------------------------------------------------------------------
// | Author: devkeep
// +----------------------------------------------------------------------

//declare(ticks=1);
use \GatewayWorker\Lib\Gateway;

class Events
{
    /**
     * 客户端连接
     * @param int $client_id 客户端ID
     */
    public static function onConnect($client_id)
    {
        $data = [
        	'type' => 'bind',
        	'msg'  => $client_id
        ];

        // 返回客户端连接ID
        Gateway::sendToClient($client_id, json_encode($data));
    }

    /**
     * 客户端消息
     * @param int $client_id 客户端ID
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        if($message === 'ping')
        {
            $data = [
                'type' => 'ping',
                'msg'  => 'yes'
            ];

            // 返回心跳包
            Gateway::sendToClient($client_id, json_encode($data));
        }
    }

    /**
     * 客户端断开
     * @param int $client_id 客户端ID
     */
    public static function onClose($client_id)
    {
        // Gateway::sendToAll('logout')
    }
}