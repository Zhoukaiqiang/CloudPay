<?php
namespace app\admin\controller;

use think\Controller;
use think\Request;
class Payment extends Controller {
    //测试服务器
    private $domain = 'http://local.cloud.com';
    /*public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }*/

    public function payOrder()
    {

        //获取订单号
//        $where['id'] = input('post.orderid');
//        //查询订单信息
//        $order_info = db('order')->where($where)->find();
//        $reoderSn = $order_info['ordersn'];
        $reoderSn = 123456;
        //获取支付方式
//        $pay_type = input('post.paytype');//微信支付 或者支付宝支付
        $pay_type = 'alipay';//微信支付 或者支付宝支付
        //获取支付金额
        $money = 0.01;//$order_info['realprice'];
        //判断支付方式

        if ($pay_type == 'alipay') {

//            $type['paytype'] = 1;

//            db('order')->where($where)->update($type);


            $alipay = new Alipay();

            //异步回调地址
            $url = $this->url_translation_address('/admin/payment/alipay_notify');
            $array = $alipay ->alipay('娃哈哈', $money, $reoderSn, $url);

            jsonReturn(1, 2101003, ['payinfo'=>$array], '操作成功');
           /* if ($array) {
                jsonReturn();
                $result = array('code' => 1, 'msg' => '成功', 'data' => $array);
                return json($result);
            } else {
                $result = array('code' => 0, 'msg' => '对不起请检查相关参数!');
                return json($result);
            }*/
        }


//        if ($pay_type == 'wechat') {
//            $type['paytype'] = 2;
//
//
//        }
    }

    /*
         * 支付宝支付回调修改订单状态
         */
    public function alipay_notify()
    {
        /*echo 1;die;
        dump($_POST);die;
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
        }*/
        $request    = input('post.');

        //写入文件做日志 调试用
//        $log = "<br />\r\n\r\n".'==================='."\r\n".date("Y-m-d H:i:s")."\r\n".json_encode($request);
//        @file_put_contents('upload/alipay.html', $log, FILE_APPEND);

        $signType = $request['sign_type'];
        $alipay = new AliPay();
        $flag = $alipay->rsaCheck($request, $signType);

        if ($flag) {
            //支付成功:TRADE_SUCCESS   交易完成：TRADE_FINISHED
            if ($request['trade_status'] == 'TRADE_SUCCESS' || $request['trade_status'] == 'TRADE_FINISHED') {
                //这里根据项目需求来写你的操作 如更新订单状态等信息 更新成功返回'success'即可
                $object =  json_decode(($request['fund_bill_list']),true);
                $trade_type     =   $object[0]['fundChannel'];
                $data    =    [
                    'pay_status'        =>   1,
                    'pay_type'             =>   1,
                    'trade_type'        =>   $trade_type,
                    'pay_time'          =>   strtotime($request['gmt_payment'])
                ];
                $buyer_pay_amount = $request['buyer_pay_amount'];
                $out_trade_no   =   $request['out_trade_no'];
                $saveorder      =   model('orders')->successPay($out_trade_no,$buyer_pay_amount,$data);
                if ($saveorder==1) {
                    exit('success'); //成功处理后必须输出这个字符串给支付宝
                } else {
                    exit('fail');
                }
            } else {
                exit('fail');
            }
        } else {
            exit('fail');
        }


    }

    //相对地址转绝对地址
    protected function url_translation_address($url)
    {
        return $this->domain . $url;
    }
}