<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 21:12
 */

namespace app\merchant\controller;
use app\merchant\model\MemberExclusive;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Db;
use think\Request;

class Coupon extends Commonality
{
    /**
     * 核销页面
     */
    public function cancel(Request $request)
    {

        //sn码
        $sncode=$request->param('sncode');
        //核销优惠券id
        $id=$request->param('id');


        //查询优惠券
        if($sncode){
            //优惠券查询     状态 0已核销 1进行中
            $data=MemberExclusive::alias('a')
                ->field('a.id,a.status,b.name,b.coupons_money,b.order_money,FROM_UNIXTIME(b.start_time,"%Y-%m-%d") start_time,FROM_UNIXTIME(b.end_time,"%Y-%m-%d") end_time ')
                ->join('shop_active_exclusive b','a.exclusive_id=b.id')
                ->where(['a.SN'=>['=',$request->param('sncode')],'a.status'=>1])->select();

            return_msg(200,'success',$data);
        }elseif($id){
            //核销优惠券

            $user_id=$this->id;
            $data=MemberExclusive::where('id',$id)->update(['status'=>0,'cancel_time'=>time(),'usre_id'=>$user_id]);
            if($data){
                return_msg(200,'success','核销成功');
            }else{
                return_msg(400,'error','核销失败');
            }
        }

    }
    /**
     *
     * 核销纪录
     */
    public function cancel_list()
    {

        $data=MemberExclusive::alias('a')
            ->field('d.member_phone,c.name,b.coupons_title,a.status,b.name activity_name,FROM_UNIXTIME(a.cancel_time) cancel_time ')
            ->join('shop_active_exclusive b','a.exclusive_id=b.id')
            ->join('merchant_user c','c.id=a.user_id')
            ->join('merchant_member d','d.id=a.member_id')
            ->whereTime('cancel_time','today')
            ->where('a.status',0)
            ->select();
        return_msg(200,'success',$data);

    }

}