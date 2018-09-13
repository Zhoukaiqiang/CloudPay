<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Partner extends Controller
{
    /**
     * 显示员工列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //显示当前代理商下面所有员工
        $id=session('agent_id');
        $data=AgentPartner::field(['id,partner_name,partner_phone,create_time,model'])
            ->where('agent_id',$id)
            ->select();
        //取出负责商户数
        foreach($data as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 新增合伙人
     *
     * @return \think\Response
     */
    public function add_partner()
    {
        if(request()->isPost()){
            //传入当前代理商id
            $data=request()->post();
            $data['agent_id']=session('agent_id');
            //提交时间
            $data['create_time']=time();
            //验证
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::create($data,true);
            if($result){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            return_msg(200,'success');
        }
    }

    /**
     * 删除合伙人
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function del()
    {
        //获取合伙人id
        $id=request()->param('id');
        $result=AgentPartner::destroy($id);
        if($result){
            return_msg(200,'删除成功');
        }else{
            return_msg(400,'删除失败');
        }
    }

    /**
     * 合伙人详情页
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function partner_detail()
    {
        if(request()->isPost){
            $data=request()->post();
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::update($data,true);
            if($result){
                return_msg(200,'修改成功');
            }else{
                return_msg(400,'修改失败');
            }
        }else{
            //获取合伙人id
            $id=request()->param('id');
            $data=AgentPartner::where('id',$id)->field(['id,partner_name,partner_phone,create_time,model'])->select();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 查看商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function show_merchant()
    {
        //获取合伙人id
        $id=request()->param('id');
        //取出合伙人下的所有有交易的商户
        $data=TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->join('cloud_order c','a.id=c.merchant_id','left')
            ->where(['partner_id'=>$id])
            ->whereTime('pay_time','>=','yesterday')
            ->select();
        //取出商户支付宝和微信交易额交易量
        foreach($data as &$v){
            $where=[
                'status'=>1,
                'pay_type'=>'alipay',
                'merchant_id'=>$v['id']
            ];
            $alipay=Order::where($where)->sum('received_money');
            $alipay_number=Order::where($where)->count();
            $where1=[
                'status'=>1,
                'pay_type'=>'wxpay',
                'merchant_id'=>$v['id']
            ];
            //取出微信交易额
            $wxpay=Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number=Order::where($where1)->count();
            $v['alipay']=$alipay;
            $v['alipay_number']=$alipay_number;
            $v['wxpay']=$wxpay;
            $v['wxpay_number']=$wxpay_number;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 员工报表
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function partner_report(Request $request)
    {
        //获取当前合伙人id
        $id=request()->param('parent_id');
        $data=AgentPartner::field(['id,partner_name,partner_phone,create_time,model'])
            ->where('agent_id',$id)
            ->select();
        //取出负责商户数
        foreach($data as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
            //根据合伙人id取出昨天新增商户数
            $new_count=TotalMerchant::whereTime('opening_time','yesterday')->where('partner_id',$v['id'])->count();
            $v['new_count']=$new_count;
        }
        //佣金??
        return_msg(200,'success',$data);

    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function test()
    {


    }


}
