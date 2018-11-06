<?php

namespace app\merchant\controller;

use app\admin\model\MerchantIncom;
use app\merchant\model\MemberExclusive;
use app\merchant\model\MemberRecharge;
use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantMemberCard;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\ShopActiveExclusive;
use app\merchant\model\ShopActiveRecharge;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Request;
use think\Session;

class Wechat extends Controller
{
    protected $appid;
    protected $secret;
    protected $redirect_uri;

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

        $code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".urlencode($this->redirect_uri)."&response_type=code&scope=snsapi_userinfo&state=202#wechat_redirect";

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
//        halt($access_token_array['access_token']);
        $access_token = $access_token_array['access_token'];
//        halt($access_token);
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
            $this->get_code();
            $openid=Session::get("openid");
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
            $this->get_code();
            $openid=Session::get("openid");
        }
//        $openid=1;
//        echo 1;
        $data=$request->post();
        $data['openid'] = $openid;

        //查询门店id
        $info = MerchantShop::field('id,merchant_id')->where('shop_name',$data['shop_name'])->find();

        $data['shop_id']=$info['id'];

        $data['merchant_id']=$info['merchant_id'];
        //验证
        check_params('member_register',$data,'MerchantValidate');
        if(isset($data['partner_phone'])){
//            halt($data);
            //查看是否有活动
            $res=ShopActiveExclusive::field('id,register_status')->where('merchant_id',$info['merchant_id'])->find();
//            halt($res);
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
                //生成会员码
                $this->qrcode($insert_id);
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

                    //生成会员码
                    $this->qrcode($insert_id);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }elseif($res['register_status']==0){
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

                    //生成会员码
                    $this->qrcode($insert_id);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }else{
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){

                    //生成会员码
                    $this->qrcode($insert_id);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }
        }else{
//            echo 1;die;
            //查看是否有新会员注册活动
            $res=ShopActiveExclusive::field('id,register_status')->where('merchant_id',$info['merchant_id'])->find();
//            halt($res['register_status']);
//            echo $res['register_status'];die;
            if($res['register_status'] == 0 || $res['register_status']==2){
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

                    //生成会员码
                    $this->qrcode($insert_id);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }elseif($res['register_status'] == -1 || $res['register_status']==1){
//                echo 2;die;
                $insert_id=MerchantMember::insertGetId($data,true);
                if($insert_id){

                    //生成会员码
                    $this->qrcode($insert_id);
                    return_msg(200,'注册成功');
                }else{
                    return_msg(400,'注册失败');
                }
            }
        }
    }

    /**
     * Notes:生成二维码地址
     * User: guoyang
     * DATE: 2018/10/25
     * @param null $url 二维码地址
     * @param null $shop_id
     * @param null $name
     */
    public function qrcode($member_id)
    {

        $url="http://47.92.212.66/index.php/merchant/proceeds/member_income?member_id=$member_id";
        header("content-type:text/html;charset=utf-8");
//        Vendor('phpqrcode.phpqrcode');  //引入的phpqrcode类
        import('phpqrcode.phpqrcode', EXTEND_PATH,'.php');
        $path = "./uploads/QRcode/".date("Ymd").DS;//创建路径

//
        $time = time().'.png'; //创建文件名


        //$file_name = iconv("utf-8","gb2312",$time);

        $file_path = $path;

        if(!file_exists($file_path)){
            mkdir($file_path, 0777,true);//创建目录
        }
        $file_path = $file_path.$this->runningWater().'.png';//1.命名生成的二维码文件
        $level = 'L';  //3.纠错级别：L、M、Q、H
        $size = 4;//4.点的大小：1到10,用于手机端4就可以了
        ob_end_clean();//清空缓冲区
        //生成二维码-保存：
        \QRcode::png($url, $file_path, $level, $size);
        //保存二维码地址
        $file_path=substr($file_path,1);
        MerchantMember::where('id',$member_id)->update(['member_qrcode'=>$file_path]);
//        return_msg(200,$file_path);
    }

    /**
     * 流水号
     * @return string
     */
    public function runningWater()
    {
        list($usec, $sec) = explode(" ", microtime());
        $times=str_replace('.','',$usec + $sec);
        //當前時間
        return time().$times;
    }

    /**
     *获取二维码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_qrcode()
    {
        if(Session::has("openid")){
            $openid=Session::get("openid");
        }else{
            $this->get_code();
            $openid=Session::get("openid");
        }
//        $openid="oFQ1K09QyKH8qENIxUnhWIGUnPG8";
        //取出会员卡信息
        $data=MerchantMember::field('member_qrcode')->where('openid',$openid)->find();
        check_data($data);
    }
    /**
     *微信会员卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_card()
    {
        //获取用户appid
        if(Session::has("openid")){
            $openid=Session::get("openid");
        }else{
            $this->get_code();
            $openid=Session::get("openid");
        }

//        $openid=1;//测试
        //取出会员信息
        $data['list']=MerchantMember::field('id,merchant_id,shop_id,money,member_phone')->where('openid',$openid)->select();
        //取出会员活动
        foreach($data['list'] as &$v){
            $v['recharge']=ShopActiveRecharge::field('recharge_money,give_money')->where('merchant_id',$v['merchant_id'])->select();
            $v['member_card']=MerchantMemberCard::field('member_color,member_content,member_cart_name')->where('merchant_id',$v['merchant_id'])->find();
        }
        check_data($data);
    }

    /**
     *会员卡列表详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_card_list(Request $request)
    {
        $id=$request->param('id');
        $merchant_id=$request->param('merchant_id');

        $data['list']=MerchantMember::field('id,merchant_id,shop_id,money,member_phone')->where(['id'=>$id,'merchant_id'=>$merchant_id])->select();

        foreach($data['list'] as &$v){
            $v['recharge']=ShopActiveRecharge::field('recharge_money,give_money')->where('merchant_id',$v['merchant_id'])->select();
            $v['member_card']=MerchantMemberCard::field('member_color,member_content,member_cart_name')->where('merchant_id',$v['merchant_id'])->find();
        }
        check_data($data);
    }


    /* public function wx_member_recharge(Request $request)
     {
         //获取会员id
         $id = $request->param('id');
         //取出会员活动
         $data=MerchantMember::field('')
     }*/

    /**
     *充值记录
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_recharge_record(Request $request)
    {
        //获取商户id
        $merchant_id = $request->param('merchant_id');
        $data = MemberRecharge::alias('a')
            ->field('a.id,a.order_money,a.status,a.recharge_time,a.pay_type,b.member_head')
            ->join('cloud_merchant_member b','a.member_id=b.id','left')
            ->where('a.merchant_id',$merchant_id)
            ->select();
        check_data($data);
    }

    /**
     *会员充值详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_recharge_detail(Request $request)
    {
        //获取充值记录id
        $id = $request->param('id');
        $data = MemberRecharge::alias('a')
            ->field('a.amount,a.order_money,a.discount_amount,a.recharge_time,a.order_no,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.id',$id)
            ->find();
        check_data($data);
    }

    /**
     *会员消费记录
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_consump_record(Request $request)
    {
        //获取商户id
        $merchant_id = $request->param('merchant_id');
        //取出当前商户下会员消费信息
        $where=[
            'a.merchant_id'=>$merchant_id,
            'a.status'=>1,
            'a.member_id'=>['>',0]
        ];
        $data = Order::alias('a')
            ->field('a.id,a.order_money,a.status,a.pay_time,a.pay_type,b.member_head')
            ->join('cloud_merchant_member b','a.member_id=b.id','left')
            ->where($where)
            ->select();
        check_data($data);
    }

    /**
     *会员消费详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_member_consump_detail(Request $request)
    {
        //获取id
        $id = $request->param('id');
        $data=Order::alias('a')
            ->field('a.discount,a.order_money,a.received_money,a.pay_time,a.order_number,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.id',$id)
            ->find();
        check_data($data);
    }

    /**
     * 微信首页数据
     * @param $where
     * @param $where_join
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wx_merchant_index()
    {
        //交易额
        $data['total_amount'] = Order::where('status',1)->whereTime('pay_time','today')->sum('received_money');

        //交易笔数
        $data['count'] = Order::where('status',1)->whereTime('pay_time','today')->count();

        //支付宝交易
        $data['alipay'] = Order::where(['status'=>1,'pay_type'=>'alipay'])->whereTime('pay_time','today')->sum('received_money');

        //支付宝交易笔数
        $data['alipay_number']=Order::where(['status'=>1,'pay_type'=>'alipay'])->whereTime('pay_time','today')->count();

        //微信交易
        $data['wxpay']=Order::where(['status'=>1,'pay_type'=>'wxpay'])->whereTime('pay_time','today')->sum('received_money');

        //微信交易笔数
        $data['wxpay_number']=Order::where(['status'=>1,'pay_type'=>'wxpay'])->whereTime('pay_time','today')->count();

        //银联交易
        $data['etc']=Order::where(['status'=>1,'pay_type'=>'etc'])->whereTime('pay_time','today')->sum('received_money');

        //银联交易笔数
        $data['etc_number']=Order::where(['status'=>1,'pay_type'=>'etc'])->whereTime('pay_time','today')->count();

        //昨日活跃商户
        $data['active_merchant']=Order::where(['merchant_id'=>['>',0],'status'=>1])->whereTime('pay_time','>','yesterday')->count();

        //昨日新增商户
        $data['new_merchant']=TotalMerchant::where('review_status',2)->whereTime('opening_time','>','yesterday')->count();

        //营业中商户
        $data['open_merchant']=TotalMerchant::where('review_status',2)->count();

        //总商户
        $data['total_merchant']=TotalMerchant::count();

        //审核中商户
        $data['review_merchant']=TotalMerchant::where(['review_status'=>['<',2]])->count();

        check_data($data);
    }

}
