<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/13
 * Time: 11:33
 */

namespace app\merchant\controller;


use app\merchant\model\MemberRecharge;
use app\merchant\model\MerchantMember;
use Endroid\QrCode\QrCode;

use app\agent\model\MerchantIncom;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\ShopActiveRecharge;
use think\Controller;
use think\Request;

class Scancode extends Commonality
{
    protected  $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/";
    protected  $decision=1;

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
    public function publics($data)
    {
        //获取商户id
        $shop = MerchantShop::alias('a')
            ->field('b.key,b.orgNo,b.mercId,b.rec')
            ->join('merchant_incom b','b.merchant_id=a.merchant_id')
            ->where('a.id', $data[ 'shop_id' ])
            ->find();

        $resu = $shop->toArray();
        $rec=json_decode($resu['rec']);
        /**设备号*/
        $data['trmNo']=$rec[0]->trmNo;
        $data['opSys'] = '3';
        $data['characterSet'] = '00';
        $data['signType'] = 'MD5';
        $data['version'] = 'V1.0.0';
        $data['txnTime'] = date("Ymdhis");
        //商户号
        list($usec, $sec) = explode(" ", microtime());
        $times = str_replace('.', '', $usec + $sec);
        $data[ 'tradeNo' ] = $data[ 'txnTime' ] . $times;
        //删除rec值
        unset($resu['rec']);

        //数组合并
        $data = array_merge($data, $resu);
        //得到当前请求的签名，用于和返回参数验证


        $data['signValue'] = sign_ature(0000, $data,$data['key']);

        return $data;
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
    public function lord_esau($data)
    {

        $url = $this->url . "sdkBarcodePay.json";
//        return json_encode($url);
        //获取返回结果 */
        $res = curl_request($url, true, $data, true);
//        return $res;
//        $res=urldecode($res);

        // json转成数组
        $par = json_decode($res, true);
//        halt($par);
//        $return_sign = sign_ature(1111, $par,$data['key']);
        //result 交易接查  为空交易失败  S - 交易成功 F - 交易失败 A - 等待授权  Z - 交易未知
            if ($par[ 'result' ] == "S") {

                //判断状态码
                if ($par[ 'returnCode' ] == '000000') {
//                    if ($par['signValue'] == $return_sign) {
                    return $this->storage($data,$par);



//                    } else {
//                        return_msg(400, 'error', urldecode($par[ 'message' ]));
//                    }
                } else {

                    return_msg(500, 'error', urldecode($par[ 'message' ]));
                }

            } else {
//                if($par[ 'result' ] == "A" || $par[ 'result' ] == "Z"){
                $sultr = ['shop_id' => $data[ 'shop_id' ], 'qryNo' => $par[ 'logNo' ]];
                $sultr = $this->publics($sultr);
                $sultr = $this->orderInquiry($sultr);
                return_msg(600, 'error', urldecode($sultr[ 'message' ]));
//                }


            }

    }

    /**
     * 返回的信息入库
     * @param $data
     * @param $par
     */
    public function storage($data,$par)
    {
        if(!empty($this->merchant_id)){
            $data['user_id']="";
        }else{
            $data['user_id']=$this->user_id;
        }
        $par['amount']=$par['amount']/100;
        $par['total_amount']=$par['total_amount']/100;
        if($data['payChannel']=="WXPAY"){
            $data['payChannel']="wxpay";
        }
        if($data['payChannel']=="ALIPAY"){
            $data['payChannel']="alipay";
        }
        //判断是否是会员充值
        if (array_key_exists('member_id',$data)) {
            if (!$this->member_recharge($data, $par)) {

                return_msg(200, 'success', '会员充值出现错误，充值已成功');
            }

        } else {
            //收银员id   如果是商户存-1
            $person_info_id=$this->id;
            if($this->role==-1){
                $person_info_id=-1;
            }
            if(array_key_exists('order_id',$data)){
                $arr=Order::where('id', $data[ 'order_id' ])->update(['person_info_id'=>$person_info_id,'pay_time' => time(), 'status' =>1, 'logNo' => $par[ 'logNo' ],'order_no'=>$par['orderNo'],'received_money'=>$par['amount'],'order_money'=>$par['total_amount'],'tradeNo'=>$par['tradeNo'],'merchant_id'=>$data['merchant_id'],'user_id'=>$data['user_id'],'order_number'=>generate_order_no()]);
                //订单是否更新成功
                if(!$arr){
                    return_msg(400,'error','订单付款出现错误，付款已成功');
                }
            }else{
                $arr=Order::create(['person_info_id'=>$person_info_id,'order_number'=>generate_order_no(),'create_time'=>time(),'pay_type'=>$data['payChannel'],'shop_id'=>$data['shop_id'],'pay_time' => time(), 'status' =>1, 'logNo' => $par[ 'logNo' ],'order_no'=>$par['orderNo'],'received_money'=>$par['amount'],'order_money'=>$par['total_amount'],'tradeNo'=>$par['tradeNo'],'merchant_id'=>$data['merchant_id'],'user_id'=>$data['user_id']]);
                //订单是否创建成功
                if(!$arr){
                    return_msg(400,'error','付款出现错误，付款已成功');
                }

            }

        }
        return_msg(200, 'success', urldecode($par[ 'message' ]));

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
    public  function client_lordesau($data)
    {
        $url = $this->url."sdkBarcodePosPay.json";

        //获取返回结果 */
        $par = curl_request($url, true, $data, true);
//return $par;
        // json转成数组

        $par = json_decode($par, true);



        $par['message']=urldecode($par['message']);

        $return_sign = sign_ature(1111, $par,$data['key']);


        //result 交易接查  为空交易失败  S - 交易成功 F - 交易失败 A - 等待授权  Z - 交易未知
//        if ($par[ 'Result' ] != 'Z' || $par[ 'Result' ] != 'A') {
            if ($par[ 'result' ] == 'S') {

                //判断状态码
                if ($par[ 'returnCode' ] == '000000') {

//                    if ($par['signValue']==$return_sign) {

                        Order::where('id', $data[ 'order_id' ])->update(['payCode' => $par[ 'payCode' ], 'logNo' => $par[ 'logNo' ], 'order_no' => $par['orderNo'],'tradeNo' => $par[ 'tradeNo' ]]);
                        //返回二维码地址
                         return $this->qrcode($par['payCode']);

//                    } else {
////                        return 5;
//                        return_msg(400, 'errors', urldecode($par[ 'message' ]));
//
//                    }

                } else {
                    return_msg(300, 'error', urldecode($par[ 'message' ]));
                }
            } else {
                if ($par[ 'Result' ] == 'Z' || $par[ 'Result' ] == 'A') {
                $sultr = ['shop_id' => $data[ 'shop_id' ], 'qryNo' => $par[ 'logNo' ]];
                $sultr = $this->publics($sultr);
                $sultr = $this->orderInquiry($sultr);
                return_msg(600, 'error', urldecode($sultr[ 'message' ]));
                }

                return_msg(500, 'error', urldecode($par[ 'message' ]));
            }




    }

    /**
     * 客户扫码  异步返回接口
     * @param Request $request
     */
    public function message_return(Request $request)
    {

        $res=$request->post();

        //异步接口返回成功
        if($res['TxnStatus']==1){
            $url = Scancode::$url . "sdkQryBarcodePay.json";

            //查询订单数据
            $data=Order::alias('a')
                ->field('a.txnTime,a.trmTyp,a.trmNo,b.mercId')
                ->join('merchant_incom b','b.merchant_id=a.merchant_id')
                ->where('a.tradeNo',$res['tradeNo'])
                ->select();
            $data=$data[0];
            //公共接口请求参数
            $data[ 'opSys' ] = 3;
            $data[ 'characterSet' ] = 00;
            $data[ 'signType' ] = 'MD5';
            $data[ 'version' ] = 'V1.0.0';
            $data['qryNo']=$res['TxnLogId'];
            $data[ 'signValue' ] = sign_ature(0000, $data);
            //订单查询
            $utle=$this->orderInquiry($data);
            if($utle['Result']=='S' && $utle['returnCode']==0){
                $result=['RspCode'=>000000,'RspDes'=>''];
                return json_encode($result);
            }else{
                Order::where('tradeNo', $data[ 'TxnLogId' ])->update(['status' => 5]);

                $result=['RspCode'=>000002,'RspDes'=>'查询失败'];
                return $result;
            }



        }else{

        }



    }


    /**
     * 订单查询
     * @param $resu
     * qryNo 1 查询流水   可根据logNo 、orderNo 、tradeNo 的值做查询
     */
    public function orderInquiry($data)
    {

        //公共参数
        $url=$this->url."sdkQryBarcodePay.json";

        //获取返回结果 */
        $res = curl_request($url, true, $data, true);

        // json转成数组
        $par = json_decode($res, true);
        $return_sign=sign_ature(1111, $par);
        //result 交易接查  为空交易失败  S - 交易成功 F - 交易失败 A - 等待授权  Z - 交易未知
//        if($par['result']!='Z' || $par['result']!='A') {
            if ($par[ 'result' ] == 'S') {

                //判断状态码
                if ($par[ 'returnCode' ] == '000000') {
//                    if ($par[ 'signValue' ] == $return_sign) {

                        Order::where('tradeNo', $data[ 'TxnLogId' ])->update(['pay_time' => time(), 'status' => 1, 'LogNo' => $par[ 'LogNo' ],'order_no'=>$par['orderNo'],'selOrderNo'=>$par['selOrderNo'],'payChannel'=>$par['payChannel']]);

                        return $par;
//                    } else {
//                        return $par;
//                    }
                } else {

                    return $par;
                }

            } else {
                return $par;
            }
//        }else{
//            //result 返回A，Z，需发起查询判断具体交易状态
//        }
    }

    /**
     * 会员充值  数据库更新
     * @param $data
     * @param $par
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  member_recharge($data,$par)
    {
        //获取当前商户的当前有效时间的优惠活动   	recharge_money	充值金额   give_money 赠送金额
        $aumen=ShopActiveRecharge::where(['merchant_id'=>$this->id,'start_time'=>['<',time()],'end_time'=>['>',time()]])->field('recharge_money,give_money')->order('recharge_money asc')->select();
        $money=0;
//        return_msg($aumen);
        foreach ($aumen as $k=>$v){
            if($v['recharge_money']<=$data['amount']){
                $money=$v['give_money'];
            }
        }

        //订单号
        $order_no=generate_order_no();
        //创建会员充值订单
        $resuoo=MemberRecharge::insert(['recharge_time' => time(),'member_id'=>$data['member_id'],'order_money'=>$data['total_amount'],'shop_id'=>$data['shop_id'],'order_no'=>$par['orderNo'],'pay_type'=>$data['payChannel'],'merchant_id'=>$data['merchant_id'],'amount'=>$par['amount'], 'status' => 1, 'logNo' => $par[ 'logNo' ],'user_id'=>$data['user_id'],'order_number'=>generate_order_no()]);
        if (!$resuoo){
            return false;
        }
        $money=$data['amount']+$money;
        //更新会员余额
        $member=MerchantMember::where('id',$data['member_id'])->update(['recharge_money'=>$data['amount'],'recharge_time'=>time(),'money'=>['inc',$money]]);
        if (!$member){
            return false;
        }else{
            return true;
        }
    }

}