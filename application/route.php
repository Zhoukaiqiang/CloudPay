<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
//return [
//    '__pattern__' => [
//        'name' => '\w+',
//    ],
//    '[hello]'     => [
//        ':id'   => ['home/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['home/hello', ['method' => 'post']],
//    ],
//
//];
/**
 * 路由定义。定义测试路由
 *
 *
 */

Route::rule('test', 'admin/user/test', "GET|POST");