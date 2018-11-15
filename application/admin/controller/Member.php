<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use app\admin\model\TotalMerchant;
use app\merchant\model\MemberRecharge;
use app\merchant\model\MerchantMember;
use think\Controller;
use think\Db;
use think\Request;

class Member extends Admin
{
    /**
     * 显示会员列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $start_time=request()->param('start_time') ? request()->param('start_time') : strtotime('-1 month');
        $end_time=request()->param('end_time') ? request()->param('end_time') : time();

        $time=[$start_time,$end_time];
        $param="between";

        $role = $this->role_id;
        $admin_id = $this->admin_id;
        if($role == 1){
            $agent_id=TotalAgent::field('id')->select();

            $member_count=0;    //会员总数
            $recharge_money=0;  //充值总额
            $discount_money=0;  //优惠总额
            $total_money=0;           //账户余额
            $data=[];
            foreach($agent_id as $s){
                //取出代理商下所有商户数
                $merchant_count=TotalMerchant::field('id,name')->where(['agent_id'=>$s['id']])->select();

                foreach ($merchant_count as $k=>$v) {
                    $member_count += MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time',$param,$time)->count();
//        die;
                    $recharge_money += MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time',$param,$time)->sum('amount');

                    $discount_money += MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time',$param,$time)->sum('discount_amount');

                    $total_money += MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');

                    $data['list'][$k]['id'] = $v['id'];
                    //商户名
                    $data['list'][$k]['merchant_name'] = $v['name'];

                    $data['list'][$k]['member'] = MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time',$param,$time)->count();

                    $data['list'][$k]['recharge'] = MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time',$param,$time)->sum('amount');

                    $data['list'][$k]['discount'] = MemberRecharge::where('merchant_id', $v['id'])->whereTime('recharge_time',$param,$time)->field('discount_amount')->sum('discount_amount');

                    $data['list'][$k]['money'] = MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');
                }

            }
            $data['member_count'] = $member_count;
            $data['recharge_money'] = $recharge_money;
            $data['discount_money'] = $discount_money;
            $data['total_money'] = $total_money;

            check_data($data);
        }else{
            $agent_id=TotalAgent::field('id')->where('admin_id',$admin_id)->select();

            $member_count=0;    //会员总数
            $recharge_money=0;  //充值总额
            $discount_money=0;  //优惠总额
            $total_money=0;     //账户余额
            $data=[];
            foreach($agent_id as $s){
                //取出代理商下所有商户数
                $merchant_count=TotalMerchant::field('id,name')->where(['agent_id'=>$s['id']])->select();

                foreach ($merchant_count as $k=>$v) {
                    $member_count += MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time',$param,$time)->count();
//        die;
                    $recharge_money += MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time',$param,$time)->sum('amount');

                    $discount_money += MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time',$param,$time)->sum('discount_amount');

                    $total_money += MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');

                    $data[$k]['id'] = $v['id'];
                    //商户名
                    $data[$k]['merchant_name'] = $v['name'];

                    $data[$k]['member'] = MerchantMember::where('merchant_id', $v['id'])->count();

                    $data[$k]['recharge'] = MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time',$param,$time)->sum('amount');

                    $data[$k]['discount'] = MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time',$param,$time)->sum('discount_amount');

                    $data[$k]['money'] = MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');
                }

            }
            $data['member_count'] = $member_count;
            $data['recharge_money'] = $recharge_money;
            $data['discount_money'] = $discount_money;
            $data['total_money'] = $total_money;

            check_data($data);
        }
    }



    /**
     *会员详情
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_member()
    {
        $merchant_id=request()->param('id');
        $start_time=request()->param('start_time') ? request()->param('start_time') : strtotime('-1 month');
        $end_time=request()->param('start_time') ? request()->param('start_time') : time();

        $time=[$start_time,$end_time];
        $param="between";
        $data['list'] = MerchantMember::field('id,member_name,member_phone,money,member_birthday')->where('merchant_id',$merchant_id)->select();

        foreach($data['list'] as &$v){
            $v['recharge_money'] = MemberRecharge::where(['merchant_id'=>$merchant_id,'member_id'=>$v['id']])->whereTime('recharge_time',$param,$time)->sum('amount');

            $v['discount_money'] = MemberRecharge::where(['merchant_id'=>$merchant_id,'member_id'=>$v['id']])->whereTime('recharge_time',$param,$time)->sum('discount_amount');

        }
        $data['member_count'] = MerchantMember::where('merchant_id', $merchant_id)->count();  //会员总数

        $data['recharge_money'] = MemberRecharge::where('merchant_id', $merchant_id)->field('amount')->sum('amount');  //充值总额

        $data['discount_money'] = MemberRecharge::where('merchant_id', $merchant_id)->field('discount_amount')->sum('discount_amount'); //优惠总额

        $data['total_money'] = MerchantMember::field('money')->where('merchant_id', $merchant_id)->sum('money');          //账户余额
        check_data($data);
    }

    /**
     *会员数据搜搜
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $name=request()->param('name');
        $data=[];
        $merchant=TotalMerchant::where(['name'=>['like',$name."%"]])->find();
        $data['list'][0]['id'] = $merchant['id'];
        //商户名
        $data['list'][0]['merchant_name'] = $merchant['name'];

        $data['list'][0]['member'] = MerchantMember::where('merchant_id', $merchant['id'])->count();

        $data['list'][0]['recharge'] = MemberRecharge::where('merchant_id', $merchant['id'])->field('amount')->sum('amount');

        $data['list'][0]['discount'] = MemberRecharge::where('merchant_id', $merchant['id'])->field('discount_amount')->sum('discount_amount');

        $data['list'][0]['money'] = MerchantMember::field('money')->where('merchant_id', $merchant['id'])->sum('money');
        $data['list']=collection($data['list'])->toArray();
        check_data($data);
    }

    /**
     *会员手机搜索
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail_search()
    {
        $merchant_id=request()->param('id');
        $phone=request()->param('phone');
        $data['list']=MerchantMember::where(['member_phone'=>['like',$phone."%"],'merchant_id'=>$merchant_id])->select();
        foreach($data['list'] as &$v){
            $v['recharge_money'] = MemberRecharge::where(['member_id'=>$v['id']])->sum('amount');

            $v['discount_money'] = MemberRecharge::where(['member_id'=>$v['id']])->sum('discount_amount');
        }
        $data['member_count'] = MerchantMember::where('merchant_id', $merchant_id)->count();  //会员总数

        $data['recharge_money'] = MemberRecharge::where('merchant_id', $merchant_id)->field('amount')->sum('amount');  //充值总额

        $data['discount_money'] = MemberRecharge::where('merchant_id', $merchant_id)->field('discount_amount')->sum('discount_amount'); //优惠总额

        $data['total_money'] = MerchantMember::field('money')->where('merchant_id', $merchant_id)->sum('money');          //账户余额
        check_data($data);
    }
}
