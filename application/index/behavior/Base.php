<?php

/**
 * 统一行为过滤器/会话过滤
 * Auther: devkeep
 * License: v1.0.1
 */

namespace app\index\behavior;

use think\Db;
use think\Response;

class Base {

    /**
     * 会话过滤处理
     */
    public function run(&$params)
    {
        // 会话过滤已迁移由继承关系解决，方便数据处理; 后续若有需要，可迁回用文件缓存、memcache、redis等中控服务，来缓存全局数据及提高性能
    }
}