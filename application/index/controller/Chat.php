<?php

namespace app\index\controller;

use think\Db;
use think\Config;
use think\Request;
use think\Validate;
use think\Session;
use GatewayClient\Gateway;

class Chat extends Base
{
    /**
     * 会话列表
     */
    public function list()
    {
        $users = Db::name('user')
            ->field('id,nickname,headimgurl')
            ->order('id asc')
            ->select();

        // 实例化Redis
        $redis = redis();

        foreach ($users as $key => &$val) 
        {
            // 生成会话ID
            $val['chatId']  = self::generateChatId($this->uid, $val['id']);

            if($val['id'] == $this->uid)
            {
                $val['nickname'] = $val['nickname'] . '-（自己）';
            }

            // 取会话最后一条消息
            $lastMsg = $redis->lrange($val['chatId'], -1, -1);
            $val['lastMsg'] = empty($lastMsg) ? '' : (json_decode($lastMsg[0], true))['content'];
        }

        return json([
            'code' => 200,
            'msg'  => 'ok',
            'data' => $users
        ]);
    }

    /**
     * 生成会话ID(16位)
     */
    private static function generateChatId($form, $to)
    {
        $chat = [$form, $to];
        sort($chat);
        $chatId = implode('', $chat);
        $chatId = substr(md5($chatId), 8, 16);
        return $chatId;
    }
}