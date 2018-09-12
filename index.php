<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2018/9/3
 * Time: 17:28
 */

namespace think;

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
define('ROOT_PATH', __DIR__.'/');
define('GlOB_CACHE_TIME',3600); //全局缓存时间
// 加载框架基础引导文件
require __DIR__ . '/thinkphp/base.php';
// 添加额外的代码
// ...

// 执行应用并响应
Container::get('app', [APP_PATH])->run()->send();