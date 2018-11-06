<?php

/**
 * Created by KaiQiang-use by PhpStorm.
 * User: fennu
 * Date: 2018/9/4
 * Time: 16:35
 */


namespace app\Merchant\controller;

use app\admin\model\TotalMerchant;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;
use think\Session;
use think\Validate;

/**
 * Class User
 * @package app\index\controller
 */
class Login extends Controller
{
    /**
     * 前置操作（调用某个方法时先调用设置的前置方法）
     * @var array
     */

    /**
     * @throws Exception
     */
    protected function _initialize()
    {
        parent::_initialize();
        $this->request = Request::instance()->param();
    }



    /**
     * 用户登录 -- 可以 商户登录 / 门店店员登录
     * @param [string]   user_name 用户名（电话）
     * @param [string]  password  用户密码
     * @method POST
     * @return [json] 返回信息
     * @throws Exception
     */
    public function login()
    {
        if (request()->isPost()) {
            $data = \request()->post();
            /** 检验参数 */
            check_params("merchant_login", $data);
            $this->check_exist($data['phone'], 'phone', 1);
            $db_res = MerchantUser::where("phone", $data["phone"])->field("shop_id,id,name,phone,role,password")->find();
            if (!$db_res) {
                $Merc = new TotalMerchant();
                $db_res = $Merc::get(["phone" => $data['phone']])->alias("merc")
                    ->join("cloud_merchant_shop ms" ,"ms.merchant_id = merc.id")
                    ->field("merc.id, merc.name, merc.phone, merc.password, merc.status, ms.id as shop_id")
                    ->find();
            }

            $res = $db_res->toArray();
            $shopName = MerchantShop::get($res["shop_id"])["shop_name"];
            Session::set("shop_name", $shopName,'app');

            if ($db_res['password'] !== encrypt_password($data['password'], $data["phone"])) {
                return_msg(400, '用户密码不正确！');
            } else {

                /** 登录成功设置session */
                if (empty($res["role"])) {
                    Session::set("username_", ["id" => $res['id'], "role" => -1], 'app');
                } else {
                    Session::set("username_", ["id" => $res['id'], "role" => $res["role"]], 'app');
                }
                unset($res['password']); //密码不返回
                if (!isset($res['role'])) {
                    $res["role"] = -1;
                }

                return_msg(200, '登录成功！', $res);
            }
        }

    }

    /**
     *一键登录商户后台
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_login()
    {
        if (request()->isPost()) {
            $data = request()->param();
            /** 检验参数 */
            if(!isset($data['token'])){
                return_msg(400,'非法登录');
            }
            if(md5($data['phone'].'token')!=$data['token']){
                return_msg(400,'token验证失败');
            }
            $this->check_exist($data['phone'], 'phone', 1);

            $db_res = TotalMerchant::alias("merc")->where("merc.phone", $data['phone'])
                ->join("cloud_merchant_shop ms" ,"ms.merchant_id = merc.id")
                ->field("merc.id, merc.name, merc.phone, merc.password, merc.status, ms.id as shop_id")
                ->find();

            $res = $db_res->toArray();

                /** 登录成功设置session */
                if (empty($res['role'])) {
                    Session::set("username_", ["id" => $res['id'], "role" => -1], 'app');
                } else {
                    Session::set("username_", ["id" => $res['id'], "role" => $res["role"]], 'app');
                }
                if (!isset($res['role'])) {
                    $res["role"] = -1;
                }

                return_msg(200, '登录成功！', $res);

        }
    }
    /**
     * 退出登录
     * @param Request $request
     */
    public function logout(Request $request)
    {
        Session::clear("app");
        if (!Session::has("username_", "app")) {
            return_msg(200, "退出成功");
        }

    }

    /**
     * 用户改密码
     * @param [int] phone 用户手机号
     * @param [string] ini_pwd 老密码
     * @param [string]  password 新密码
     * @return [json] 返回消息
     * @throws Exception
     */
    public function changePwd(Request $request)
    {
        /* 接受参数 */
        $query = $request->param();

        check_params("change_pwd", $query);
        /* 检测用户名并取出数据库中的密码 */
        $this->check_exist($query["phone"], "phone", 1);
        $where['phone'] = $query['phone'];

        /* 判断原始密码是否正确 */
        if (Session::get("username_", "app")["role"] == -1) {
            $db_ini_pwd = TotalMerchant::where($where)->value("password");
        } else {
            $db_ini_pwd = MerchantUser::where($where)->value("password");
        }

        if ($db_ini_pwd !==  encrypt_password( $query['ini_pwd'], $query["phone"])) {
            return_msg(400, '旧密码不正确!');
        }

        if (Session::get("username_", "app")["role"] == -1) {
            $res = Db::name('total_merchant')->where($where)->setField('password', encrypt_password($query['password'], $query['phone']));

        }else {
            $res = Db::name('merchant_user')->where($where)->setField('password', encrypt_password($query['password'], $query['phone']));
        }

        /* 把新的密码存入数据库 */
        if ($res !== 0) {
            return_msg(200, '密码修改成功！');
        } else {
            return_msg(400, '密码修改失败！');
        }
    }

    /**
     * 找回密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findPwd()
    {
        /* 接受参数 */
        $query = \request()->param();
        check_params("find_pwd", $query);
        /* 检测用户是否存在 */
        $this->check_exist($query['phone'], 'phone', 1);
        /* 检测 验证码 */
        $this->check_code($query['phone'], $query['code'], $query["time"]);


        $where['phone'] = $query['phone'];

        /* 修改数据库 */
        if (Session::get("username_", "app")["role"] == -1) {
            $res = db('total_merchant')->where($where)->setField('password', encrypt_password($query['password'], $query['phone']));
        } else {
            $res = db('merchant_user')->where($where)->setField('password', encrypt_password($query['password'], $query['phone']));
        }

        if ($res !== false) {
            return_msg(200, '密码修改成功！');
        } else {
            return_msg(400, '密码修改失败！');
        }
    }


    /**
     * 验证验证码
     * @param string $user_name [用户名]
     * @param int $code [验证码]
     */
    public function check_code($user_name, $code, $time)
    {
        /* 检测是否超时 创建session */
        $last_time = session($user_name . '_last_send_time', $time);

        if (time() - $last_time > 300) {
            return_msg(400, '验证超时，请在5分钟内验证！');
        }

        /* 检测验证码是否正确 */
        $md5_code = md5($user_name . '_' . md5($code));

        if (session($user_name . "_code") !== $md5_code) {
            return_msg(400, '验证码不正确！');
        }

        /*不管正确与否，每个验证只验证一次*/
        session($user_name . '_code', null);
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
            return_msg(200, '邮箱绑定成功！');
        } else {
            return_msg(400, '邮箱绑定失败！');
        }
    }


    /**
     * 发送短信到手机
     * @param $phone
     * @param $msg
     */
    public function send_msg_to_phone($phone, $msg)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend.json');
        //curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        //配置submail
        $data = [
            'appid' => '27075', //应用id
            'to' => $phone,     //要接受短信的电话
            'project' => 'Jaayb', //模板标识
            'vars' => "{'code': '" . $msg . "'}",
            'signature' => '5ac305ef38fb126d2a0ec5304040ab7d', //应用签名
        ];

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($res);
        if ($res->status !== 'success') {
            return false;
        } else {
            return true;
        }

    }

    /**
     * 验证手机号是否存在
     * @param $value
     * @param string $type
     * @param $exist
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_exist($value, $type = 'phone', $exist)
    {
        $type_num = $type == 'phone' ? 2 : 4;
        $flag = $type_num + $exist;
        $phone_res['merchant'] = Db("total_merchant")->where("phone", $value)->field("phone")->find();
        $phone_res['user'] = Db("merchant_user")->where("phone", $value)->field("phone")->find();

//        $email_res = db("total_admin")->where("email", $value)->find();
        if (!empty($phone_res['merchant'])) {
            $phone_res = $phone_res['merchant'];
        } else {
            $phone_res = $phone_res['user'];
        }
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

}
