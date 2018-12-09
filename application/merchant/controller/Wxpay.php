<?php

namespace app\merchant\controller;

use think\Controller;
use think\Request;

class Wxpay
{
    public function wxpay(Request $request)
    {
        $appid= "wx1aeeaac161a210df";
        $mch_id = "1511906501";
        $notify_url = "http://pay.hzyspay.com/merchant/wxpay/notify";
        $key = "175fef1031207c84f1eb6b5ec4d5004a";
        $data = request()->param();
        $wechatAppPay = new WechatPay($appid, $mch_id, $notify_url, $key);
        $params['body'] = '商品描述'; //商品描述
        $params['out_trade_no'] = generate_order_no(); //自定义的订单号
        $params['total_fee'] = $data['amount'] * 100; //订单金额 只能为整数 单位为分
        $params['trade_type'] = 'NATIVE'; //交易类型 JSAPI | NATIVE | APP | WAP
        $result = $wechatAppPay->unifiedOrder( $params );
        //print_r($result); // result中就是返回的各种信息信息，成功的情况下也包含很重要的prepay_id
//2.创建APP端预支付参数
        /** @var TYPE_NAME $result */
        $data = @$wechatAppPay->getAppPayParams( $result['prepay_id'] );
// 根据上行取得的支付参数请求支付即可
        print_r($data);
    }

    public function notify()
    {
        $data = request()->param();
        halt($data);
    }
}
