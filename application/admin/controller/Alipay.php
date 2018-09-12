<?php
namespace app\admin\controller;
//use think\Config;
use think\Controller;
class Alipay extends Controller
{

    /*
     * 支付宝支付
     * $body            名称
     * $total_amount    价格
     * $product_code    订单号
     * $notify_url      异步回调地址
     */
    public function alipay($body, $total_amount, $product_code, $notify_url)
    {

        /**
         * 调用支付宝接口。
         */

        import('.Alipay.aop.AopClient', '', '.php');
        import('.Alipay.aop.request.AlipayTradeAppPayRequest', '', '.php');

        $aop = new \AopClient();
        $aop->gatewayUrl            = config('alipay')['gatewayUrl'];
        $aop->appId                 = config('alipay')['app_id'];
        $aop->rsaPrivateKey         = config('alipay')['merchant_private_key'];
        $aop->format                = 'json';
        $aop->charset               = 'UTF-8';
        $aop->signType              = config('alipay')['sign_type'];
        $aop->alipayrsaPublicKey    = config('alipay')['alipay_public_key'];

        $request = new \AlipayTradeAppPayRequest();
        $arr['body']                = $body;
        $arr['subject']             = $body;
        $arr['out_trade_no']        = $product_code;
        $arr['timeout_express']     = '30m';
        $arr['total_amount']        = floatval($total_amount);
        $arr['product_code']        = 'QUICK_MSECURITY_PAY';

        $json = json_encode($arr);
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($json);

        $response = $aop->sdkExecute($request);
        return $response;

    }

//    public function notify(){
//        $data = $_POST;
//        $aop = new \AopClient;
//        $aop->alipayrsaPublicKey = config('alipay')['alipay_public_key'];
//        $flag = $aop->rsaCheckV1($data, NULL, "RSA2");
//        return $flag;
//    }
    /**
     * 异步通知验签
     *
     * @param string $params 参数
     * @param string $signType 签名类型：默认RSA
     * @return bool 是否通过
     */
    public function rsaCheck($params, $signType)
    {
        return $this->aop->rsaCheckV1($params, NULL, $signType);
    }

}