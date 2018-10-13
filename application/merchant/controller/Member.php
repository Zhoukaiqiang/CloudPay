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

class Member extends Common
{

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
//                ->whereTime('register_time','>','yesterday')
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            //显示当前门店下所有会员
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
                ->where('shop_id',$info['shop_id'])
//                ->whereTime('register_time','>','yesterday')
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
        $search='高';
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
        $data=MerchantMember::field('id,member_head,recharge_money,consumption_money,member_name,member_phone,money,consump_number,register_time')
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
            ->whereTime('pay_time','>',time()-7776000)
            ->select();
//        $data['member_id']=$member_id;
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
        //获取会员订单id
        $id=$request->param('id');
        $id=1;//测试
        $data=Order::alias('a')
            ->field('a.received_money,a.order_money,a.discount,a.cashier,a.pay_time,a.pay_type,a.order_remark,a.order_number,a.status,a.authorize_number,a.prove_number,a.give_money,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id')
            ->where('a.id',$id)
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
                //修改充值总额
                $recharge_money=MerchantMember::field('recharge_money')->where('id',$data['id'])->find();
                $recharge_total=$recharge_money['recharge_money']+$data['money'];
                MerchantMember::where('id',$data['id'])->update(['recharge_money'=>$recharge_total]);
                //查询商户id
                $merchant=MerchantMember::field('merchant_id')->where('id',$data['id'])->find();
                //查询门店id
                $shop=MerchantMember::field('shop_id')->where('id',$data['id'])->find();
                //加入订单表
                $arr=[
                    'status'=>3,
                    'order_money'=>$info['money'],
                    'received_money'=>$info['money'],
                    'pay_time'=>time(),
                    'pay_type'=>'cash',
                    'order_number'=>generate_order_no($data['id']),
                    'merchant_id'=>$merchant['merchant_id'],
                    'give_money'=>$data['give_money'],
                    'member_id'=>$data['id'],
                    'shop_id'=>$shop['shop_id']
                ];
                Order::create($arr,true);
                return_msg(200,'充值成功');
            }else{
                return_msg(400,'充值失败');
            }
        }else{
            //取出所有会员充值送活动
                //取出永久充值送活动
            if(!empty($this->merchant_id)){
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['active_time'=>0,'merchant_id'=>$this->merchant_id])->select();
                //取出未过期充值送活动
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['active_time'=>1,'merchant_id'=>$this->merchant_id])->whereTime('end_time','>',time())->select();
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('merchant_id')->where('id',$this->user_id)->find();
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['active_time'=>0,'merchant_id'=>$info['merchant_id']])->select();
                //取出未过期充值送活动
                $data[]=ShopActiveRecharge::field('recharge_money,give_money')->where(['active_time'=>1,'merchant_id'=>$info['merchant_id']])->whereTime('end_time','>',time())->select();
                return_msg(200,'success',$data);
            }

        }
    }

    /**
     * 扫码收款
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
    public function scan_code(Request $request)
    {
        if(!empty($this->merchant_id)){
            //获取会员id
            $data=$request->post();
            //发送给新大陆
            $result = curl_request($this->url, true, $data, true);
            if($result['Result']=="S"){
                $arr=[
                    'LogNo'=>$result['LogNo'],
                    'order_no'=>$result['orderNo'],
                    'received_money'=>$result['Amount']/100,
                    'order_money'=>$result['total_amount']/100,
                    'member_id'=>$data['id'],
                    'prove_number'=>$data['authCode'],
                    'pay_type'=>$data['payChannel'],
                    'status'=>3,
                    'create_time'=>time(),
                    'pay_time'=>time(),
                    'merchant_id'=>$this->merchant_id
                ];
                $res=Order::insert($arr);
                if($res){
                    return_msg(200,'交易成功');
                }else{
                    return_msg(200,'交易失败');
                }
            }else{
                return_msg(400,'交易失败');
            }
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //获取会员id
            $data=$request->post();
            //发送给新大陆
            $result = curl_request($this->url, true, $data, true);
            if($result['Result']=="S"){
                $arr=[
                    'LogNo'=>$result['LogNo'],
                    'order_no'=>$result['orderNo'],
                    'received_money'=>$result['Amount']/100,
                    'order_money'=>$result['total_amount']/100,
                    'member_id'=>$data['id'],
                    'prove_number'=>$data['authCode'],
                    'pay_type'=>$data['payChannel'],
                    'status'=>3,
                    'create_time'=>time(),
                    'pay_time'=>time(),
                    'merchant_id'=>$this->user_id
                ];
                $res=Order::insert($arr);
                if($res){
                    return_msg(200,'交易成功');
                }else{
                    return_msg(200,'交易失败');
                }
            }else{
                return_msg(400,'交易失败');
            }
        }

    }

    /**
     * 银联收款
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
    public function union_pay()
    {
        
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
            return_msg(200,'设置成功');
        }else{
            return_msg(400,'设置失败');
        }
    }
}
