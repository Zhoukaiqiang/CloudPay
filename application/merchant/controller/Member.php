<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantMemberCart;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use app\merchant\model\ShopActiveRecharge;
use think\Controller;
use think\Request;

class Member extends Controller
{
    public $merchant_id;
    public $user_id;
    public function __construct()
    {
        $this->merchant_id=session('merchant_id') ? session('merchant_id') : null;
        $this->user_id=session('user_id') ? session('user_id') : 1;
    }
    /**
     * 显示会员列表
     *
     * @return \think\Response
     */
    public function member_list(Request $request)
    {
        if(!empty($this->merchant_id)){
            //显示当前商户下所有会员
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
                ->where('merchant_id',$this->merchant_id)
                ->whereTime('register_time','>','yesterday')
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            //显示当前门店下所有会员
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
                ->where('shop_id',$info['shop_id'])
                ->whereTime('register_time','>','yesterday')
                ->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 会员搜索
     *
     * @return \think\Response
     */
    public function search()
    {
        $search=request()->param('search');
        if(is_numeric($search)){
            //电话搜索
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where('member_phone','like',$search.'%')->select();
        }else{
            //姓名搜索
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where('member_name','like',$search.'%')->select();
        }
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
        $id=1;//测试
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
        $member_id=1;//测试
        $data=Order::field('id,order_money,pay_type,status,create_time')
            ->where('member_id',$member_id)
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
        $member_id=1;
        $data=Order::alias('a')
            ->field('a.received_money,a.order_money,a.discount,a.cashier,a.pay_time,a.pay_type,a.order_remark,a.order_number,a.status,a.authorize_number,a.prove_number,a.give_money,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id')
            ->where('a.member_id',$member_id)
            ->find();
        return_msg(200,'success',$data);
    }

    /**
     * 会员充值
     *
     * @param  int  $id 会员id
     * @return \think\Response
     */
    public function recharge(Request $request)
    {
        if($request->isPost()){
            //获取会员id
            $data=$request->post();
            $data['money']=floatval($data['money']);
            $info=MerchantMember::field('money')->where('id',$data['id'])->find();
            $total=$data['money']+$info['money'];
            $result=MerchantMember::where('id',$data['id'])->update(['money'=>$total]);
            if($result){
                return_msg(200,'充值成功');
            }else{
                return_msg(400,'充值失败');
            }
        }else{
            //取出所有会员充值送活动
            //获取门店信息
            if(!empty($this->merchant_id)){//?????
                $info=MerchantShop::where('id')->where('merchant_id',$this->merchant_id)->find();
                //取出门店下所有活动
                //取出永久充值送活动
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['recharge_time'=>0,'shop_id'=>$info['id']])->select();
                //取出非永久充值送活动
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                //取出门店下所有活动
                //取出永久充值送活动
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['recharge_time'=>0,'shop_id'=>$info['shop_id']])->select();
                //取出未过期充值送活动
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['recharge_time'=>1,'shop_id'=>$info['shop_id']])->whereTime('end_time','>',time())->select();
               return_msg(200,'success',$data);
            }




        }
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
        //今日充值并为会员
        if(!empty($this->merchant_id)) {
            $data[]['recharge'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 3, 'merchant_id' => $this->merchant_id, 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日赠送
            $data[]['give'] = Order::whereTime('pay_time', 'today')
                ->where(['merchant_id' => $this->merchant_id, 'member_id' => ['>', 0]])
                ->sum('give_money');
            //今日消费
            $data[]['consume'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 1, 'merchant_id' => $this->merchant_id, 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日新增
            $data[]['new_count'] = MerchantMember::whereTime('register_time', 'today')
                ->where('merchant_id', $this->merchant_id)
                ->count();
            return_msg(200, 'success', $data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            $data[]['recharge'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 3, 'shop_id' => $info['shop_id'], 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日赠送
            $data[]['give'] = Order::whereTime('pay_time', 'today')
                ->where(['shop_id' => $info['shop_id'], 'member_id' => ['>', 0]])
                ->sum('give_money');
            //今日消费
            $data[]['consume'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 1, 'shop_id' => $info['shop_id'], 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日新增
            $data[]['new_count'] = MerchantMember::whereTime('register_time', 'today')
                ->where('shop_id', $info['shop_id'])
                ->count();
            return_msg(200, 'success', $data);
        }
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
        $result=MerchantMemberCart::insertGetId($data,true);
        if($result){
            MerchantMember::update('card_id',$result);
            return_msg(200,'success','设置成功');
        }else{
            return_msg(400,'failure','设置失败');
        }
    }
}
