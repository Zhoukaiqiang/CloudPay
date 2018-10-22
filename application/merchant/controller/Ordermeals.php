<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/20
 * Time: 16:59
 */

namespace app\merchant\controller;


use think\Controller;

class Ordermeals extends Controller
{
    public function returntime()
    {

        $shop_id=\request()->param('shop_id');
        $name=\request()->param('name');
        return_msg(200,'success',['shop'=>$shop_id,'name'=>$name]);
    }
}