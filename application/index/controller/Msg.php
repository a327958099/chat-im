<?php

/**
 * 消息
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\controller;

use think\Request;
use GatewayClient\Gateway;

class Msg extends Base
{
    /**
     * 消息列表
     */
    public function list()
    {
        $chatId = Request::instance()->post('chatId', 'a');
        
        // 拉取消息队列
        $redis = redis();
        $data = $redis->lrange($chatId, 0, -1);

        foreach ($data as $key => &$val) 
        {
            $val = json_decode($val, true);

            // 区别消息来源
            $val['classname'] = $val['form'] == $this->uid ? 'me' : 'other';

            // 是否显示时间（10分钟内不显示时间）
            if($key == 0 || ($val['lasttime'] - $data[$key-1]['lasttime']) > 600)
            {   
                $val['isShow'] = 1;
                $bool = date('Ymd') === date('Ymd', $val['lasttime']);
                $val['isShowTime'] = $bool ? date('今天 H:i', $val['lasttime']) : date('m月d日 H:i', $val['lasttime']);
            }
            else
            {
                $val['isShow'] = 0;
            }
        }

        return json([
            'code' => 200,
            'msg'  => 'ok',
            'data' => $data
        ]);
    }

    /**
     * 拉取最后一条消息
     */
    public function get_last_msg()
    {
        $chatId = Request::instance()->post('chatId', '1');

        // 实例化Redis
        $redis = redis();

        // 取消息队列最后一条
        $msg = $redis->lrange($chatId, -1, -1);
        $msg = empty($msg) ? '' : (json_decode($msg[0], true))['content'];

        return json([
            'code' => 200,
            'msg'  => 'ok',
            'content' => $msg
        ]);
    }

    /**
     * 撤回消息
     */
    public function back()
    {
        $chatId = Request::instance()->post('chatId', '1');
        $msgId = Request::instance()->post('msgId', '1');


        $redis = redis();

        // 获取消息队列数据
        $data = $redis->lrange($chatId, 0, -1);

        foreach ($data as $key => &$val) 
        {
            $base = json_decode($val, true);

            if($base['chatId'] == $chatId && $base['msgId'] == $msgId && $base['form'] == $this->uid)
            {
                // 移除队列指定元素
                $redis->lrem($chatId, $val, 0);

                // 撤回消息通知
                Gateway::sendToUid($base['to'], json_encode([
                    'type' => 'back',
                    'chatId'=> $chatId,
                    'msgId' => $msgId
                ]));

                break;
            }
        }

        // 返回
        return json([
            'code' => 200,
            'msg'  => 'ok',
        ]);
    }

    /**
     * 消息加密
     */
    private static function encrypt($data, $key, $iv)
    {
        return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, true, $iv));
    }

    /**
     * 消息解密
     */
    private static function decrypt($encode, $key, $iv)
    {
        return openssl_decrypt(base64_decode($encode), 'AES-128-CBC', $key, true, $iv);
    }
}