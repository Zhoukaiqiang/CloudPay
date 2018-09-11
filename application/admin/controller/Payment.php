<?php
namespace app\admin\controller;

use think\Request;
class Payment extends Common{
    //测试服务器
    private $domain = 'http://local.cloud.com';
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function payOrder()
    {

        //获取订单号
        $where['id'] = input('post.orderid');
        //查询订单信息
        $order_info = db('order')->where($where)->find();
        $reoderSn = $order_info['ordersn'];
        //获取支付方式
        $pay_type = input('post.paytype');//微信支付 或者支付宝支付
        //获取支付金额
        $money = 0.01;//$order_info['realprice'];
        //判断支付方式

        if ($pay_type == 'alipay') {

            $type['paytype'] = 1;

            db('order')->where($where)->update($type);


            $alipay = new Alipay();

            //异步回调地址
            $url = $this->url_translation_address('/index/payment/alipay_notify');

            $array = $alipay ->alipay(Config::get('company'), $money, $reoderSn, $url);


            if ($array) {
                return $this->response($array, 1, '成功');
            } else {

                return $this->response('', 0, '对不起请检查相关参数');
            }
        }


        if ($pay_type == 'wechat') {
            $type['paytype'] = 2;


        }
    }

    /*
         * 支付宝支付回调修改订单状态
         */
    public function alipay_notify()
    {
        //原始订单号
        $out_trade_no = input('out_trade_no');
        //支付宝交易号
        $trade_no = input('trade_no');
        //交易状态
        $trade_status = input('trade_status');


        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

            $condition['ordersn'] = $out_trade_no;
            $data['status'] = 2;
            $data['third_ordersn'] = $trade_no;

            $result=db('order')->where($condition)->update($data);//修改订单状态,支付宝单号到数据库

            if($result){
                echo 'success';
            }else{
                echo 'fail';
            }

        }else{
            echo "fail";
        }



    }

    //相对地址转绝对地址
    protected function url_translation_address($url)
    {
        return $this->domain . $url;
    }
}