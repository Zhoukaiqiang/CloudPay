<?php

namespace app\agent\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Session;

class Login extends Controller
{
    /**
     * 用户登录
     * @param [strin]   user_name 用户名（电话）
     * @param [stirng]  password  用户密码
     * @return [json] 返回信息
     * @throws Exception
     */
    public function login(Request $request)
    {
        $data = $request->param();
        check_params("agent_login", $data);
        $this->check_exist($data['phone'], 'phone', 1);
        $db_res = Db('total_agent')->field('id,username,agent_phone,status,password, parent_id')
            ->where('agent_phone', $data['phone'])->find();

        if ($db_res['password'] !== encrypt_password($data['password'], $data["phone"])) {
            return_msg(400, '用户密码不正确！');
        } else {
            unset($db_res['password']); //密码不返回
            /** 登录成功储存session */
            Session::set("username_", $db_res);
            return_msg(200, '登录成功！', $db_res);
        }
    }

    /**
     * 一键登录代理商系统
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agent_login(Request $request)
    {
        $data = $request->param();
        if (!isset($data['token'])) {
            return_msg(400, '非法登录');
        }
        if (md5($data['phone'] . 'token') != $data['token']) {
            return_msg(400, 'token验证失败');
        }
        $this->check_exist($data['phone'], 'phone', 1);
        $status = TotalAgent::field('status')->where('agent_phone', $data['phone'])->find();
        if ($status['status'] == 0) {
            return_msg(400, '该代理商不能进入代理商系统');
        }
        $info = TotalAgent::field('id,username,agent_phone,status, parent_id')
            ->where('agent_phone', $data['phone'])
            ->find();
        if ($info) {

            Session::set("username_", $info);

            return_msg(200, '登录成功', $info);
        } else {
            return_msg(400, '登录失败');
        }
    }


    /**
     * 检验账号是存在数据库
     * @param $value
     * @param string $type
     * @param $exist
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function check_exist($value, $type = 'phone', $exist)
    {
        $type_num = $type == 'phone' ? 2 : 4;
        $flag = $type_num + $exist;
        $phone_res = Db("total_agent")->where("agent_phone", $value)->find();
//        $email_res = db("total_admin")->where("email", $value)->find();
        $email_res = 0;
        switch ($flag) {
            /* 2+0 phone need no exist */
            case 2:
                if ($phone_res) {
                    return_msg(400, '手机号已经被占用');
                }
                break;
            /*  2+1 phone need exist */
            case 3:
                if (!$phone_res) {
                    return_msg(400, '手机号不存在！');
                }
                break;
            /* 4+0 email need no exist */
            case 4:
                if ($email_res) {
                    return_msg(400, '此邮箱已经被占用！');
                }
                break;
            /* 4+1 email need exist */
            case 5:
                if (!$email_res) {
                    return_msg(400, '此邮箱不存在！');
                }
                break;

        }

    }
    /**
     * 登出
     */
    public function logout()
    {
        Session::clear();
        if (Session::has("username_")) {
            return_msg(400, "失败");
        } else {
            return_msg(200, "成功");
        }
    }
}
