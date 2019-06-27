<?php

/**
 * 用户接口
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\controller;

use think\Db;
use think\Config;
use think\Request;
use think\Validate;
use think\Session;
use think\Cookie;
use think\Loader;

Loader::import('phpqrcode.phpqrcode', EXTEND_PATH, '.php');

class User
{

    /**
     * 登录页
     */
    public function index()
    {
        if(Session::get('uid'))
        {
            header('Location: /index/index/index/');
            exit;
        }
        else
        {
            // 生成会话token
            Session::set('token', md5(uniqid(mt_rand(), true)));
            Session::set('crsfToken', md5(uniqid()));

            return view('index', [
                'crsfToken' => Session::get('crsfToken')
            ]);
        }
    }

    /**
     * 生成二维码
     */
    public function qrcode()
    {
        $token = Session::get('token');

        $url = 'http://im.skeep.cc/index/user/check/?token=' . $token;

        \QRcode::png($url, false, 'L', 8, 2);

        exit();
    }

    /**
     * PC登录接口
     */
    public function login()
    {
        $crsfToken = Request::instance()->post('crsfToken');

        if(Session::get('crsfToken') !== $crsfToken)
        {
            return json([
                'code' => 204,
                'msg'  => '安全验证失败',
            ]);
        }

        $userinfo = Db::name('user')
                ->where([
                    'token' => Session::get('token')
                ])
                ->find();

        if(empty($userinfo))
        {
            return json([
                'code' => 204,
                'msg'  => 'no scan',
            ]);
        }

        // 注册会话
        Session::set('uid', $userinfo['id']);

        return json([
            'code' => 200,
            'msg'  => 'ok'
        ]);
    }


    /**
     * 微信扫码登录
     */
    public function check()
    {
        // token
        $token = Request::instance()->get('token');

        if(!isset($token))
        {
            return json([
                'code' => 204,
                'msg'  => 'link fail'
            ]);
        }

        // 登录凭证code
        $code = Request::instance()->get('code');

        if(isset($code))
        {
            $appid = Config::get('config.appid');
            $secret = Config::get('config.secret');

            // code换取网页授权access_token
            $url  = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
            $data = curlGet($url);
            $data = json_decode($data, true);

            if(!isset($data['access_token']))
            {
                return json([
                    'code' => 204,
                    'msg'  => $data['errmsg']
                ]);
            }

            // 获取用户基本信息
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}&lang=zh_CN";
            $data = curlGet($url);
            $data = json_decode($data, true);

            if(!isset($data['openid']))
            {
                return json([
                    'code' => 204,
                    'msg'  => $data['errmsg']
                ]);
            }

            // 数据存储/更新
            $num = Db::name('user')
                ->where([
                    'openid' => $data['openid']
                ])
                ->count();

            if($num > 0)
            {
                // 更新token
                $bool = Db::name('user')
                    ->where([
                        'openid' => $data['openid']
                    ])
                    ->update([
                        'token' => $token
                    ]);
            }
            else
            {
                
                // 存储
                $bool = Db::name('user')->insert([
                    'openid'    => $data['openid'],
                    'nickname'  => @removeEmoji($data['nickname']),
                    'headimgurl'=> @$data['headimgurl'],
                    'createtime'=> time(),
                    'token'     => $token,
                ]);
            }

            if($bool)
            {
                return '<div style="font-size:50px;width:100%;text-align:center;padding-top:80px;">扫码登录成功</div>';
            }
            else
            {
                return '<div style="font-size:50px;width:100%;text-align:center;padding-top:80px;">二维码已过期</div>';
            }
        }
        else
        {
            // 微信授权登录
            $appid = Config::get('config.appid');
            $host = Config::get('config.host');
            $url = urlencode($host . '/index/user/check/?token=' . $token);
            header('location: https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$url.'&response_type=code&scope=snsapi_userinfo&state=STATE&connect_redirect=1#wechat_redirect');
            exit();
        }
    }

    /**
     * 清空redis
     */
    public function clear()
    {
        $bool = redis()->flushall();
        var_dump($bool);
    }
}
