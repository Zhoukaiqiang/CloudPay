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

Route::rule('test', 'merchant/capital/test', "GET|POST");
/** 商户后台资金管理 */

Route::rule('pc_cash', 'merchant/capital/pc_cash', "GET");
Route::rule('pc_cash_search', 'merchant/capital/pc_cash_search', "GET|POST");
Route::rule('pc_profile', 'merchant/capital/pc_profile', "GET");
Route::rule('pc_bind', 'merchant/capital/pc_bind', "GET|POST");
Route::rule('pc_bill', 'merchant/capital/pc_bill', "GET");

