<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantMemberCart;
use app\merchant\model\Order;
use think\Controller;
use think\Request;

class Member extends Controller
{
    /**
     * 显示会员列表
     *
     * @return \think\Response
     */
    public function member_list(Request $request)
    {
        //获取商户id
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
            ->where('merchant_id',$merchant_id)
            ->whereTime('register_time','>','yesterday')
            ->select();
        return_msg(200,'success',$data);
    }

    /**
     * 会员详情
     *
     * @return \think\Response
     */
    public function member_detail(Request $request)
    {
        //获取会员id
        $id=$request->param('id');
        $data=MerchantMember::field('id,member_head,recharge_money,consumption_money,member_name,member_phone,money,consume_number,register_time')
            ->where('id',$id)
            ->find();
        return_msg(200,'success',$data);
    }

    /**
     * 查看当前会员所有订单
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function member_order(Request $request)
    {
        //获取会员id
        $member_id=$request->param('id');
        $merchant_id=session('merchant_id');
        $merchant_id=1;//测试
        $data=Order::field('id,order_money,pay_type,status,create_time')
            ->where(['member_id'=>$member_id,'merchant_id'=>$merchant_id])
            ->whereTime('pay_time','month')
            ->select();
        $data['member_id']=$member_id;
        return_msg(200,'success',$data);
    }

    /**
     * 查看会员订单详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function order_detail(Request $request)
    {
        //获取会员id
        $member_id=$request->param('member_id');
        $data=Order::field('received_money,order_money,discount,received_store,cashier,pay_time,pay_type,order_remark,order_number,status,authorize_number,prove_number,give_money')
            ->where('member_id',$member_id)
            ->find();
        return_msg(200,'success',$data);
    }

    /**
     * 会员充值
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 会员数据
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
    public function member_data(Request $request)
    {
        //获取商户id
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        //今日充值并为会员
        $data[]['recharge']=Order::whereTime('pay_time','today')
            ->where(['status'=>3,'merchant_id'=>$merchant_id,'member_id'=>['>',0]])
            ->sum('received_money');
        //今日赠送
        $data[]['give']=Order::whereTime('pay_time','today')
            ->where(['merchant_id'=>$merchant_id,'member_id'=>['>',0]])
            ->sum('give_money');
        //今日消费
        $data[]['consume']=Order::whereTime('pay_time','today')
            ->where(['status'=>1,'merchant_id'=>$merchant_id,'member_id'=>['>',0]])
            ->sum('received_money');
        //今日新增
        $data[]['new_count']=MerchantMember::whereTime('register_time','today')
            ->where('merchant_id',$merchant_id)
            ->count();
        return_msg(200,'success',$data);
    }

    /**
     * 设置会员卡
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function set_member_cart(Request $request)
    {
        $data=$request->post();
        $result=MerchantMemberCart::where('id',1)->update($data,true);
        if($result){
            return_msg(200,'success','设置成功');
        }else{
            return_msg(400,'failure','设置失败');
        }
    }
}
