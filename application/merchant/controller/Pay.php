<?php
/**
 * Created by KaiQiang-use by PhpStorm.
 * User: fennu
 * Date: 2018/11/3
 * Time: 19:54
 */

namespace app\merchant\controller;


use Endroid\QrCode\QrCode;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Pay extends Common
{

    protected $merchant_id;
    protected $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/sdkBarcodePosPay.json";  //客户主扫

    public function __initialize()
    {
        parent::_initialize();
        $this->merchant_id = Session::get("username_", "app")["id"];
    }

    /**
     * 生成H5点餐页面收款码
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function clientScan(Request $request)
    {
        $data = $request->post();
        /** （请求星POS） 获取交易信息 存入数据库*/
        $this->starPos($data);
    }

    /**
     * 获取WX/ALI首款码
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payWay(Request $request)
    {
        $data = $request->post();
        /** （请求星POS） 获取交易信息 存入数据库*/
        $this->generateCode($data);
    }

    /**
     * 生成WX/ALI首款码
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function generateCode($param)
    {
        $res = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        $trms = json_decode($res["rec"], true);
        if (count($trms)) {
            $trmNo = $trms[0]["trmNo"];
        } else {
            $trmNo = $trms;
        }

        /** 公共参数 */
        $data["opSys"] = "3";
        $data["characterSet"] = "00";
        $data["orgNo"] = "27573";
        $data["mercId"] = $res["mercId"];
        $data["trmNo"] = (string)$trmNo;
        $data["tradeNo"] = generate_order_no($this->merchant_id);
        $data["txnTime"] = (string)date("YmdHis");
        $data["signType"] = "MD5";
        $data["version"] = "V1.0.0";
        /** 客户主扫参数 */
        $data["amount"] = strval($param['amount'] * 100);
        $data["total_amount"] = strval($param['amount'] * 100);
        $data["payChannel"] = $param["channel"];

        $data["signValue"] = sign_ature(0000, $data, $res["key"]);

        $rp = curl_request($this->url, true, $data, true);
        $re = json_decode(urldecode($rp), true);

        if ($re["returnCode"] == "000000") {
            /** 生成二维码 */
            $qrCode = new QrCode($re["payCode"]);
            $filename = md5($qrCode->getText()) . ".png";
            $root = "uploads/";
            $pngAbsolutePath = $root . $filename;
            if (!file_exists($pngAbsolutePath)) {
                $qrCode->writeFile($pngAbsolutePath);
            }else {
                return_msg(400, "错误");
            }
            echo json_encode(["code"=> 200, "msg" => "成功", "data" => $pngAbsolutePath]);

            /** 订单入库 */
            switch ($re["result"]) {
                case "S":
                    $rst = 1;
                    break;
                case "F":
                    $rst = 2;
                    break;
                case "Z":
                    $rst = -1;  //未知
                    break;
                default:
                    $rst = 0;
            }
            $dt['order_money'] = (int)$re["total_amount"] / 100;
            $dt['received_money'] = (int)$re["amount"] / 100;
            $dt['create_time'] = time();
            $dt['pay_type'] = strtolower($param["channel"]);
            $dt['status'] = $rst;
            $dt['merchant_id'] = $this->merchant_id;
            $dt['logNo'] = $re["logNo"];
            $dt['order_no'] = $re["orderNo"];
            $dt['order_number'] = $data["tradeNo"];
            $dt['pay_time'] = strtotime($data["txnTime"]);
            /** 保存订单号到Session */
            Session::set("logNo", $re["logNo"], "_app");
            Db::name("order")->insertGetId($dt);

        } else {
            return_msg(400, $re["message"]);
        }
    }

    /**
     * H5点餐页面收款码
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function starPos($param)
    {
        $res = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        $trms = json_decode($res["rec"], true);
        if (count($trms)) {
            $trmNo = $trms[0]["trmNo"];
        } else {
            $trmNo = $trms;
        }
        $channel = $this->IsWeixinOrAlipay();
        if (empty($channel)) {
            return_msg(400, "渠道错误");
        } elseif ($channel) {
            /** 公共参数 */
            $data["opSys"] = "3";
            $data["characterSet"] = "00";
            $data["orgNo"] = "27573";
            $data["mercId"] = $res["mercId"];
            $data["trmNo"] = (string)$trmNo;
            $data["tradeNo"] = generate_order_no($this->merchant_id);
            $data["txnTime"] = (string)date("YmdHis");
            $data["signType"] = "MD5";
            $data["version"] = "V1.0.0";
            /** 客户主扫参数 */
            $data["amount"] = strval($param['amount'] * 100);
            $data["total_amount"] = strval($param['amount'] * 100);
            $data["payChannel"] = $channel;

            $data["signValue"] = sign_ature(0000, $data, $res["key"]);

            $rp = curl_request($this->url, true, $data, true);
            $re = json_decode(urldecode($rp), true);

            if ($re["returnCode"] == "000000") {
                /** 生成二维码 */
                $qrCode = new QrCode($re["payCode"]);
                $filename = md5($qrCode->getText()) . ".png";
                $root = "uploads/";
                $pngAbsolutePath = $root . $filename;
                if (!file_exists($pngAbsolutePath)) {
                    $qrCode->writeFile($pngAbsolutePath);
                }
                echo json_encode(["code"=> 200, "msg" => "成功", "data" => $pngAbsolutePath]);

                /** 订单入库 */
                switch ($re["result"]) {
                    case "S":
                        $rst = 1;
                        break;
                    case "F":
                        $rst = 2;
                        break;
                    case "Z":
                        $rst = -1;  //未知
                        break;
                    default:
                        $rst = 0;
                }
                $dt['order_money'] = (int)$re["total_amount"] / 100;
                $dt['received_money'] = (int)$re["amount"] / 100;
                $dt['create_time'] = time();
                $dt['pay_type'] = strtolower("$channel");
                $dt['status'] = $rst;
                $dt['merchant_id'] = $this->merchant_id;
                $dt['logNo'] = $re["logNo"];
                $dt['order_no'] = $re["orderNo"];
                $dt['order_number'] = $data["tradeNo"];
                $dt['pay_time'] = strtotime($data["txnTime"]);
                /** 保存订单号到Session */
                Session::set("logNo", $re["logNo"], "_app");
                Db::name("order")->insertGetId($dt);

            } else {
                return_msg(400, $re["message"]);
            }
        }
    }


    /**
     * 订单查询
     */
    public function order_query(Request $request)
    {
        $true_url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/sdkQryBarcodePay.json";
        $user = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        $trms = json_decode($user["rec"], true);

        if (count($trms)) {
            $trmNo = $trms[0]["trmNo"];
        } else {
            $trmNo = $trms;
        }
        $query = [
            "qryNo" => Session::get("logNo", "_app"), //查询流水
            /** 公共参数 */
            "opSys" => "3",
            "characterSet" => "00",
            "orgNo" => "27573",
            "mercId" => $user["mercId"],
            "trmNo" => (string)$trmNo,    //设备号
            "tradeNo" => generate_order_no($this->merchant_id),
            "txnTime" => (string)date("YmdHis"),
            "signType" => "MD5",
            "version" => "V1.0.0"
        ];
        $query["signValue"] = sign_ature("0000", $query, $user["key"]);

        $res = curl_request($true_url, true, $query, true);
        $res = json_decode(urldecode($res), true);
        if ($res["returnCode"] == "000000") {
            switch ($res["result"]) {
                case "S":
                    $rst = 1;
                    break;
                case "F":
                    $rst = 2;
                    break;
                case "Z":
                    $rst = -1;  //未知
                    break;
                case "D":
                    $rst = 2;  //关闭
                    break;
                default:
                    $rst = 0;
            }
            if ($res["result"] == "S") {
                Db::name("order")->where("logNo", $res["logNo"])->update(["status" => $rst]);
                Session::delete("logNo", "_app");
                return_msg(200, "交易成功！", $res["logNo"]);
            } elseif ($res["result"] == "D") {
                return_msg(400, "交易已撤销");
            } else {
                return_msg(400, "交易失败");
            }

        } else {
            return_msg(400, $res["message"]);
        }

    }

    /**
     * 检查用户使用微信还是支付宝扫码
     * @return string
     */
    protected function IsWeixinOrAlipay()
    {

        //判断是不是微信
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return "WXPAY";
        }
        //判断是不是支付宝
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return "ALIPAY";
        }
        //哪个都不是
        return $_SERVER["HTTP_USER_AGENT"];
    }


}