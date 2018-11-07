<?php

namespace app\admin\controller;

use app\admin\model\TotalAdmin;
use think\Db;
use think\Exception;
use think\Request;
use think\Loader;

class Personal extends Admin
{
    /**
     * 显示人员列表
     *
     * @return \think\Response
     * @throws Exception
     */
    public function index()
    {
        //查询不是超级管理员的人员

        $rows = TotalAdmin::count("id");

        $pages = page($rows);

        $data['list'] = TotalAdmin::limit($pages['offset'], $pages['limit'])
            ->order("id", "DESC")
            ->select();

//        $res = collection($data)->toArray();
        $data["pages"] = $pages;
        check_data($data["list"], $data);
    }


    /**
     * 添加人员
     *
     * @return \think\Response
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = request()->post();
            check_params("user", $data, "AdminValidate");
            //查询用户名是否存在
            $user = TotalAdmin::where('name', $data['name'])->find();
            if ($user) {
                return_msg(400, '用户名已经存在');
            }
            //查询用户账号是否存在
            check_phone_exists("cloud_total_admin", $data["phone"], '0');
            //获取创建时间
            $data['create_time'] = time();
            $data['password'] = encrypt_password($data['password'], $data['phone']);
            $info = Db::name("total_admin")->insertGetId($data);
            if ($info) {
                return_msg(200, '保存成功', $info);
            } else {
                return_msg(400, '保存失败');
            }
        }
    }

    /**
     * 人员详情页面
     *
     * @param  id int 人员id
     * @return \think\Response
     */
    public function detail()
    {
        if (request()->isPost()) {
            //需要传一个主键id
            $data = request()->post();
            $pwd = \request()->post("password");
            //验证
            check_params("user_edit", $data, "AdminValidate");
            if ($data['status'] == -1) {
                $data['status'] = 0;
            }
            //查询用户名是否存在
            $where = [
                'id' => ['<>', $data['id']],
                'name' => ['=', $data['name']]
            ];
            $user = TotalAdmin::where($where)->find();
            if ($user) {
                return_msg(400, '用户名已经存在');
            }
            //查询用户账号是否存在
            $where = [
                'id' => ['<>', $data['id']],
                'phone' => ['=', $data['phone']]
            ];
            $result = TotalAdmin::where($where)->find();
            if ($result) {
                return_msg(400, '用户账号已经存在');
            }
            if (isset($pwd)) {
                $data['password'] = encrypt_password($pwd, $data["phone"]);
            }
            $info = TotalAdmin::update($data, true);
            if ($info) {
                return_msg(200, '修改成功', $info);
            } else {
                return_msg(400, '修改失败');
            }
        } else {
            $id = request()->param('id');
            $data = TotalAdmin::where('id', $id)->find();
            unset($data['password']);
            return_msg(200, 'success', $data);
        }
    }


}
