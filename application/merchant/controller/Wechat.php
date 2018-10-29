<?php

namespace app\merchant\controller;

use app\admin\model\MerchantIncom;
use app\merchant\model\Order;
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
//        $this->redirect_uri = 'http://47.92.212.66/index.php/merchant/wechat/back_url';//返回的域名网址
        $this->redirect_uri = 'http://api.hzyspay.com';//返回的域名网址

        $url = "http://gateway.starpos.com.cn/adpweb/ehpspos3/{$WxQuery}.json";
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOpenId()
    {
        $code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appid . "&redirect_uri=" . urlencode($this->redirect_uri) . "&response_type=code&scope=snsapi_base&state=202&#wechat_redirect";

        $openid = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code";
        $res = curl_request($code, true, '',true);

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
        $code = $request->param("code");
        $state = $request->param("state");
        if (isset($code) || (int)$state == 202) {
            Session::set("code", $code);

//            $userinfo = $this->get_user_info($code);

            return_msg(200, "success");

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
    public function https_request($url)//访问url返回结果
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return 'ERROR' . curl_error($curl);
        }
        curl_close($curl);
        return $data;
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

    public function get_access()
    {
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx193727ba2313b0d8&secret=fe7c3669faec17d3e681c4c938af12a6";
        $res=$this->https_request($url);
        $res=json_decode($res,true);
        return $res['access_token'];
    }

    public function check()
    {
        $access=$this->get_access();
        $url="https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=".$access;
        $res=curl_request($url,true,"",true);
        halt($res);
    }
    /**
     *发送模板消息
     */
    public function send_msg()
    {
        $access_token=$this->get_access();
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
//
        $arr=array(
            "touser"=>"obyu51Xfd1RnLfop4lZcVYWGnNm0",
            "template_id"=>"k41FDjmHugKZgPaTaz4mg4RHlS_NZ7QIbYR9qYvLSt8",
            "url"=>"www.baidu.com",
            "data"=>array(
                'name'=>array('value'=>'100万到手','color'=>'#173177'),
                'money'=>array('value'=>100,'color'=>'#173177'),
                'date'=>array('value'=>date("Y-m-d H:i:s"),"color"=>"#173177")
            ),
        );
//        $postjson=json_encode($arr);
        $res=curl_request($url,true,$arr,true);
        halt($res);
    }

}
