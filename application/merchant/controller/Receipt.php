<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use think\Controller;
use think\Request;

class Receipt extends Common
{
    public $url = 'http://sandbox.starpos.com.cn/emercapp';

    /**
     * 显示账单详情
     *
     * @return \think\Response
     */
    public function receipt_detail(Request $request)
    {
        $id=$request->param('id');
        $id=1;//测试
        $data=Order::alias('a')
            ->field('a.id,a.status,a.received_money,a.order_money,a.discount,b.shop_name,a.cashier,a.pay_time,a.cashier,a.pay_type,a.order_remark,a.order_number,a.status,a.authorize_number,a.prove_number,a.give_money')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.id',$id)
            ->find();
        return_msg(200,'success',$data);
    }

    /**
     * 退款
     *
     * @return \think\Response
     */
    public function refund(Request $request)
    {
        //获取订单id和密码
        $param=$request->param();
        if(!empty($this->merchant_id)){
            //取出商户手机和密码
            $data=TotalMerchant::field('phone,password')->where('id',$this->merchant_id)->find();
            //判断输入密码是否正确
            $param['password']=encrypt_password($param['password'],$data['phone']);
            if($param['password']!=$data['password']){
                return_msg(400,'密码不正确');
            }
            //取出订单号
            $order=Order::field('order_no')->where('id',$param['id'])->find();
            $order=$order->toArray($order);
            //发给新大陆
            $result = curl_request($this->url, true, $order, true);
            $result = json_decode($result, true);
            if($result['result']=='S'){

                return_msg(200,'退款中');
            }else{
                return_msg(400,'退款失败');
            }
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //取出用户手机和密码
            $data = MerchantUser::field('phone,password')->where('id',$this->user_id)
                ->find();
            $param['password']=encrypt_password($param['password'],$data['phone']);
            //判断
            if($param['password']!=$data['password']){
                return_msg(400,'密码不正确');
            }
            //取出订单号
            $order=Order::field('order_no')->where('id',$param['id'])->find();
            $order=$order->toArray($order);
            //发给新大陆
            $result = curl_request($this->url, true, $order, true);
            $result = json_decode($result, true);
            if($result['result']=='S'){
                return_msg(200,'退款成功');
            }else{
                return_msg(400,'退款失败');
            }
        }

    }
    /**
     * 显示账单
     *
     * @return \think\Response
     */
    public function receipt_bill()
    {
        if(!empty($this->merchant_id)){
            //显示当前商户下所有门店
            $data['shop']=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
            //显示当前商户下所有账单
            $data['list']=Order::field('id,status,order_money,pay_type,create_time')
                ->where('merchant_id',$this->merchant_id)
//                ->whereTime('create_time','yesterday')
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //获取门店id
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            //显示当前门店
            $data['shop']=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();
            //显示当前门店下所有账单
            $data['list']=Order::field('id,status,order_money,pay_type,create_time')
                ->where('shop_id',$info['shop_id'])
//                ->whereTime('create_time','yesterday')
                ->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 筛选(需要把门店和状态传过来)
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function filter(Request $request)
    {
        $data['today']=$request->param('today') ? $request->param('today') : null;
        $data['week']=$request->param('week') ? $request->param('week') : null;
        $data['shop_id']=$request->param('shop_id') ? $request->param('shop_id') : null;
        $data['status']=$request->param('status') ? $request->param('status') : null;
        $data['start_time']=$request->param('start_time') ? $request->param('start_time') : null;
        if(!empty($data['start_time'])){
            $data['end_time']=$request->param('end_time') ? $request->param('end_time') : '';
            if(empty($data['end_time'])){
                return_msg(400,'请选择结束时间');
            }
            if(time()-$data['start_time']>8196000){
                return_msg(400,'您选择的时间大于三个月，请重新选择！');
            }
            $param="between";
            $time=[$data['start_time'],$data['end_time']];
            $this->query($data,$time,$param);
        }elseif(!empty($data['today'])){
            $this->query($data,$data['today']);
        }elseif(!empty($data['week'])){
            $this->query($data,$data['week']);
        }

    }

    /**
     * 搜索
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function query($data,$time,$param=">")
    {
        if(!empty($data['shop_id']) && empty($data['status'])){
            //显示当前门店下所有账单
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where('shop_id',$data['shop_id'])
                ->whereTime('create_time',$param,$time)
                ->select();
            return_msg(200,'success',$data);
        }elseif(!empty($data['shop_id']) && !empty($data['status'])){
            //取出当前门店下所有状态
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where(['shop_id'=>$data['shop_id'],'status'=>$data['status']])
                ->whereTime('create_time',$param,$time)
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($data['shop_id']) && !empty($data['status'])){
            //当前商户下所有状态
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where(['merchant_id'=>$this->merchant_id,'status'=>$data['status']])
                ->whereTime('create_time',$param,$time)
                ->select();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 选择门店和选择状态搜索
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function search(Request $request)
    {
        $data['shop_id']=$request->param('shop_id') ? $request->param('shop_id') : null;
        $data['status']=$request->param('status') ? $request->param('status') : null;
        if(!empty($data['shop_id']) && empty($data['status'])){
            //显示当前门店下所有账单
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where('shop_id',$data['shop_id'])
                ->order('create_time desc')
                ->select();
            return_msg(200,'success',$data);
        }elseif(!empty($data['shop_id']) && !empty($data['status'])){
            //取出当前门店下所有状态
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where(['shop_id'=>$data['shop_id'],'status'=>$data['status']])
                ->order('create_time desc')
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($data['shop_id']) && !empty($data['status'])){
            //当前商户下所有状态
            $data=Order::field('id,status,order_money,pay_type,create_time')
                ->where(['merchant_id'=>$this->merchant_id,'status'=>$data['status']])
                ->order('create_time desc')
                ->select();
            return_msg(200,'success',$data);
        }
    }


}
