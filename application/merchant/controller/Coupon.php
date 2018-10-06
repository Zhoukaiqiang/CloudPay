<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 21:12
 */

namespace app\merchant\controller;
use think\Controller;
use think\Db;
use think\Request;

class Coupon extends Controller
{
    /**
     * 核销页面
     */
    public function cancel(Request $request)
    {
        //判断是否核销
        if($request->param('sncode')){
            //优惠券查询
            $data=Db::name('shop_coupon')->where(['sncode'=>['=',$request->param('sncode')],'status'=>['=',0]])->field(['type','money','max_money','create_time','end_time','status','id'])->select();
            $data=json_encode($data);
            return_msg(200,'success',$data);
        }else if($request->param('id')){
            //核销优惠券
            $data=Db::name('shop_coupon')->where('id',$request->param('id'))->update(['status'=>1]);
            if($data){
                return_msg(200,'success','核销成功');
            }else{
                return_msg(400,'error','核销失败');
            }
        }

    }
    /**
     * 核销纪录
     */
    public function cancel_list()
    {
        $data=Db::name('shop_coupon')->whereTime('cancel_time','today')->where('status',1)->field(['type','name','user_id','cancel_time','status','id','sncode'])->select();
        return_msg(200,'success',json_encode($data));
    }

}