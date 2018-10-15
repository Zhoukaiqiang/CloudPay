<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/13
 * Time: 11:33
 */

namespace app\merchant\controller;


use app\merchant\model\Order;
use think\Controller;

class Scancode extends Controller
{
    protected static $url="http://139.196.77.69:8280/adpweb/ehpspos3/";

    /**
     * 公共请求参数头
     * opSys  1  操作系统   0 ：ANDROID sdk  1 ：IOS sdk  2 ：windows sdk  3:直连
     * characterSet  1 字符集   默认00- GBK
     * orgNo  1  机构号
     * mercId  1  商户号
     * trmNo   1 设备号
     * oprId  0  操作员号
     * trmTyp  0  设备类型   P - 智能POS  A -   app 扫码   C - PC端   T - 台牌扫码
     * tradeNo  1  商户单号  在商户端不重复
     * txnTime  1  设备端交易时间  如：20170527153245
     * signType  1 签名方式  MD5
     * signValue 1 签名域，详见“加签说明”
     * addField  0  附加字段  版本号需上送V 1.0.1
     *  version  1  版本号  默认V1.0.0
     *
     *
     * 公共返回参数头
     * tradeNo 1  商户单号 在商户端不重复
     * returnCode 1 返回码
     * sysTime 1  系统交易时间
     * message   1  返回信息
     * mercId   1  商户号
     * signValue  1  签名域，详见“数字签名”
     * addField  0  附加字段
     */
    public static function publics($data)
    {
        /**商户单号*/
        $url=Scancode::$url."sdkBarcodePay.json";
        $data['opSys']=3;
        $data['characterSet']=00;
        $data['signType']='MD5';
        $data['version']='V1.0.0';
        //商户号
        list($usec, $sec) = explode(" ", microtime());
        $times=str_replace('.','',$usec + $sec);
        $timese=date('YmdHis',time());
        $code=$timese.$times;
        $data['tradeNo']=$code;
        //trmTyp  0  设备类型   P - 智能POS  A -   app 扫码   C - PC端   T - 台牌扫码
        $data['trmTyp']='P';
        $data['txnTime']=date("Ymdhis");
        $shop=MerchantShop::where('id',$data['shop_id'])->field('merchant_id')->find();
        $resu=MerchantIncom::where('merchant_id',$shop['merchant_id'])->field('orgNo,mercId')->find();
        $resu=$resu->toArray();
        //数组合并
        $data=array_merge($data,$resu);
    }

    /**
     * 扫码支付-商户主扫
     * @param $data
     * 请求参数
     * Amount   1 实付金额
     * total_amount 1 订单总金额
     * authCode  1  扫码支付授权码，设备读取用户微信或支付宝中的条码或者二维码信息
     * payChannel  1  支付渠道  附录3.2
     * Subject   0  订单标题
     * selOrderNo  0 订单号  订货订单号
     * goods_tag  0 订单优惠说明
     * Attach  0  附加数据
     *
     * 返回参数
     * LogNo 1 系统流水号   平台系统流水号，可用于订单查询接口查询结果
     * Result  1  交易结查  S - 交易成功  F - 交易失败  A - 等待授权   Z - 交易未知
     * orderNo 0 支付渠道订单号   交易成功（result为S ）返回的与用户支付订单中条码一致，可用于退货；
     * Amount  0  实付金额
     * total_amount 0 订单总金额
     * Subject   0  订单标题
     * selOrderNo  0  订单号  订货订单号，对应异步通知接口“消息通道”中的ChannelId字段
     * goodsTag 0 订单优惠说明
     * Attach  0    附加数据
     * openId  0  用户标识
     */
    public static function lord_esau($data)
    {
        $url=Scancode::$url."sdkBarcodePay.json";
        $data['opSys']=3;
        $data['characterSet']=00;
        $data['signType']='MD5';
        $data['version']='V1.0.0';
        //trmTyp  0  设备类型   P - 智能POS  A -   app 扫码   C - PC端   T - 台牌扫码
        $data['trmTyp']='P';
        //得到当前请求的签名，用于和返回参数验证
        $data['signValue'] = sign_ature(0000, $data);
        //获取返回结果 */
        $res = curl_request($url, true, $data, true);

        // json转成数组
        $par = json_decode($res, true);
        $return_sign=sign_ature(1111, $par);
        //result 交易接查  为空交易失败  S - 交易成功 F - 交易失败 A - 等待授权  Z - 交易未知
        if($par['Result']!='Z' || $par['Result']!='A') {
            if ($par[ 'Result' ] == 'S') {

                //判断状态码
                if ($par[ 'returnCode' ] == '000000') {
                    if ($par[ 'signValue' ] == $return_sign) {
                        $time = time();
                        Order::where('id', $data[ 'order_id' ])->update(['pay_time' => $time, 'status' => 1, 'LogNo' => $par[ 'LogNo' ]]);
                return_msg(200, 'success', $par[ 'repMsg' ]);
            } else {
                        return_msg(400, 'error', $par[ 'repMsg' ]);
                    }
                } else {

                    return_msg(500, 'error', $par[ 'repMsg' ]);
                }

            } else {
                return_msg(600, 'error', $par[ 'repMsg' ]);

            }
        }else{
            //result 返回A，Z，需发起查询判断具体交易状态
        }

    }

    /**
     *扫码支付- 客户主扫（sdkBarcodePosPay ）
     *    * Amount   1 实付金额
     * total_amount 1 订单总金额
     * payChannel  1  支付渠道  支付宝 ALIPAY  微信 WXPAY   银联 YLPAY
     * Subject   0  订单标题
     * selOrderNo  0 订单号  订货订单号
     * goods_tag  0 订单优惠说明
     * Attach  0  附加数据
     */
    public function client_Lordesau($data)
    {
        $url=Scancode::$url."sdkBarcodePosPay.json";
        $data['opSys']=3;
        $data['characterSet']=00;
        $data['signType']='MD5';
        $data['version']='V1.0.0';
        //trmTyp  0  设备类型   P - 智能POS  A -   app 扫码   C - PC端   T - 台牌扫码
        $data['trmTyp']='P';
        //得到当前请求的签名，用于和返回参数验证
        $data['signValue'] = sign_ature(0000, $data);
        //获取返回结果 */
        $res = curl_request($url, true, $data, true);

        // json转成数组
        $par = json_decode($res, true);
        $return_sign=sign_ature(1111, $par);
        //result 交易接查  为空交易失败  S - 交易成功 F - 交易失败 A - 等待授权  Z - 交易未知
        if($par['Result']!='Z' || $par['Result']!='A') {
            if ($par[ 'Result' ] == 'S') {

                //判断状态码
                if ($par[ 'returnCode' ] == '000000') {
                    if ($par[ 'signValue' ] == $return_sign) {
                        $time = time();
                        Order::where('id', $data[ 'order_id' ])->update(['pay_time' => $time, 'status' => 1, 'LogNo' => $par[ 'LogNo' ]]);
                        return_msg(200, 'success', $par[ 'repMsg' ]);
                    } else {
                        return_msg(400, 'error', $par[ 'repMsg' ]);
                    }
                } else {

                    return_msg(500, 'error', $par[ 'repMsg' ]);
                }

            } else {
                return_msg(600, 'error', $par[ 'repMsg' ]);

            }
        }else{
            //result 返回A，Z，需发起查询判断具体交易状态
        }
    }

}