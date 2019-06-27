<?php

/**
 * 消息推送
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\controller;

use think\Validate;
use think\Db;
use think\Request;
use GatewayClient\Gateway;

class Send extends Base
{
	/**
	 * 消息发送/存储
	 */
	public function push()
	{
        $data = Request::instance()->post();

        // 基础验证
        $validate = new Validate([
            ['msgId', 'require', '数据格式有误'],
            ['chatId', 'require', '无效的会话ID'],
            ['to', 'require', '无效的接收者'],
            ['content', 'require', '消息内容不能为空哦'],
        ]);

        if(!$validate->check($data))
        {
            return json([
                'code' => 204,
                'msg'  => $validate->getError()
            ]);
        }

        // 发送人、消息时间、消息ID
        $data['msgId'] = $this->uid.$data['msgId'];
        $data['form'] = $this->uid;
        $data['lasttime'] = time();

        // 数据过滤
        $data['content'] = nl2br(htmlspecialchars($data['content'], ENT_QUOTES));

        // 处理表情
        preg_match_all('/\[\/:[\s\S]*?\]/i', $data['content'], $res);

        if(count($res) > 0)
        {
            foreach ($res[0] as $v) 
            {
                $value = str_replace('[/:', '', $v);
                $value = (int)str_replace(']', '', $value);
                
                if($value <= 98 && $value >= 0)
                {
                    $face = '<img src="/static/face/'.$value.'.gif">';
                    $data['content'] = str_replace('[/:'.$value.']', $face, $data['content']);
                }
            }
        }

        // 写入消息队列
        $redis = redis();
        $count = $redis->rpush($data['chatId'], json_encode($data));

        // 队列长度超过50，则消息依次出列
        if($count >= 50)
        {
            $redis->lpop( $data['chatId'] );
        }

        // 发送给自己 (由发送者拉取)
        // Gateway::sendToUid($data['form'], json_encode([
        //     'type' => 'text',
        //     'msg'  => $data['chatId']
        // ]));

        // 发送给接收方
        Gateway::sendToUid($data['to'], json_encode([
            'type' => 'text',
            'msg'  => $data['chatId']
        ]));

        // 离线暂不处理（由于浏览器刷新等机制，无法完全保证客户端是否在线）
        // if( Gateway::isUidOnline($data['to']) )
        // {

        // }

        return json([
            'code' => 200,
            'msg'  => 'ok'
        ]);
    }

    private function getBetweenStr($input, $start, $end) 
    {
        $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        return $substr;
    }
}