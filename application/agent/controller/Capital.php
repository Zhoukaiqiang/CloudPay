<?php

namespace app\agent\controller;

use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalCapital;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Capital extends Controller
{
    /**
     * 申请结算列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //获取当前代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $join=[
            ['cloud_agent_partner b','a.partner_id=b.id'],
            ['cloud_total_agent c','a.agent_id=c.id'],
            ['cloud_order d','d.merchant_id=a.id']
        ];
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join($join)
            ->where('a.agent_id',$agent_id)
            ->group('a.id')
            ->count();
        //分页
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field(['a.id,a.name,b.partner_name,a.merchant_rate,c.agent_name,c.agent_rate,b.rate,b.proportion,b.model'])
            ->join($join)
            ->where('a.agent_id',$agent_id)
            ->group('a.id')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
//        dump($data);die;
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
            //计算佣金
            $money=$alipay+$wxpay;//总交易量
            //间联预估佣金=本代理商所负责商户的交易额*（代理商与商户约定的费率-平台与代理商约定的费率）
            $v['total_money']=$money*(($v['merchant_rate']-$v['agent_rate'])/100);//所得佣金
            $v['agent_money']=$money*$v['merchant_rate']/100;//代理商佣金
            if($v['model']==0){
                //安比例
                $v['partner_money']=$money*$v['proportion']/100;
            }elseif($v['model']==1){
                //按费率
                $v['partner_money']=$money*$v['rate']/100;
            }
            $v['alipay']=$alipay;
            $v['alipay_number']=$alipay_number;
            $v['wxpay']=$wxpay;
            $v['wxpay_number']=$wxpay_number;
        }
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 申请结算
     *
     * @return \think\Response
     */
    public function apply_money()
    {
        $agent_id=session('agent_id');
        if(request()->isPost()){
            $data=request()->post();
            $result=TotalCapital::create($data,true);
            if($result){
                return_msg(200,'申请成功');
            }else{
                return_msg(400,'申请失败');
            }
        }else{
            $data=TotalAgent::field(['agent_name,agent_area,contact_person,agent_phone,json,account'])->where('id',$agent_id)->find();
            $data['create_time']=time();
            $data['json']=json_decode($data['json']);
            return_msg(200,'success',$data);
        }
    }

    /**
     * 子代佣金统计
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function agent_count(Request $request)
    {
        $agent_id=session('agent_id');
        $agent_id=1;
        $data=TotalAgent::alias('a')
            ->field('a.id,a.agent_name,b.id merchant_id')
            ->join('cloud_total_merchant b','a.id=b.agent_id')
            ->join('cloud_order c','b.id=c.merchant_id')
            ->where('a.parent_id',$agent_id)
            ->group('a.id')
            ->select();
        foreach($data as &$v){
            $v['count']=TotalMerchant::where('agent_id',$v['id'])->count();
            //计算微信交易额和支付宝交易额
            //根据商户id获取交易额
            $where=[
                'status'=>1,
                'pay_type'=>'alipay',
                'merchant_id'=>$v['merchant_id']
            ];
            $alipay=Order::where($where)->sum('received_money');
            $alipay_number=Order::where($where)->count();
            $where1=[
                'status'=>1,
                'pay_type'=>'wxpay',
                'merchant_id'=>$v['merchant_id']
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
//        dump($data);die;
        return_msg(200,'success',$data);
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
