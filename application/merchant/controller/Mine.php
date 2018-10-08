<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Request;

class Mine extends Controller
{
    /**
     * 显示签约信息   0 支付宝  1-微信
     * @method Get
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_sign_info(Request $request)
    {

        if ($request->isGet()) {
            $merchant_id = $request->param("id");
            $usage= TotalMerchant::get($merchant_id)->field("channel")->find();

            $rate['usage'] = (int)$usage->toArray()['channel'];
            $rate['alipay_rate'] = 0.38;
            $rate['wx_rate'] = 0.38;
            $rate['union_rate'] = 0.55;

            return_msg(200, "success", $rate);
        }

    }


    public function set_print_type(Request $request) {
        $param = $request->param();
        $param['merchant_union'] = $param['merchant_union'];  //商户联
        $param['user_union'] = $param['user_union'];          //客户联
        /** 两个选项都勾选 打印两张 */
        if (!empty($param["merchant_union"]) && !empty($param['user_union'])) {
            $this->go_print(2);
        }else if (!empty($param['merchant_union'])) {
            $info['union_type'] = "商户联";
        }else if (!empty($param['user_union'])) {
            $info['union_type'] = "客户联";
        }
            //mark
        $info['table'] = 13;
    }


    public function go_print($num = 1) {

    }

    /**
     * 显示我的资料.
     * @method Get
     * @param [int string] phone 手机号
     * @return \think\Response
     */
    public function get_profile(Request $request)
    {
        if ($request->isGet()) {
            $query = $request->param();

            /** 检验参数 */
            check_params("merchant_get_profile",$query);

            $identify = $this->check_identify($query['phone']);


            if ($identify == "user") {

                $res = MerchantUser::where(["phone"  => $query['phone']])->field("name,phone,role")->find();
                return_msg(200, "success", $res);

            }else {

                $res = TotalMerchant::where(["phone" => $query['phone']])->field("username,phone")->find();
                $res['role'] = "商户";
                return_msg(200, "success", $res);

            }

        }
    }


    /**
     * 改密码
     * @method Post
     * @param [phone, password]
     * @param Request $request
     * @throws \think\exception\DbException
     */
    public function change_pwd(Request $request) {
        if ($request->isPost()) {
            $query = $request->param();
            /** 检验参数 */
            check_params("merchant_change_pwd", $query);
            $identify = $this->check_identify($query['phone']);
            $password = encrypt_password($query['password'], $query['phone']);
            if ($identify == "merchant") {
                $res = TotalMerchant::get(["phone" => $query['phone']])->save([
                    "password" => $password
                ]);

            }else {
                $res = MerchantUser::get(["phone" => $query['phone']])->save([
                    "password" => $password
                ]);
            }
            if ($res) {
                return_msg(200, "密码更改成功");
            }else {
                return_msg(400, "密码更新失败");
            }
        }

    }

    /**
     * 检查身份
     * @param $phone
     * @return string
     * @throws \think\exception\DbException
     */
    protected function check_identify($phone) {

        $merchant = TotalMerchant::get(["phone" => $phone]);
        if (!$merchant) {
            $res = MerchantUser::get(["phone" => $phone]);
            if($res) {
                return "user";
            }else {
                return_msg(400, "没有此人");
            }
        }else {
            $res = TotalMerchant::where(["phone" => $phone]);
            if($res) {
                return "merchant";
            }else {
                return_msg(400, "没有此人");
            }
        }
    }

}
