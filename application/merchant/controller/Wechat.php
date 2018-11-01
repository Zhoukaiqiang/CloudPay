<?php

namespace app\merchant\controller;

use app\admin\model\MerchantIncom;
use app\merchant\model\MemberExclusive;
use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\ShopActiveExclusive;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Request;
use think\Session;

class Wechat extends Controller
{
    public $appid;
    public $secret;
    public $redirect_uri;

    public function _initialize()
    {
        parent::_initialize();
        $WxQuery = "pubSigQry";
        $WxPay = "pubSigPay";
        $this->appid = 'wx1aeeaac161a210df';//appid
        $this->secret = '0b1ddf2e988b7d05e4e775cc8bdfc831'; //secrect
        $this->redirect_uri = 'http://pay.hzyspay.com/index.php/merchant/wechat/back_url';//返回的域名网址
//        $this->appid="wx193727ba2313b0d8";
//        $this->secret="fe7c3669faec17d3e681c4c938af12a6";
//        $this->redirect_uri = 'http://47.92.212.66/index.php/merchant/wechat/back_url';//返回的域名网址

        $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/{$WxQuery}.json";
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_code()
    {
        $code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".urlencode($this->redirect_uri)."&response_type=code&scope=snsapi_base&state=202#wechat_redirect";

        header("Location:".$code);
    }

    /**
     * 回调地址
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function back_url(Request $request)
    {
//        echo 1;
        $code = $request->param("code");
//        halt($code);
        $state = $request->param("state");
        if (isset($code) || (int)$state == 202) {
//            halt(1);
            Session::set("code", $code);

            $userinfo = $this->get_user_info($code);

            Session::set('openid',$userinfo['openid']);
            return $userinfo;

        } else {
            return_msg(400, "fail");
        }
    }

    /**
     * 获取信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_info($code)
    {
        $access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->appid . "&secret=" . $this->secret . "&code=" . $code . "&grant_type=authorization_code";
        $access_token_json = $this->https_request($access_token_url);//自定义函数
        $access_token_array = json_decode($access_token_json, true);
//        halt($access_token_array);
        $access_token = $access_token_array['access_token'];
        $openid = $access_token_array['openid'];
        $userinfo_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $userinfo_json = $this->https_request($userinfo_url);
        $userinfo_array = json_decode($userinfo_json, true);
        return $userinfo_array;
    }

    /**
     * 发起请求
     */
    public function https_request($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    /**
     * 会员充值
     * 连贯操作 ->go_wxpay()
     */
    public function member_recharge($param = null)
    {
        /** 查询 -> 支付 */
//        $data=MerchantIncom::field('mercId,rec,key')->where('merchant_id',"87")->find();
        $param = [
            "orgNo" => "27573",
            "mercId" => "800332000002146",
            "trmNo" => "95445644",
            "txnTime" => (string)date("YmdHms", time()),
            "signType" => 'MD5',
            "version" => 'V1.0.0',

        ];
        $key = "0FF9606C39C2CCF1515E5CE108B506F0";

        $param["signValue"] = sign_ature(0000, $param, $key);

        $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/pubSigQry.json";
        $res = curl_request($url, true, $param, true);

        $res = json_decode(urldecode($res), true);
//        halt($res);
        /** 如果查询成功请求公众号支付否则返回错误信息 */
        if ($res["returnCode"] == "000000") {

            $this->go_wxpay($param);

        } else {
            return_msg(400, "fail", $res["message"]);
        }


    }

    /**
     * 调用星pos微信支付接口
     * @param null $param
     */
    protected function go_wxpay($param = null)
    {
        $request = \request();
        unset($param["signType"]);
        unset($param["signValue"]);
        $param["amount"] = (string)$request->param("amount");  //金额
        $param["total_amount"] = (string)$request->param("total_amount");  //总金额
        $param["signValue"] = sign_ature(0000, $param);

//        $param["appid"] = 'wx1aeeaac161a210df';
        $param["code"] = "021guDVz0d4yJf1nl2Yz0BcGVz0guDvq,";  //授权码 未使用的话，5分钟后过期
//        halt($param);
        $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/pubSigPay.json";

//        halt($param);
        $res = curl_request($url, true, $param, true);

        $res = json_decode(urldecode($res), true);
        halt($res);

            /** 如果查询成功请求公众号支付否则返回错误信息 */
        if ($res["returnCode"] == "000000") {

            /** [array] 成功存入数据库 $data */
            $data = [
                "order_no" => $res["orderNo"],
                "order_number" => $res["LogNo"],
                "order_money" => $res["total_amount"],
                "received_money" => $res["amount"],
                "pay_time" => $res["apiTimestamp"],
                "payer" => $res["PrepayId"],
                "order_remark" => $res["attach"],
                "pay_type"   => "wxpay",
            ];

            $query = Order::create($data);
            return_msg(200, $res);

        } else {
            return_msg(400, $res["message"]);
        }
    }

    /**
     *获取优惠券信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_exclusive()
    {
        if(Session::has("openid")){
            $openid=Session::get("openid");
        }else{
            $openid=$this->get_code();
            $openid=$openid['openid'];
        }
        $join=[
            ['cloud_shop_active_exclusive b','a.exclusive_id=b.id','left'],
            ['cloud_merchant_member c','a.member_id=c.id','left']
        ];
        $data=MemberExclusive::alias('a')
            ->join($join)
            ->field('a.SN,b.coupons_money,b.order_money,b.start_time,b.end_time')
            ->where(['b.end_time'=>['>',time()],'c.openid'=>$openid])
            ->select();
        check_data($data);
    }

    /**
     *验证token
     * @return bool
     */
    public function checkSignature()
    {
        // you must define TOKEN by yourself
        //判断TOKEN常量是否定义

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "weixin";
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr);
        $tmpStr = implode( '',$tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            echo $_GET['echostr'];
        }else{
            return false;
        }
    }

    /**
     *会员注册
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function member_register(Request $request)
    {
        if(Session::has("openid")){
            $openid=Session::get("openid");
        }else{
            $openid=$this->get_code();
            $openid=$openid['openid'];
        }
        $data=$request->post();
        $data['openid'] = $openid;

        //查询门店id
        $info = MerchantShop::field('id,merchant_id')->where('shop_name',$data['shop_name'])->find();

        $data['shop_id']=$info['id'];

        $data['merchant_id']=$info['merchant_id'];
        //验证
        check_params('member_register',$data,'MerchantValidate');
        if(isset($data['partner_phone'])){
            //查看是否有活动
            $res=ShopActiveExclusive::field('id,register_status')->where('merchant_id',$info['merchant_id'])->find();
            if($res['register_status'] == 1){
                //老会员推荐新会员注册
                //查询是否有该手机号
                $msg=MerchantMember::field('id,member_phone')->select();
                foreach($msg as $v){
                    if($v['member_phone'] == $data['partner_phone']){
                        //给当前手机号的会员排卷
                        $arr=[
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$res['id'],
                            'merchant_id'=>$info['merchant_id'],
                            'SN'=>getSN(),
                            'status'=>1,
                            'order_number'=>generate_order_no(),
                        ];

                        $result=MemberExclusive::insert($arr,true);

                    }else{
                        return_msg(400,'请填写正确的手机号');
                    }
                }
                //新会员注册
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }elseif($res['register_status']==2){
                //老会员推荐新会员注册和新会员注册都会派送优惠券
                //查询是否有该手机号
                $msg=MerchantMember::field('id,member_phone')->select();
                foreach($msg as $v){
                    if($v['member_phone'] == $data['partner_phone']){
                        //给当前手机号的会员排卷
                        $arr=[
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$res['id'],
                            'merchant_id'=>$info['merchant_id'],
                            'SN'=>getSN(),
                            'status'=>1,
                            'order_number'=>generate_order_no(),
                        ];
                        $result=MemberExclusive::insert($arr,true);

                    }else{
                        return_msg(400,'请填写正确的手机号');
                    }
                }

                //新会员注册
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){
                    $arr1=[
                        'member_id'=>$insert_id,
                        'exclusive_id'=>$res['id'],
                        'merchant_id'=>$info['merchant_id'],
                        'SN'=>getSN(),
                        'status'=>1,
                        'order_number'=>generate_order_no(),
                    ];
                    MemberExclusive::insert($arr1,true);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }
        }else{
            //查看是否有新会员注册活动
            $res=ShopActiveExclusive::field('id,register_status')->where('merchant_id',$info['merchant_id'])->find();

            if($res['register_status'] == 0){
                //新会员注册派卷
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){
                    $arr1=[
                        'member_id'=>$insert_id,
                        'exclusive_id'=>$res['id'],
                        'merchant_id'=>$info['merchant_id'],
                        'SN'=>getSN(),
                        'status'=>1,
                        'order_number'=>generate_order_no(),
                    ];
                    $re = MemberExclusive::insert($arr1,true);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }elseif($res['register_status'] == -1){
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }
        }
    }



}
