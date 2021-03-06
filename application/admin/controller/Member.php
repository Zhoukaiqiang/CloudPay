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
        $start_time = request()->param('start_time') ? request()->param('start_time') : strtotime('-1 month');
        $end_time = request()->param('end_time') ? request()->param('end_time') : time();

        $time = [$start_time, $end_time];
        $param = "between";

        $role = $this->role_id;
        $admin_id = $this->admin_id;
        if ($role == 1) {
            $agent_id = TotalAgent::field('id')->select();

            $member_count = 0;    //会员总数
            $recharge_money = 0;  //充值总额
            $discount_money = 0;  //优惠总额
            $total_money = 0;           //账户余额
            $data = [];
            foreach ($agent_id as $s) {
                //取出代理商下所有商户数
                $merchant_count = TotalMerchant::field('id,name')->where(['agent_id' => $s['id']])->select();

                foreach ($merchant_count as $k => $v) {
                    $member_count += MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time', $param, $time)->count();
//        die;
                    $recharge_money += MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time', $param, $time)->sum('amount');

                    $discount_money += MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time', $param, $time)->sum('discount_amount');

                    $total_money += MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');

                    $data['list'][$k]['id'] = $v['id'];
                    //商户名
                    $data['list'][$k]['merchant_name'] = $v['name'];

                    $data['list'][$k]['member'] = MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time', $param, $time)->count();

                    $data['list'][$k]['recharge'] = MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time', $param, $time)->sum('amount');

                    $data['list'][$k]['discount'] = MemberRecharge::where('merchant_id', $v['id'])->whereTime('recharge_time', $param, $time)->field('discount_amount')->sum('discount_amount');

                    $data['list'][$k]['money'] = MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');
                }

            }
            $data['member_count'] = $member_count;
            $data['recharge_money'] = $recharge_money;
            $data['discount_money'] = $discount_money;
            $data['total_money'] = $total_money;

            check_data($data);
        } else {
            $agent_id = TotalAgent::field('id')->where('admin_id', $admin_id)->select();

            $member_count = 0;    //会员总数
            $recharge_money = 0;  //充值总额
            $discount_money = 0;  //优惠总额
            $total_money = 0;     //账户余额
            $data = [];
            foreach ($agent_id as $s) {
                //取出代理商下所有商户数
                $merchant_count = TotalMerchant::field('id,name')->where(['agent_id' => $s['id']])->select();

                foreach ($merchant_count as $k => $v) {
                    $member_count += MerchantMember::where('merchant_id', $v['id'])->whereTime('register_time', $param, $time)->count();
//        die;
                    $recharge_money += MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time', $param, $time)->sum('amount');

                    $discount_money += MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time', $param, $time)->sum('discount_amount');

                    $total_money += MerchantMember::field('money')->where('merchant_id', $v['id'])->sum('money');

                    $data[$k]['id'] = $v['id'];
                    //商户名
                    $data[$k]['merchant_name'] = $v['name'];

                    $data[$k]['member'] = MerchantMember::where('merchant_id', $v['id'])->count();

                    $data[$k]['recharge'] = MemberRecharge::where('merchant_id', $v['id'])->field('amount')->whereTime('recharge_time', $param, $time)->sum('amount');

                    $data[$k]['discount'] = MemberRecharge::where('merchant_id', $v['id'])->field('discount_amount')->whereTime('recharge_time', $param, $time)->sum('discount_amount');

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
     * 会员详情
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_member(Request $request)
    {
        $mid = $request->param('id');
        $phone = $request->param('phone');
        $time = $request->param("time");

        if ($phone) {
            $nf = "LIKE";
        } else {
            $nf = "NOT LIKE";
            $phone = "2";
        }
        if ($time) {
            $tf = "between";
        } else {
            $tf = ">=";
            $time = -2;
        }
        $where = [
            "mm.merchant_id" => ["eq", $mid],
            "mm.member_birthday" => [$tf, $time],
            "mm.member_phone" => [$nf, $phone]
        ];


        $rows = Db::name("merchant_member")->where('merchant_id', $mid)->where('member_phone', $nf, $phone)
            ->whereTime("register_time", $tf, $time)
            ->count("id");

        $pages = page($rows);
        $data['list'] = Db::name("merchant_member")->alias("mm")
            ->field('mm.id, mm.member_name,mm.member_phone ,mm.member_birthday, sum(mr.order_money) recharge_money, sum(discount_amount) discount_money, mm.money')
            ->join("member_recharge mr", "mr.member_id = mm.id")
            ->group("mm.id")
            ->where($where)
            ->limit($pages['offset'], $pages["limit"])
            ->select();

        $data['member_count'] = MerchantMember::where('merchant_id', $mid)->count();  //会员总数

        $data['recharge_money'] = MemberRecharge::where('merchant_id', $mid)->field('amount')->sum('amount');  //充值总额

        $data['discount_money'] = MemberRecharge::where('merchant_id', $mid)->field('discount_amount')->sum('discount_amount'); //优惠总额

        $data['total_money'] = MerchantMember::field('money')->where('merchant_id', $mid)->sum('money');          //账户余额

        check_data($data);
    }

    /**
     * 会员数据搜索
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search(Request $request)
    {
        $name = $request->param("ky");
        $time = $request->param("time");

        if ($time) {
            $tf = "between";
            $time = [$time];
        } else {
            $tf = ">=";
            $time = -2;
        }
        if ($name) {
            $nf = "LIKE";
            $name = $name . "%";
        } else {
            $nf = "NOT LIKE";
            $name = "-2";
        }
        $where = [
            "mer.name" => [$nf, $name],
            "mm.register_time" => [$tf, $time],
            "mr.status" => ["eq", 1],
        ];
        $rows = Db::name("total_merchant")->where("name", $nf, $name)->count("id");
        $pages = page($rows);
        $ids = Db::name("total_merchant")->where("name", $nf, $name)
            ->limit($pages["offset"], $pages["limit"])
            ->field("id")->select();
        foreach ($ids as $id) {
            $res["list"][] = Db::name("total_merchant")->alias("mer")
                ->where($where)
                ->where("mer.id", $id["id"])
                ->join("merchant_member mm", "mm.merchant_id = mer.id")
                ->join("member_recharge mr", "mr.member_id = mm.id")
                ->field("mer.id,mer.name as merchant_name ,count(mm.id) member, sum(mr.order_money) recharge, sum(mr.discount_amount) discount, sum(mm.money) money")
                ->find();
        }
        $res["pages"] = $pages;
//        $res["member_count"] = 2;
//        $res["discount_money"] = 2;
//        $res["recharge_money"] = 2;
//        $res["total_money"] = 2;
        check_data($res["list"], $res);

    }
}
