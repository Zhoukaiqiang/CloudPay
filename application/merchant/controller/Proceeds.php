<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/11
 * Time: 14:05
 */

namespace app\merchant\controller;


use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Proceeds extends Controller
{
    /**
     * 现金支付
     * @param Request $request
     * @throws \think\Exception
     */
    public function cash_income(Request $request)
    {
        $money=$request->param('cash_money');
        $merchant_id=session('merchant_id');
        $merchant_id=1;

        $resu=Db::name('total_merchant')->where('id',$merchant_id)
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
}