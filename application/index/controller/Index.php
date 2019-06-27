<?php

namespace app\index\controller;

use think\Db;
use think\Config;
use think\Request;
use think\Validate;
use think\Session;
use GatewayClient\Gateway;

class Index extends Base
{
    /**
     * 首页
     */
    public function index()
    {
        return view('index');
    }
}
