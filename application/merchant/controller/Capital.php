<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;

class Capital extends Common
{
    public $url = 'https://gateway.starpos.com.cn/emercapp';

    /**
     * 结算列表
     * @status  1--成功 2--提现中 3--失败
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cash(Request $request)
    {
        if ($request->isGet()) {
            $param["status"] = $request->param("status");
            if (isset($param["status"])) {
                $param["status_flag"] = "eq";
            } else {
                $param["status_flag"] = "<>";
                $param["status"] = -1;
            }
            $where = [
                "merchant_id" => $this->merchant_id,
                "status" => [$param["status_flag"], $param["status"]],
            ];
            $rows = Db::name("merchant_withdrawal")->where($where)->count("id");
            $pages = page($rows);

            $res["list"] = Db::name("merchant_withdrawal")->where($where)->select();

            $res["pages"] = $pages;
            $res["pages"]["rows"] = $rows;
            check_data($res["list"], $res);
        }
    }

    /**
     * PC端---提现搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cash_search(Request $request)
    {
        $time = $request->param("time");

        if (isset($time)) {
            $time_flag = "between";
            $time = explode(',', $time);
        } else {
            $time_flag = ">";
            $time = 0;
        }

        $where = [
            "merchant_id" => $this->merchant_id,
            "create_time" => [$time_flag, $time],
        ];

        $rows = Db::name("merchant_withdrawal")->where($where)->count("id");
        $pages = page($rows);

        $res["list"] = Db::name("merchant_withdrawal")->where($where)->select();

        $res["pages"] = $pages;
        $res["pages"]["rows"] = $rows;
        check_data($res["list"], $res);

    }

    /**
     * PC端资金---个人资料
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function pc_profile()
    {
        $res = TotalMerchant::get($this->merchant_id);

        $amount = Order::where(["merchant_id" => $this->merchant_id, "pay_time" => [">", 0]])->sum("received_money");

        $data = $res->visible(["name", "phone", "id_card", "account_no"], true)->toArray();
        $data["amount"] = $amount;
        check_data($data);
    }

    /**
     * PC端--绑定银行卡
     * @param Request $request
     */
    public function pc_bind(Request $request)
    {
        if ($request->isPost()) {
            $param = [];
            /** 请求bank_query */
            check_params("bind_card", $param, "MerchantValidate");

            $param["id"] = $this->merchant_id;
            $param["account_no"] = $request->param("account_no");
            $param["id_card"] = $request->param("id_card");
            $param["open_bank"] = $request->param("open_bank");

            /** 获取改开户行支行联行行号 */
            $param["open_branch"] = $request->param("open_branch");

            $param["phone"] = $request->param("phone");
            $param["account_name"] = $request->param("account_name");
            $this->mermachant_edit();

            $check_bank = $this->check_bank($param);

            /** 存入数据库 */

        }
    }


    /**
     * 银行卡绑定查询 -- 走聚合数据接口
     * @param $arg
     */
    public function check_bank($arg)
    {
        return 1;
    }

    /*
     * 商户修改申请
     * 商户审核通过后，修改商户为修改未完成状态
     * @param Request $request
     */
    public function merchant_edit($arg)
    {

        $id = $arg["id"];
        $serviceId = 6060605;
        $version = 'V1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $id)->field('mercId,orgNo,check_flag')->select();

        //商户是否通过审核
//        if($data[0]['check_flag']==1) {
        $resul = ['serviceId' => $serviceId, 'version' => $version, 'mercId' => $data[0]['mercId'], 'orgNo' => $data[0]['orgNo']];
        //获取签名域
        $resul_age = sign_ature(0000, $resul);
        $resul['signValue'] = $resul_age;
        //向新大陆接口发送信息验证
        $par = curl_request($this->url, true, $resul, true);

        $bbntu = json_decode($par, true);
        $return_sign = sign_ature(1111, $resul);


        if ($bbntu['msg_cd'] === 000000) {
            if ($return_sign == $bbntu['signValue']) {
                //商户状态修改为修改未完成
                Db::table('merchant_incom')->where('merchant_id', $id)->update(['log_no' => $bbntu['log_no'], 'status' => 2]);

                return_msg(200, 'success', $bbntu['msg_dat']);
            } else {
                return_msg(400, 'error', $bbntu['msg_dat']);
            }
        } else {
            return_msg(500, 'error', $bbntu['msg_dat']);
        }
//        }else{
//            return_msg(100,'error','商户审核未通过');
//        }

    }

    /**
     * 商户资料修改
     * 商户资料修改是的状态（注册未完成，修改未完成，注册拒绝，修改拒绝）
     * @param Request $request
     */
    public function commercial_edit(Request $request)
    {
        $del = $request->post();

        $del['serviceId'] = 6060604;
        $del['version'] = 'V1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $del['merchant_id'])->field('log_no,mercId,stoe_id,mcc_cd,status')->select();
        //查看商户是否是完成状态
        if ($data[0]['status'] != 1) {
            $aa = [];
            foreach ($data[0] as $k => $v) {
                $aa[$k] = $v;
            }
            $dells = $del + $aa;
            $sign_ature = sign_ature(0000, $del);
            $dells['signValue'] = $sign_ature;
            //向新大陆接口发送信息验证

            $par = curl_request($this->url, true, $del, true);

            $par = json_decode($par, true);
            //返回数据的签名域
//        dump($par);die;

            $return_sign = sign_ature(1111, $par);

            if ($par['msg_cd'] === 000000) {
                if ($par['signValue'] == $return_sign) {
                    $del['status'] = 0;
                    Db::table('merchant_incom')->where('merchant_id', $del['merchant_id'])->update($del);

                    return_msg(200, 'error', $par['msg_dat']);
                } else {
                    return_msg(400, 'error', $par['msg_dat']);
                }
            } else {
                return_msg(500, 'error', $par['msg_dat']);
            }
        } else {
            return_msg(100, 'error', '商户为审核完成状态，请先申请修改');
        }


    }

    /**
     * PC端--对账列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * mark
     */
    public function pc_bill(Request $request)
    {
        if ($request->isGet()) {
            $rows = MerchantShop::where(["merchant_id" => $this->merchant_id])->count("id");
            $pages = page($rows, 2);
            $res = MerchantShop::where(["merchant_id" => $this->merchant_id])->limit($pages["offset"], $pages["limit"])->select();
            check_data($res, $res, 0);
            $shop_ids = [];
            foreach ($res as $v) {
                $shop_ids[] = $v["id"];
            }


            $field = ["ms.id", "ms.shop_name", "o.pay_type", "count(o.id) money_num", "sum(o.order_money) order_money", "sum(o.discount) discount", "sum(o.refund_money) refund_money", "sum(o.received_money) received_money"];


            $pay_type = ["wxpay", "alipay", "etc", "cash"];


            foreach ($shop_ids as $i) {

                foreach ($pay_type as $type) {
                    /** 退款次数 */
                    $refund[$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("refund_time", ">", 0)
                        ->where("ms.id", $i)
                        ->where("o.pay_type", $type)
//                        ->whereTime("pay_time", "d")
                        ->group("o.id")
                        ->count("ms.id");

                    $data["list"][$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("ms.id", $i)
//                    ->whereTime("pay_time", "d")
                        ->where("o.pay_type", $type)
                        ->field($field)
//                        ->group("o.id")
                        ->find();
                }

            }

//            $money = MerchantShop::alias("ms")
//                ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
//                ->where("ms.id", 106)
//                ->field($field)
//                ->where("o.pay_type", 'alipay')
//                ->select();

            foreach ($data["list"] as $k => &$list) {

                /** 退款笔数 */
                foreach ($pay_type as $type) {
                    $list[$type]["refund_num"] = $refund[$k][$type];
                }
            }

            $data["pages"] = $pages;
            $data["pages"]["rows"] = $rows;

            check_data($data["list"], $data);
        }
    }


}
