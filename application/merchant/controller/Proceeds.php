<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/11
 * Time: 14:05
 */

namespace app\merchant\controller;


use app\agent\model\MerchantIncom;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Proceeds extends Scancode
{


    /**
     * 现金支付
     * @param Request $request
     * @throws \think\Exception
     */
    public function cash_income(Request $request)
    {
        $money=$request->param('cash_money');
        $id=$this->id;
        if($this->role==1 ||$this->role==2 || $this->role==3){
            $rulew=MerchantUser::where('id',$id)->field('merchant_id')->find();
            $merchant_id=$rulew->merchant_id;
        }else{
            $merchant_id=$this->id;
        }

        $arr=[
            'status'=>1,
            'order_money'=>$money,
            'received_money'=>$money,
            'create_time'=>time(),
            'pay_time'=>time(),
            'pay_type'=>'cash',
            'order_number'=>generate_order_no(),
            'merchant_id'=>$merchant_id,
            'person_info_id'=>$this->id,
            'user_id'=>$this->id,
        ];
        $resu=Db::name('order')->insert($arr);
        if($resu){
            return_msg(200,'现金收款成功');
        }else{
            return_msg(400,'现金收款失败');
        }
    }

    /**
     * 会员支付
     * @param Request $request
     * @throws \think\Exception
     */
    public function member_income(Request $request)
    {

        //支付金额
       $money= $request->param('money');
       //会员id
       $member_id= $request->param('member_id');

       $result=Db::name('merchant_member')->where('id',$member_id)->field('money')->find();
       if($result['money']-$money <= 0){
           return_msg('400','余额不足');
       }
       $resu=Db::name('merchant_member')->where('id',$member_id)
           ->update(['money'=>['dec',$money],'consumption_time'=>time(),'consump_number'=>['inc',1]]);
        if($resu){
            //获取商户id
            $merchant=Db::name('merchant_member')->where('id',$member_id)->field('merchant_id')->find();
            //生成订单
            $arr=[
                'status'=>1,
                'order_money'=>$money,
                'received_money'=>$money,
                'create_time'=>time(),
                'pay_time'=>time(),
                'pay_type'=>'cash',
                'order_number'=>generate_order_no(),
                'merchant_id'=>$merchant['merchant_id'],
                'person_info_id'=>$this->id,
                'user_id'=>$this->id,
                'member_id'=>$member_id,
            ];
            Db::name('order')->insert($arr);
            return_msg(200,'会员支付成功');
        }else{
            return_msg(400,'会员支付失败');
        }
    }

    /**
     * 银联收款
     * msg_tp 1  报文类型   0200
     * pay_tp  1 支付方式 0-银行卡 1-扫码 11-微信支付 12-支付宝支付 13-银联二维码支付
     * proc_tp 1  交易类型  00－消费类
     * proc_cd 1 交易处理码 000000消费 200000消费撤销 300000预授权 330000预授权完成 400000预授权撤销  440000预授权完成撤销  500000联机退货 660000扫码支付 680000扫码撤销 700000扫码补单900000结算
     * systraceno 0 凭证号 消费撤销，预授权完成撤销时，传入做撤销
     *  amt  1   交易金额
     * order_no   0 订单号
     *  batchbillno  0 批次流水号
     * trans_cardno  0 交易卡号   预授权撤销，预授权完成时使用
     * expate  0 卡号有效期    预授权撤销，预授权完成时使用，非必填，
     * auth_code 0 交易授权码    预授权撤销，预授权完成时使用，非必填
     * sysolddate   0    原交易日期    该字段在进行预授权撤销，预授权完成时可作为请求数据
     * print_info  0  打印信息
     * appid        调用者应用包名
     * time_stamp   交易时间戳
     * reason      失败原因
     * txndetail  交易详情
     * cardtype   银行卡类型   00:借记卡 01:贷记卡 02:准贷记卡 03:预付卡  04:其他
     * sysoldtraceno   原交易凭证号
     * sysoldreferno   原交易参考号
     * operid     操作员
         *
     */
    public function index()
    {

    }

    /**
     *扫码支付- 商户主扫
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * Amount  实付金额
     * total_amount 1 订单总金额
     * authCode  1  扫码支付授权码，设备读取用户微信或支付宝中的条码或者二维码信息
     * payChannel  1  支付渠道  支付宝 ALIPAY  微信 WXPAY   银联 YLPAY
     * tradeNo   商户单号  在商户端不重复    ??????
     */
    public function shop_lordesau(Request $request)
    {
        $data=$request->post();
        $data['amount']=$data['amount']*100;
        $data['total_amount']=$data['total_amount']*100;
        //如果是商户收款
        if($this->role==-1){
            $shop=MerchantShop::where('merchant_id',$this->id)->field('id')->find();
            $data['shop_id']=$shop->id;
        }
        //支付渠道
        $data['payChannel']=$this->isplay($data['authCode']);
        //公共参数
        $shop = MerchantShop::alias('a')
            ->field('b.key,b.orgNo,b.mercId,b.rec,a.merchant_id')
            ->join('merchant_incom b','b.merchant_id=a.merchant_id')
            ->where('a.id', $data[ 'shop_id' ])
            ->find();
//       $data= $this->publics($data);
//       halt($shop);
//       unset($data['shop_id']);
//       unset($data['key']);
        $info=request_head($shop,$data);
//        unset($info['shop_id']);
        $info['merchant_id']=$shop['merchant_id'];
//        halt($info);
       //调用星_pos接口
       return $this->lord_esau($info);

    }

    /**
     * 支付渠道
     * @param $data
     */
    public  function isplay($authCode)
    {
        $wxpay=[10,11,12,13,14,15];
        $zfbpay=[25,26,27,28,29,30];
        //判断微信支付 支付宝
        if(in_array(substr($authCode,0,2),$wxpay)){
            return "WXPAY";
        }elseif(in_array(substr($authCode,0,2),$zfbpay)){
            return "ALIPAY";
        }
    }

    /**
     * 客户主扫
     * @param Request $request
     * @deprecated 弃用
     */

    public function clientLordesau(Request $request)
    {
        $data = $request->post();
        /** 生成收款维码---识别客户是wx / ali  跳转到指定URL （请求星POS） 获取交易信息*/
        echo $this->IsWeixinOrAlipay();

    }


    /**
     * 客户主扫  查询订单详情
     *
     */
    public function order_inquiry(Request $request)
    {
        $logNo = $request->param('logNo');
        $resu = Order::where('lonNo', $logNo)->field('status')->find();
        //是否支付成功
        if ($resu->status == 1) {
            return_msg(200, 'success', '支付成功');
        } else{
            return_msg(200, 'success', '支付失败');
        }

    }
}