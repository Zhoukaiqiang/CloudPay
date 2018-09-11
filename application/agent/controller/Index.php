<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Merchant extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
        $time=time()-(23*60*60);
        echo $time;

    }

    /**
     * 显示交易数据
     *
     * @return \think\Response
     */
    public function trad()
    {
        $where=[
            'status'=>1,
            'pay_type'=>'alipay'
        ];
        //取出支付宝交易额
        $alipay=Order::where($where)->sum('seller_money');
        //取出支付宝交易量
        $alipay_number=Order::where($where)->count();
        $where=[
            'status'=>1,
            'pay_type'=>'wxpay'
        ];
        //取出微信交易额
        $wxpay=Order::where($where)->sum('seller_money');
        //取出微信交易量
        $wxpay_number=Order::where($where)->count();
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.contact,a.phone,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->select();

        return_msg(200,'success',$data);
    }

    /**
     * 变更员工
     *
     * @param  id 商户id
     * @return \think\Response
     */
    public function change_partner(Request $request)
    {
        if($request->isPost()){
            //获取合伙人id
            $data['partner_id']=$request->post('pertner_id');
            //获取商户id
            $data['id']=$request->post('id');
            $request=TotalMerchant::update($data);
            if($request){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }else{
            $id=$request->param('id');
            //取出商户姓名
            $data=TotalMerchant::where('id',$id)->field(['id','name'])->select();
            //取出所有合伙人姓名和id
            $partner=AgentPartner::field(['id','partner_name'])->select();
            $data['partner']=$partner;
            return_msg(200,'success',$data);
        }
    }

    /**
     * 获取活跃商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function active()
    {
        //获取昨日活跃商户
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.alipay_money,a.alipay_number,a.wechat_money,a.wechat_number,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->whereTime('a.create_time','yesterday')
            ->select();
        return_msg(200,'success',$data);
    }

    /**
     * 获取新增商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function new_merchant()
    {
        //获取昨日新增商户
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.alipay_money,a.alipay_number,a.wechat_money,a.wechat_number,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->whereTime('a.opening_time','yesterday')
            ->select();
        return_msg(200,'success',$data);
    }

    /**
     * 总商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function total_merchant(Request $request)
    {
        //
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->select();
        return_msg(200,'success',$data);
    }

    /**
     * 审核中商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     */
    public function review_merchant()
    {
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.review_status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where(['a.review_status'=>['<>',3]])
            ->select();
        return_msg(200,'success',$data);
    }

    /**
     * 已驳回商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     */
    public function reject_merchant()
    {
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.rejected,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where(['a.review_status'=>['=',3]])
            ->select();
        return_msg(200,'success',$data);
    }
}
