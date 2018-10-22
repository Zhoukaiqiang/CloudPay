<?php

namespace app\merchant\controller;

use app\admin\model\MerchantIncom;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use Endroid\QrCode\QrCode;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;

class Capital extends Common
{
//    public $url = 'https://gateway.starpos.com.cn/emercapp';//正式
     public $url = 'http://sandbox.starpos.com.cn/emercapp';//正式
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
     * @param array $param
     * @return array [处理并返回数组参数]
     */
    protected function search_item(Array $param)
    {
        $arr = [];
        foreach ($param as $k => &$v) {
            $flag = $k . "_flag";
            if (!$v) {
                $arr[$flag] = "<>";
                $v = -2;
            } else {
                $arr[$flag] = "eq";
            }
        }
        $query = $arr + $param;
        return $query;
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
        if (\request()->isGet()) {
            $res = TotalMerchant::get($this->merchant_id);

            $amount = Order::where(["merchant_id" => $this->merchant_id, "pay_time" => [">", 0]])->sum("received_money");

            $data = $res->visible(["name", "phone", "id_card", "account_no"], true)->toArray();
            $data["amount"] = $amount;
            check_data($data);
        }
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

            $card = TotalMerchant::get($this->merchant_id)["id_card"];

            $param["id"] = $this->merchant_id;
            $param["id_card"] = $card;
            $param["account_name"] = $request->param("account_name");
            $param["phone"] = $request->param("phone");
            $param["account_no"] = $request->param("account_no");

            $param["open_bank"] = $request->param("open_bank");

            /** 获取开户行支行联行行号 */
            $param["open_branch"] = $request->param("open_branch");

            check_params("bind_card", $param, "MerchantValidate");


            $this->check_bank($param);
            /** 请求商户修改接口 */
            $this->merchant_edit($param);
            /** 存入数据库 */

        }
    }


    /**
     * 银行卡绑定查询 -- 走聚合数据接口
     * @param $arg
     * @return bool / msg
     */
    protected function check_bank($arg)
    {

        $param = [
            "realname" => $arg["account_name"],
            "idcard" => (string)$arg["id_card"],
            "bankcard" => (string)$arg["account_no"],
            "mobile" => (string)$arg["phone"],
            "key" => "4c0307cd5fafa19a2da4ffe877370313",
        ];
        /** @var [string] 聚合支付请求API $url */
        $url = "http://v.juhe.cn/verifybankcard4/query";
        $res = $this->curl_request($url, true, $param, false);


        $res = json_decode($res, true);
        if ($res["result"]["res"] == "2") {
            return_msg(400, $res["result"]["message"]);
        } else {
            return true;
        }
    }


    /**
     * Curl 请求
     * @param $url
     * @param bool $post
     * @param array $params
     * @param bool $https
     * @return mixed
     */
    protected function curl_request($url, $post = false, $params = [], $https = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array("application/json;") );
        }
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    /*
     * 商户修改申请
     * 商户审核通过后，修改商户为修改未完成状态
     * @param Request $request
     */
    public function merchant_edit($arg)
    {
        $id = $arg["id"];
//        $id=102;
        $serviceId = '6060605';
        $version = 'V1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $id)->field('mercId,orgNo')->select();

        //商户是否通过审核
//        if($data[0]['check_flag']==1) {
        $resul = ['serviceId' => $serviceId, 'version' => $version, 'mercId' => $data[0]['mercId'], 'orgNo' => $data[0]['orgNo']];
        //获取签名域
        $resul_age = sign_ature(0000, $resul);
        $resul['signValue'] = $resul_age;
//        halt($resul);
        //向新大陆接口发送信息验证
        $par = curl_request($this->url, true, $resul, true);

        $bbntu = json_decode($par, true);
//        halt($bbntu);
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
     * @rule 商户资料修改
     * @rule 商户资料修改是的状态（注册未完成，修改未完成，注册拒绝，修改拒绝）
     * @param Request $request
     * @throws Exception
     * @return [bool / string] msg
     */
    public function commercial_edit(Request $request)
    {
//        $merchant_id=102;
        if($request->isPost()){
            $del = $request->post();
            $del['serviceId'] = 6060604;
            $del['version'] = 'V1.0.1';
            $data = Db::name('merchant_incom')->where('merchant_id', $this->merchant_id)->field('log_no,mercId,stoe_id,status')->find();
            //商户为未完成状态才可以修改
//        halt($data);
            if ($data['status'] == 2) {
                $aa = [];
                foreach ($data as $k => $v) {
                    $aa[$k] = $v;
                }
                $dells = $del + $aa;
//            halt($dells);
                //mark 调试到这里 by--端木
                $sign_ature = sign_ature(0000, $del);

                $dells['signValue'] = $sign_ature;
                //向新大陆接口发送信息验证

                $par = curl_request($this->url, true, $del, true);

                $par = json_decode($par, true);
                //返回数据的签名域

                $return_sign = sign_ature(1111, $par);

                if ($par['msg_cd'] === 000000) {
                    if ($par['signValue'] == $return_sign) {
                        $del['status'] = 0;
                        Db::name('merchant_incom')->where('merchant_id', $del['merchant_id'])->update($del);

                        return_msg(200, 'success', $par['msg_dat']);
                    } else {
                        return_msg(400, 'error', $par['msg_dat']);
                    }
                } else {
                    return_msg(500, 'error', $par['msg_dat']);
                }
            } else {
                return_msg(100, 'error', '商户为审核完成状态，请先申请修改');
            }
        }else{
            //取出商户信息
            $data = MerchantIncom::where('merchant_id',$this->merchant_id)->find();

            return_msg(200,'success',$data);
        }



    }

    /**
     * PC端--对账列表
     * @param Request $request [name] 门店搜索
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_bill(Request $request)
    {
        if ($request->isGet()) {
            /** @var  $rows */
            $param["name"] = $request->param("name");
            $rows = MerchantShop::where(["merchant_id" => $this->merchant_id])->count("id");
            $pages = page($rows, 2);
            $res = MerchantShop::where(["merchant_id" => $this->merchant_id])->limit($pages["offset"], $pages["limit"])->select();
            check_data($res, $res, 0);
            $shop_ids = [];
            foreach ($res as $v) {
                $shop_ids[] = $v["id"];
            }
            $query = $this->search_item($param);

            $where = [
                "ms.shop_name" => [$query["name_flag"], $query["name"]],
            ];
            $field = ["ms.id", "ms.shop_name", "o.pay_type", "count(o.id) money_num", "sum(o.order_money) order_money", "sum(o.discount) discount", "sum(o.refund_money) refund_money", "sum(o.received_money) received_money"];


            $pay_type = ["wxpay", "alipay", "etc", "cash"];


            foreach ($shop_ids as $i) {

                foreach ($pay_type as $type) {
                    /** 退款次数 */
                    $refund[$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("refund_time", ">", 0)
                        ->where($where)
                        ->where("ms.id", $i)
                        ->where("o.pay_type", $type)
                        ->whereTime("pay_time", "d")
                        ->group("o.id")
                        ->count("ms.id");

                    $data["list"][$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("ms.id", $i)
                        ->where($where)
                        ->whereTime("pay_time", "d")
                        ->where("o.pay_type", $type)
                        ->field($field)
//                        ->group("o.id")
                        ->find();


                    if (empty($data["list"][$i][$type]["id"])) {
                        unset($data["list"][$i]);
                    } elseif (empty($data["list"][$i][$type]["pay_type"])) {
                        unset($data["list"][$i][$type]);
                    }
                }
                if (isset($data["list"][$i]) && !count($data["list"][$i])) {
                    unset($data["list"][$i]);
                }

            }


            foreach ($data["list"] as $k => &$list) {

                /** 退款笔数 */
                foreach ($pay_type as $type) {
                    if (isset($list[$type])) {
                        $list[$type]["refund_num"] = $refund[$k][$type];

                    }
                }
            }

            $data["pages"] = $pages;
            $data["pages"]["rows"] = $rows;

            check_data($data["list"], $data);
        }
    }


    /** 交易查询接口测试 */
    public function test()
    {
        /** @var [string] 客户主扫 $url */
        $true_url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/sdkBarcodePosPay.json";
        session("msg", ["tradeNo" => (string)generate_order_no(12), "txnTime" => (string)date("YmdHms", time())]);
        $param = [
            "amount" => "001",
            "total_amount" => "001",
            "payChannel" => "WXPAY",
//            "subject"  => "云商付测试收款",
//            "selOrderNo"  => (string)generate_order_no(12),
//            "goods_tag"   => "会员优惠20",
//            "attach"    => '',
            /*public 公共参数 */
            "opSys" => "3",
            "characterSet" => "00",
            "orgNo" => ORG_NO,
            "mercId" => "800332000002146",
            "trmNo" => "95445645",
            "tradeNo" => session("msg")["tradeNo"],
            "txnTime" => session("msg")["txnTime"],
            "signType" => "MD5",
            "version" => "V1.0.0"
        ];
        $param["signValue"] = sign_ature(0000, $param, "0FF9606C39C2CCF1515E5CE108B506F0");

        $res = curl_request($true_url, true, $param, true);

        $res = json_decode($res, true);

        if ($res["returnCode"] !== "000000") {
            return_msg(400, urldecode($res["message"]));
        } else {
            /** 成功 返回二维码 */
            $qrcode = new QrCode($res["payCode"]);
            header("Content-type:" . $qrcode->getContentType());
            echo $qrcode->writeString();
            exit;
        }
    }

    /**
     * 订单查询
     */
    public function order_query($param = [])
    {

        $true_url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/sdkQryBarcodePay.json";

        $query = [
            "qryNo" => "20181022141651", //查询流水
            /** 公共参数 */
            "opSys" => "3",
            "characterSet" => "00",
            "orgNo" => ORG_NO,
            "mercId" => "800332000002146",
            "trmNo" => "95445645",    //设备号
            "tradeNo" => session("msg")["tradeNo"],
            "txnTime" => session("msg")["txnTime"],
            "signType" => "MD5",
            "version" => "V1.0.0"
        ];

        $query["signValue"] = sign_ature("0000", $query, "0FF9606C39C2CCF1515E5CE108B506F0");

        $res = curl_request($true_url, true, $query, true);

        halt(urldecode($res));
    }

}

