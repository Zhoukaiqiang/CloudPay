<?php

/**
 * Created by KaiQiang-use by PhpStorm.
 * User: fennu
 * Date: 2018/9/4
 * Time: 16:35
 */


namespace app\Merchant\controller;

use app\admin\controller\Common;
use app\admin\model\TotalMerchant;
use think\Exception;
use think\Loader;
use think\Request;
use think\Validate;

/**
 * Class User
 * @package app\index\controller
 */
class Login extends Common
{
    /**
     * 前置操作（调用某个方法时先调用设置的前置方法）
     * @var array
     */
    protected $beforeActionList = [
        //'bbb' => ['only' => 'test']
    ];

    protected $rules = [
        'Login' => [
            "login" => [
                ['phone','require|length:11', '账号必须填写|手机号长度为11'],
                ['password', 'require', '密码必须填写'],
            ],
        ],
    ];
    protected function _initialize()
    {
        parent::_initialize();
        $this->request = Request::instance()->param();

    }

    /**
     * 用户登录
     * @param [strin]   user_name 用户名（电话）
     * @param [stirng]  password  用户密码
     * @return [json] 返回信息
     * @throws Exception
     */
    public function login()
    {
        if (request()->isPost()) {
            $data = $this->request;
            $user_name_type = 'phone';
            /** 检验参数 */

            $this->check_params($data);

            $this->check_exist($data['phone'], 'phone', 1);
            $db_res = TotalMerchant::where('phone', $data['phone'])
                ->field('id,username,phone,status,password')->find();

            if ($db_res['password'] !== $this->encrypt_password($data['password'], $data["phone"])) {
                $this->return_msg(400, '用户密码不正确！');
            } else {
                unset($db_res['password']); //密码不返回
                $this->return_msg(200, '登录成功！', $db_res);
            }
        }

    }

    /**
     * 添加员工
     * @param [string] name  用户名称
     * @param  [int]   phone 用户手机号
     * @param  []
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addStaff()
    {

        $this->check_phone($this->request('phone'));
        $data['name'] = $this->request('name');
        $data['phone'] = $this->request('phone');
        $data['status'] = $this->request('status');

        $data['password'] = $this->request('password') ? $this->encrypt_password($this->request('password'), $data['phone']) : "cloudpay";

        $result = TotalAdmin::create($data);

        if (!$result) {
            $this->return_msg(400, '插入新成员失败!');
        } else {
            unset($result['password']);
            $this->return_msg(200, '插入新成员成功！', $result);
        }

    }

    /**
     * 检测用户是否存在于数据库
     *
     * @param $phone 用户手机号
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return [json]  检测结果
     */
    public function check_phone($phone)
    {
        $result = Db::table('cloud_total_admin')->where('phone', $phone)->find();
        if ($result) {
            return $this->return_msg(400, '用户已存在！');
        }
    }

    public function delStaff(Request $request)
    {
        $id = $request->param("id");
        if (!$id) {
            return;
        }
        $result = Db::table('cloud_total_admin')->delete($id);
        if ($result) {
            $this->return_msg(200, '删除成功');
        } else {
            $this->return_msg(400, '删除失败');
        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editStaff()
    {
        $id = $this->request('id');
        if (empty($id)) {
            return;
        }

        $data['name'] = $this->request('name');

        $data['phone'] = $this->request('phone');
        $data['status'] = $this->request('status');
        $data['create_time'] = date('Y-m-d H:m:s');
        $data['password'] = $this->request('password');

        $result = Db::table('cloud_total_admin')->where('id', $id)->update($data);
        if (!$result) {
            $this->return_msg(400, '修改新成员失败!');
        } else {
            $this->return_msg(200, '修改新成员成功！', $result);
        }

    }

    /**
     * 用户改密码
     * @param [int] phone 用户手机号
     * @param [string] ini_pwd 老密码
     * @param [string]  password 新密码
     * @return [json] 返回消息
     */
    public function changePwd(Request $request)
    {
        /* 接受参数 */
        $data = $request->param();

        /* 检测用户名并取出数据库中的密码 */
        $user_name_type = $this->check_username($data['phone']);
        switch ($user_name_type) {
            case "phone":
                $this->check_exist($data['phone'], 'phone', 1);
                $where['phone'] = $data['phone'];
                break;
            case "email":
                # code..
                break;
        }
        /* 判断原始密码是否正确 */
        $db_ini_pwd = db('total_admin')->where($where)->value('password');
        if ($db_ini_pwd !== $data['ini_pwd']) {
            $this->return_msg(400, '密码错误!');
        }

        /* 把新的密码存入数据库 */
        $res = db('total_admin')->where($where)->setField('password', $this->encrypt_password($data['password'], $data['phone']));
        if ($res !== false) {
            $this->return_msg(200, '密码修改成功！');
        } else {
            $this->return_msg(400, '密码修改失败！');
        }
    }

    public function findPwd()
    {
        /* 接受参数 */
        $data = $this->params;

        /* 检测验证码 */
        $this->check_code($data['phone'], $data['code']);

        /* 检测用户名 */
        $user_name_type = $this->check_username($data['phone']);
        switch ($user_name_type) {
            case "phone":
                $this->check_exist($data['phone'], 'phone', 1);
                $where['phone'] = $data['phone'];
                break;
            case "email":
                # code..
                break;
        }

        /* 修改数据库 */
        $res = db('total_admin')->where($where)->setField('password', $this->encrypt_password($data['password'], $data['phone']));
        if ($res !== false) {
            $this->return_msg(200, '密码修改成功！');
        } else {
            $this->return_msg(400, '密码修改失败！');
        }
    }


    /**
     * 绑定邮箱
     * @paraam 邮箱地址 [string]
     * @param [int] 验证码
     * @retrun [json] 返回验证结果
     */
    public function bind_email()
    {
        /* 接受参数 */
        $data = $this->params;
        /* 验证验证码 */
        $this->check_code($data['email'], $data['code']);

        /* 修改数据库 */
        $res = db('total_admin')->where('id', $data['user_id'])->setField('email', $data['email']);
        if ($res !== false) {
            $this->return_msg(200, '邮箱绑定成功！');
        } else {
            $this->return_msg(400, '邮箱绑定失败！');
        }
    }


    /**
     *  验证参数是否正确
     * @param   [array] $arr 所有参数
     * @return [json] 参数验证结果/返回参数
     */
    public function check_params($arr)
    {
        /* 获取参数的验证规则 */
        try {
            $rule = $this->rules[request()->controller()][request()->action()];
        }catch (Exception $e) {return true;}

        /* 验证参数并返回错误 */
        $this->validater = new Validate($rule);
        if (!$this->validater->check($arr)) {
            $this->return_msg(400, $this->validater->getError());
        }

        return $arr;
    }

}
