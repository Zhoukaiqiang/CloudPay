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
            $id=$rulew->merchant_id;
        }


        $resu=Db::name('total_merchant')->where('id',$id)
            ->setInc('cash_money',$money);



        if($resu){
            return_msg(200,'success','现金收款成功');
        }else{
            return_msg(400,'error','现金收款失败');
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
       $resu=Db::name('merchant_member')->where('id',$member_id)
           ->update(['money'=>['dec',$money],'consumption_time'=>time()]);
        if($resu){
            return_msg(200,'success','会员支付成功');
        }else{
            return_msg(400,'error','会员支付失败');
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
    public function shop_Lordesau(Request $request)
    {
        $data=$request->post();
        //oprId  操作员号暂时没有
//        $data['payChannel']="WXPAY";
//        $data['payChannel']=
        //公共参数
//
       $data= $this->publics($data);
       //调用接口
//        return json_encode($data);
       return $this->lord_esau($data);

    }

    /**
     * 客户主扫
     * @param Request $request
     */

    public function clientLordesau(Request $request)
    {
        $data=$request->post();
        //oprId  操作员号暂时没有

        //公共参数
        $data= $this->publics($data);
        //调用接口
        return $this->client_lordesau($data);

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