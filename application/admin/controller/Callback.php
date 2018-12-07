<?php

namespace app\admin\controller;
use app\push\controller\Worker;
use think\Db;
use think\Request;

class Callback extends Worker
{
    /**
     * 接收星POS的推送消息
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function callback(Request $request)
    {

        $param = $request->param();

        $mid = Db::name("merchant_incom")->where("mercId",$param["BusinessId"])->field("merchant_id")->find();

        $trmId = Db::name("merchant_shop")->where("mercId",$param["BusinessId"])->column("id, rec");

        if (count($trmId)) {

            foreach ($trmId as $k => &$v) {
                $trm = json_decode($v, true);
                foreach ($trm as $vv) {
                    if (in_array($param["SDTermNo"], $vv)) {$shop_id = $k;}
                }
            }
        }

        $data = [
            "pay_time" => strtotime($param["TxnDate"].$param["TxnTime"]),    //结算日期
            "create_time" => strtotime($param["BalDate"]),
            "received_money" => $param["TxnAmt"],  //实际金额
            "order_money" => $param["TxnAmt"],  //交易金额
            "status" => (int)$param["TxnStatus"], //交易状态
            "order_remark" => $param["attach"],  //附加信息
            "orderNo" => $param["logNo"],  //交易流水号
            "order_no" => $param["OfficeId"],
            "order_number" => $param["ChannelId"],  //交易流水号
            "merId" => $param["AgentId"],    // 合作商号
            "merchant_id" => $mid["merchant_id"],
            "payer" => $param["UserId"],
            "shop_id"  =>  $shop_id,
            "cashier"  =>  $param["attach"],
        ];
        $pattern = preg_match('/^[A-Z]+/', $param["TxnCode"]);
        if ($pattern) {
            $data["pay_type"] = "etc";
        } else {
            $ty = intval($param["PayChannel"]);
            switch ($ty) {
                case 1:
                    $data["pay_type"] = "alipay";
                    break;
                case 2:
                    $data["pay_type"] = "wxpay";
                    break;
                default:
                    $data["pay_type"] = "etc";
            }
        }
        check_params("add_order", $data,"AdminValidate");
        /** 根据流水号，判断要更新哪个订单 */

        /** @var [int] $res */

        $res = Db::name("order")->where("order_no", $param["OfficeId"])->find();
        if ($res) {
            Db::name("order")->where("order_no", $param["OfficeId"])->update($data);
        } else {
            $rr = Db::name("order")->insert($data);
            if ($rr) {
                /** 发送语音播报 */
                return_msg(200, "success!");
            }else{
                return_msg(400, "fail!");
            }
        }

    }
}