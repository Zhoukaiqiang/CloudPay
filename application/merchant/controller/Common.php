<?php

namespace app\merchant\controller;

use think\Controller;
use think\Session;

class Common extends Controller
{
    protected $merchant_id;
    protected $user_id;
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        /** 检验并赋值给变量 */
        if (Session::has("username_","app")) {
            if (Session::get("username_","app")["role"] == -1) {
                $this->merchant_id = Session::get("username_","app")["id"];
            }else {
                $this->user_id = Session::get("username_","app")["id"];
            }
        }else {
            return_msg(400, "请登录");
        }
    }
}