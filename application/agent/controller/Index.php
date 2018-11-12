<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use Phinx\Seed\SeedInterface;
use think\Controller;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Request;
use think\Session;
use think\Validate;
use Zxing\Qrcode\Decoder\DataBlock;

class Index extends Controller
{


    /**
     * 用户登录
     * @param [strin]   user_name 用户名（电话）
     * @param [stirng]  password  用户密码
     * @return [json] 返回信息
     * @throws Exception DbException
     */
    public function login(Request $request)
    {
        $data = $request->param();
        check_params("agent_login", $data);
        $this->check_exist($data['phone'], 'phone', 1);
        $db_res = Db('total_agent')->field('id,username,agent_phone,status,password, parent_id')
            ->where('agent_phone', $data['phone'])->find();

        if ($db_res['password'] !== encrypt_password($data['password'], $data["phone"])) {
            return_msg(400, '用户密码不正确！');
        } else {
            unset($db_res['password']); //密码不返回
            /** 登录成功储存session */
            Session::set("username_", $db_res);
            return_msg(200, '登录成功！', $db_res);
        }
    }

    /**
     *一键登录代理商系统
     * @param Request $request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agent_login(Request $request)
    {
        $data = $request->param();
        if (!isset($data['token'])) {
            return_msg(400, '非法登录');
        }
        if (md5($data['phone'] . 'token') != $data['token']) {
            return_msg(400, 'token验证失败');
        }
        $this->check_exist($data['phone'], 'phone', 1);
        $status = TotalAgent::field('status')->where('agent_phone', $data['phone'])->find();
        if ($status['status'] == 0) {
            return_msg(400, '该代理商不能进入代理商系统');
        }
        $info = TotalAgent::field('id,username,agent_phone,status, parent_id')
            ->where('agent_phone', $data['phone'])
            ->find();
        if ($info) {

            Session::set("username_", $info);
            /*$this->redirect('/agent/index/login',[
                "phone" => $info["phone"],
                "password" => $info['password'],
                "token" => "access_token",
            ]);*/

            return_msg(200, '登录成功', $info);
        } else {
            return_msg(400, '登录失败');
        }
    }

    /**
     * 登出
     */
    public function logout()
    {
        Session::clear();
        if (Session::has("username_")) {
            return_msg(400, "失败");
        } else {
            return_msg(200, "成功");
        }
    }


    /**
     * 获取当前代理商的二级代理商
     * @param Request $request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_second_agent(Request $request)
    {
        if ($request->isGet()) {
            $id = Session::get("username_")["id"];
            $res = TotalAgent::where("parent_id", $id)->field("agent_name,id")->select();

            if (count($res)) {
                return_msg(200, "success", $res);
            } else {
                return_msg(400, "no data");
            }
        }
    }

    /**
     * 首页--- 统计数据
     * @param [int]   $time 7/30
     * @param [int] $id 二级代理商ID
     * @param Request $request
     * @method GET
     */
    public function get_count_data(Request $request)
    {
        if ($request->isGet()) {
            $id = Session::get("username_")["id"];
            /** 当前代理商下所有直属商户交易额 --  微信/支付宝/银行卡  */
            $time = $request->param("time");
            $data["ali_amount"] = $this->get_type_amount($id, 'alipay', $time);
            $data["wx_amount"] = $this->get_type_amount($id, 'wxpay', $time);
            $data["bank_amount"] = $this->get_type_amount($id, 'etc', $time);

            /** 当前代理商下所有直属商户交易量 --  微信/支付宝/银行卡  */
            $data["ali_num"] = $this->get_type_num($id, "alipay", $time);
            $data["wx_num"] = $this->get_type_num($id, "wxpay", $time);
            $data["bank_num"] = $this->get_type_num($id, "etc", $time);

            return_msg(200, "success", $data);
        }
    }


    /**
     * 首页---- 图表
     * @param [int] $time 7/30
     * @method GET
     * @param Request $request
     */
    public function get_diagram(Request $request)
    {
        if ($request->isGet()) {
            $id = Session::get("username_")["id"];
            /* 接受参数 */
            $pay_type = $request->param('pay_type');

            $time = $request->param("time") ? $request->param("time") : 0;
            $i = -7;
            if ($time == 30) {
                $i = -30;
            }
            if (empty($pay_type)) {
                $pay_type_flag = "<>";
                $pay_type = '-2';
            } else {
                $pay_type_flag = "eq";
            }

            $sum = [];
            /** 默认展示7天数据 */

            while ($i < 0) {
                $morning = strtotime(date('Y-m-d 00:00:00', strtotime($i . ' days')));
                $night = strtotime(date('Y-m-d 23:59:59', strtotime($i . ' days')));
                $date = [$morning, $night];
                $data['chartData'][] = TotalMerchant::alias("mer")
                    ->join("cloud_order o", "o.merchant_id = mer.id")
                    ->where("mer.agent_id", $id)
                    ->where("pay_type", $pay_type_flag, $pay_type)
                    ->whereTime('pay_time', "between", $date)
                    ->sum("o.received_money");

                $sum[] = TotalMerchant::alias("mer")
                    ->join("cloud_order o", "o.merchant_id = mer.id")
                    ->where("mer.agent_id", $id)
                    ->where("pay_type", $pay_type_flag, $pay_type)
                    ->whereTime('pay_time', "between", $date)
                    ->count("o.id");

                $i++;
            }
            foreach ($data["chartData"] as $k => $v) {
                $filted_data[] = [
                    "amount" => $v,
                    "count" => $sum[$k],
                    "pay_time" => date('Y-m-d', strtotime($i + $k . ' days')),
                ];
            }
            check_data($filted_data);
        }
    }

    /**
     * 首页--商户数据
     * @param [int] $id 二级代理商ID
     * @throws DbException
     * @return [json]
     * @throws Exception
     */
    public function get_merchant_data(Request $request)
    {
        if ($request->isGet()) {
            $id = Session::get("username_")["id"];
            /** 昨日活跃商户 */
            $data["active_mer"] = Db::name("total_merchant")->alias("mer")
                ->join("cloud_order o", "o.merchant_id = mer.id", "RIGHT")
                ->where("mer.agent_id = $id")
                ->whereTime("pay_time", "yesterday" )
                ->group("o.merchant_id")
                ->count("o.id");

            /** 昨日新增商户 */

            $data["new_come"] = TotalMerchant::where("agent_id = $id")
                ->whereTime("create_time", "yesterday")
                ->count("id");

            /** 总商户 */
            $data["total_mer"] = collection(TotalMerchant::all(["agent_id" => $id]))->count('id');
            /** 审核中商户 */
            $data["checking_mer"] = TotalMerchant::where(["status" => 0, "agent_id" => $id])->count("id");
            /** 已驳回商户 */
            $data["refuse_mer"] = TotalMerchant::where(["status" => 3, "agent_id" => $id])->count("id");
            /** 7日无交易商户 */
            $data["inactive_mer"] = TotalMerchant::alias("mer")
                ->where("mer.agent_id = $id")
                ->join("cloud_order o", "o.merchant_id = mer.id")
                ->whereTime("pay_time", "<", strtotime("-7 days"))
                ->group("o.merchant_id")
                ->count("mer.id");

            return_msg(200, "success", $data);
        }
    }

    /**
     * 获取对应类型的交易额
     * @param $id
     * @param $type
     * @return float|int
     */
    protected function get_type_amount($id, $type, $time = 0)
    {
        $time = intval($time);
        if ($time == 7) {
            $time = strtotime("-7 days");
        } elseif ($time == 30) {
            $time = strtotime("-30 days");
        } elseif ($time == 0) {
            $time = "yesterday";
        }
        $res = TotalMerchant::alias("mer")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where([
                "mer.agent_id" => $id,
                "o.pay_type" => $type
            ])
            ->whereTime("pay_time" , $time)
            ->sum("o.received_money");
        return $res;
    }


    /**
     * 显示交易数据
     * 默认显示昨天的交易数据
     * @return \think\Response
     * @throws Exception
     */
    public function trad(Request $request)
    {
        //获取当前代理商id
        $agent_id = session('username_')["id"];

        $ky = $request->param("ky");
        $deal = $request->param("deal");
        if ($ky) {
            $k_f = "LIKE";
            $ky = $ky . "%";
        }else {
            $k_f = "NOT LIKE";
            $ky = "-2";
        }

        halt(1);
        $where = ["name", $k_f, $ky];
        //获取总行数
        $rows = TotalMerchant::alias('a')
            ->join('cloud_order c', 'c.merchant_id=a.id', 'left')
            ->where('a.agent_id', $agent_id)
            ->where($where)
            ->whereTime('pay_time',  'yesterday')
            ->group('a.id')
            ->count();

        $pages = page($rows);
        //根据代理商id查询昨日交易商户
        $data['list'] = TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b', 'a.partner_id=b.id', 'left')
            ->join('cloud_order c', 'c.merchant_id=a.id', 'left')
            ->where('a.agent_id', $agent_id)
            ->where($where)
            ->whereTime('pay_time', 'yesterday')
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $parent = AgentPartner::where('agent_id', $agent_id)->field('partner_name,id')->select();
        $data['parent'] = $parent;
        //取出商户支付宝和微信交易额交易量
        foreach ($data['list'] as &$v) {
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count();
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['id']
            ];
            //取出微信交易额
            $wxpay = Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number = Order::where($where1)->count();
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
        }
        $data['pages'] = $pages;
        check_data($data["list"], $data);
    }

    /**
     * 变更员工
     *
     * @param  [int] id 商户id
     * @return \think\Response
     */
    public function change_partner(Request $request)
    {
        if ($request->isPost()) {
            //获取合伙人id
            /*$data['partner_id']=$request->post('pertner_id');
            //获取商户id
            $data['id']=$request->post('id');*/
            $data = $request->post();

            $request = TotalMerchant::update($data);
            if ($request) {
                return_msg(200, '操作成功');
            } else {
                return_msg(400, '操作失败');
            }
        } else {
            $id = Session::get("username_")["id"];
            //取出商户姓名
            $data = TotalMerchant::where('id', $id)->field(['id', 'name'])->select();
            //取出所有合伙人姓名和id
            $partner = AgentPartner::field(['id', 'partner_name'])->select();
            $data['partner'] = $partner;

            return_msg(200, 'success', $data);
        }
    }

    /**
     * 获取活跃商户
     * @throws
     * @param  int $id
     * @return \think\Response
     */
    public function active()
    {
        //获取当前代理商id
        $agent_id = session('username_')["id"];

        //获取总行数
        $rows = TotalMerchant::alias('a')
            ->join('cloud_order c', 'c.merchant_id=a.id', 'left')
            ->where('a.agent_id', $agent_id)
            ->whereTime('pay_time', '>=', 'yesterday')
            ->group('a.id')
            ->count();
        //分页
        $pages = page($rows);
        //根据代理商id查询昨日交易商户
        $data['list'] = TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b', 'a.partner_id=b.id', 'left')
            ->join('cloud_order c', 'c.merchant_id=a.id', 'left')
            ->where('a.agent_id', $agent_id)
            ->whereTime('pay_time', '>=', 'yesterday')
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
//        dump($data);die;
        //取出商户支付宝和微信交易额交易量
        foreach ($data['list'] as &$v) {
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count();
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['id']
            ];
            //取出微信交易额
            $wxpay = Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number = Order::where($where1)->count();
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
        }
        $data['pages'] = $pages;
        if (count($data["list"])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 获取新增商户
     * @throws Exception
     * @param  status 0停用 1启用
     * @return \think\Response
     */
    public function new_merchant()
    {
        //获取代理商id
        $agent_id = session('username_')["id"];;

        //获取昨日新增商户
        //获取总行数
        $rows = TotalMerchant::whereTime('opening_time', 'yesterday')
            ->where(['agent_id' => $agent_id, 'status' => 1])
            ->count();
        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.phone,b.partner_name')
            ->join('cloud_agent_partner b', 'a.partner_id=b.id', 'left')
            ->whereTime('a.opening_time', 'yesterday')
            ->where(['a.agent_id' => $agent_id, 'a.status' => 1])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;

        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 总商户
     *
     * @param  \think\Request $request
     * @param  int $id
     * @throws  Exception
     */
    public function total_merchant(Request $request)
    {
        //获取代理商id
        $agent_id = session('username_')["id"];;

        $rows = TotalMerchant::where('agent_id', $agent_id)
            ->count();
        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b', 'a.agent_id=b.id', 'left')
            ->where('a.agent_id', $agent_id)
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 审核中商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     * @throws Exception
     */
    public function review_merchant()
    {
        //获取代理商id
        $agent_id = session('username_')["id"];

        $rows = TotalMerchant::where(['review_status' => ['<>', 3], 'agent_id' => ['=', $agent_id]])
            ->count();
        $pages = page($rows);
        //显示当前代理商下审核中的商户
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.review_status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b', 'a.agent_id=b.id', 'left')
            ->where(['a.review_status' => ['<>', 3], 'a.agent_id' => ['=', $agent_id]])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 已驳回商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     * @throws Exception
     */
    public function reject_merchant()
    {
        //获取代理商id
        $agent_id = session('username_')["id"];

        $rows = TotalMerchant::where(['review_status' => ['=', 3], 'agent_id' => ['=', $agent_id]])
            ->count();
        $pages = page($rows);
        //显示当前代理商下已驳回的商户
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.rejected,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b', 'a.agent_id=b.id', 'left')
            ->where(['a.review_status' => ['=', 3], 'a.agent_id' => ['=', $agent_id]])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 查询7日无交易数据
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function no_trad()
    {
        //获取代理商id
        $agent_id = session('username_')["id"];

        $time = strtotime("-7 days");//7天前时间
        //获取代理商下7天无交易商户
        $join = [
            ['cloud_agent_partner b', 'a.partner_id=b.id'],
            ['cloud_order c', 'a.id=c.merchant_id']
        ];

        $rows = TotalMerchant::alias('a')
            ->join($join)
            ->whereTime('pay_time', '<', $time)
            ->where('a.agent_id', $agent_id)
            ->count();
        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join($join)
            ->whereTime('pay_time', '<', $time)
            ->where('a.agent_id', $agent_id)
            ->limit($pages['offset'], $pages['limit'])
            ->group("a.id")
            ->select();
        $data['pages'] = $pages;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "no data");
        }
    }

    /**
     * 首页--交易数据搜索查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_deal(Request $request)
    {
        return strtotime(date("Y-m-d", strtotime("-6 days")));
        //获取商户|联系人|联系方式
        $name = $request->param('keyword');
        //获取合伙人id
        $partner_id = $request->param('partner_id') ? $request->param('partner_id') : 0;
        //获取是否交易
        $deal = $request->param('deal');

        $pay_time = $request->param('pay_time') ? $request->param('pay_time') : 1531011563;
        $yesterday = $request->param('yesterday') ? $request->param('yesterday') : 2133999048;


        //如果$deal=1是有交易用户，$deal=2是无交易用户
        $nodeal = 'between';

        if ($deal == 2) {
            $nodeal = 'not between';
        }
        $symbol = '<>';
        if ($partner_id > 0) {
            $symbol = 'eq';
        }
        $total = Db::name('total_merchant')->count('id');
        $rows = TotalMerchant::alias('a')
            ->field('a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id', 'left')
            ->where('a.abbreviation|a.contact|a.phone', 'like', "%$name%")
            ->where('a.partner_id', $symbol, $partner_id)
            ->where('cloud_order.pay_time', $nodeal, [$pay_time, $yesterday])
            ->group('a.id')
            ->count('a.id');

        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.name,a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id', 'left')
            ->where('a.abbreviation|a.contact|a.phone', 'like', "%$name%")
            ->where('a.partner_id', $symbol, $partner_id)
            ->where('cloud_order.pay_time', $nodeal, [$pay_time, $yesterday])
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();

        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, '没有数据');
        }
    }

    /**
     * 首页-昨日活跃商户
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function yesterday_active(Request $request)
    {
        $total = Db::name('total_merchant')->count('id');

        $rows = TotalMerchant::alias('a')
            ->field('a.abbreviation,a.address,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id', 'left')
            ->whereTime('cloud_order.pay_time', 'between', [$request->param('pay_time'), $request->param('yesterday')])
            ->group('a.id')
            ->count('a.id');
        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.abbreviation,a.address,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id', 'left')
            ->whereTime('cloud_order.pay_time', 'between', [$request->param('pay_time'), $request->param('yesterday')])
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, '没有数据');
        }
    }

    /**
     * 首页-七日无交易-筛选查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws Exception
     */
    public function merchant_trade(Request $request)
    {
        //获取商户|联系人|联系方式
        $name = $request->param('keyword');
        //获取合伙人id
        $partner_id = $request->param('partner_id') ? $request->param('partner_id') : 0;
        //获取是否交易 $deal=1是有交易用户，$deal=2是无交易用户
        $deal = $request->param('deal');
        $total = Db::name('total_merchant')->count('id');
        $pay_time = $request->param('pay_time') ? $request->param('pay_time') : strtotime('-8 days') - 1;
        $yesterday = $request->param('create_time') ? $request->param('create_time') : mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;;
        //如果$deal=1是有交易用户，$deal=2是无交易用户
        $nodeal = 'between';
        if ($deal == 2) {
            $nodeal = 'not between';
        }
        $symbol = '<>';
        if ($partner_id > 0) {
            $symbol = 'eq';
        }

        $rows = TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner', 'a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone', 'like', "%$name%")
            ->where('a.partner_id', $symbol, $partner_id)
            ->whereTime('cloud_order.pay_time', $nodeal, [$pay_time, $yesterday])
            ->group('a.id')
            ->count('a.id');
        $pages = page($rows);
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.channel,a.address,a.name,a.id,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner', 'a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone', 'like', "%$name%")
            ->where('a.partner_id', $symbol, $partner_id)
            ->whereTime('cloud_order.pay_time', $nodeal, [$pay_time, $yesterday])
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, '没有数据');
        }
    }

    /**
     * 商户列表-筛选查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws  Exception
     */
    public function merchant_list(Request $request)
    {
        //获取商户|联系人|联系方式
        $agent_id = Session::get('username_')["id"];
        $name = $request->param('keyword');
        //获取合伙人id
        $partner_id = $request->param('partner_id');
        $status = $request->param('status');
        $create_time = $request->param('opening_time');
        $address = $request->param('address');

        if ($status) {
            $status_f = "eq";
        } else {
            $status_f = ">";
            $status = -2;
        }

        if ($address) {
            $address_f = "LIKE";
        } else {
            $address_f = "NOT LIKE";
            $address = "-2";
        }
        if ($partner_id) {
            $partner_f = "eq";
        } else {
            $partner_f = ">";
            $partner_id = -2;
        }
        if ($name) {
            $name_f = "LIKE";
            $name = $name . "%";
        } else {
            $name_f = "NOT LIKE";
            $name = "-2";
        }
        if ($create_time) {
            $create_f = "between";
            $night = strtotime(date("Y-m-d 23:59:59", $create_time));
            $create_time = [(int)$create_time, $night];
        } else {
            $create_f = ">";
            $create_time = -2;
        }
        $where["a.status"] = [$status_f, $status];
        $where["a.partner_id"] = [$partner_f, $partner_id];
        $where["a.agent_id"] = ["eq", $agent_id];
        $where["ag.agent_area"] = [$address_f, $address];
        $where["a.abbreviation|a.contact|a.phone|a.name"] = [$name_f, $name];

        $total = Db::name('total_merchant')->where("agent_id", $agent_id)->count('id');
        $rows = TotalMerchant::alias('a')
            ->join('cloud_order', 'cloud_order.merchant_id = a.id')
            ->join('cloud_agent_partner', ' cloud_agent_partner.id = a.partner_id')
            ->join("cloud_total_agent ag", "ag.id = a.agent_id")
            ->where($where)
            ->whereTime('opening_time', $create_f, $create_time)
            ->group('a.id')
            ->count('a.id');
        $pages = page($rows);

        $data['list'] = TotalMerchant::alias('a')
            ->field('a.channel,ag.agent_area as address,a.name,a.id,a.contact,a.phone,a.status,a.opening_time,a.review_status,a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id')
            ->join("cloud_total_agent ag", "ag.id = a.agent_id")
            ->join('cloud_agent_partner', 'a.partner_id=cloud_agent_partner.id')
            ->where($where)
            ->whereTime('opening_time', $create_f, $create_time)
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;

        check_data($data["list"], $data);
    }

    /**
     * 商户交易--筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws  Exception
     */
    public function merchant_deal(Request $request)
    {
        $name = $request->param('keyword');
        //获取合伙人id
        $deal = $request->param('deal');
        $create_time = $request->param('opening_time');

        if ($deal) {
            $deal_f = "eq";
        } else {
            $deal_f = ">";
            $deal = -2;
        }

        if ($name) {
            $name_f = "LIKE";
            $name = $name . "%";
        } else {
            $name_f = "NOT LIKE";
            $name = "-2";
        }
        if ($create_time) {
            $create_f = "between";
            $night = strtotime(date("Y-m-d 23:59:59", $create_time));
            $create_time = [(int)$create_time, $night];
        } else {
            $create_f = ">";
            $create_time = -2;
        }

        $total = Db::name('total_merchant')->where("agent_id", Session::get("username_")['id'])->count('id');

        $rows = TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order', 'a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner', 'cloud_agent_partner.id=a.partner_id')
            ->where('a.name', $name_f, $name)
            ->where('a.agent_id', Session::get("username_")['id'])
            ->whereTime('cloud_order.pay_time', $create_f, $create_time)
            ->group('a.id')
            ->count('a.id');
        $pages = page($rows);
        if ($deal) {
            $total_arr = Db::name("total_merchant")->where("agent_id", Session::get("username_")['id'])->field("id")->select();

            foreach ($total_arr as $v) {
                $ta[] = $v["id"];
            }
            $arr = Db::name("total_merchant")->alias("m")->where("m.agent_id", Session::get("username_")['id'])
                ->join("cloud_order o", "o.merchant_id = m.id")
                ->group("m.id")
                ->field("m.id")->select();

            foreach ($arr as $v) {
                $aa[] = $v["id"];
            }
            $result = array_diff($ta, $aa);
            $str = implode(',', $result);

            $data['list'] = TotalMerchant::alias('a')
                ->where("a.id", "in", $str)
                ->where('a.name', $name_f, $name)
                ->where('a.partner_id', $partner_f, $partner_id)
                ->group('a.id')
                ->limit($pages['offset'], $pages['limit'])
                ->select();
        } else {
            $data['list'] = TotalMerchant::alias('a')
                ->field('a.name,a.address,cloud_agent_partner.partner_name,a.id,a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
                ->join('cloud_order', 'a.id=cloud_order.merchant_id')
                ->join('cloud_agent_partner', 'cloud_agent_partner.id=a.partner_id')
                ->where('a.agent_id', Session::get("username_")['id'])
                ->where('a.name', $name_f, $name)
                ->where('a.partner_id', $partner_f, $partner_id)
                ->whereTime('cloud_order.pay_time', $create_f, $create_time)
                ->group('a.id')
                ->limit($pages['offset'], $pages['limit'])
                ->select();
        }


        foreach ($data['list'] as &$v) {
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count();
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['id']
            ];
            //取出微信交易额
            $wxpay = Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number = Order::where($where1)->count();
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
        }
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        check_data($data["list"], $data);
    }

    /**
     * 服务商列表-筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \Exception
     */
    public function facilitator_list(Request $request)
    {
        $parent_id = Session::get("username_")['id'];
        //服务商名称|联系人|联系方式
        $name = $request->param('keyword');
        //服务商的状态，1启用 0停用 3全部
        $status = $request->param('status');
        //代理区域    0代表全部区域
        $agent_area = $request->param('agent_area');
        //时间
        $create_time = $request->param('create_time');
        //服务商

        if ($status) {
            $status_f = "eq";
        } else {
            $status_f = "<>";
            $status = -2;
        }
        if ($agent_area) {
            $agent_f = 'LIKE';
        } else {
            $agent_f = 'NOT LIKE';
            $agent_area = "-2";
        }
        if ($name) {
            $name_f = 'LIKE';
            $name = $name . "%";
        } else {
            $name_f = 'NOT LIKE';
            $name = "-2";
        }
        if ($create_time) {
            $create_f = 'between';
            $night = strtotime(date("Y-m-d, 24:59:59", $create_time));
            $create_time = [$create_time, $night];
        } else {
            $create_f = '>';
            $create_time = -2;
        }

        $total = Db::name('total_agent')->where("parent_id ", $parent_id)->count('id');

        $rows = Db::name('total_agent')->where([
            "agent_name" => [$name_f, $name],
            'parent_id' => $parent_id,
            'agent_area' => [$agent_f, $agent_area],
            'status' => [$status_f, $status],
            'create_time' => [$create_f, $create_time]
        ])->count('id');
        $pages = page($rows);
        $data['list'] = Db::name('total_agent')->where([
            "agent_name" => [$name_f, $name],
            'parent_id' => $parent_id,
            'agent_area' => [$agent_f, $agent_area],
            'status' => [$status_f, $status],
            'create_time' => [$create_f, $create_time]
        ])
            ->field(['id,agent_name,contact_person,agent_phone,agent_mode,agent_area,create_time,status'])
            ->limit($pages['offset'], $pages['limit'])
            ->select();

        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, '没有数据');
        }
    }

    /**
     * 服务商商户-筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws  \Exception
     */

    public function facilitator_tenant(Request $request)
    {
        $name = $request->param('keyword');
        //服务商的状态，1启用 0停用 3全部
        $status = $request->param('status');
        //代理区域    0代表全部区域
        $agent_area = $request->param('agent_area');
        //时间
        $agent_id = Session::get("username_")["id"];
        //服务商

        if ($status) {
            $status_f = "eq";
        } else {
            $status_f = "<>";
            $status = -2;
        }
        if ($agent_area) {
            $agent_f = 'LIKE';
        } else {
            $agent_f = 'NOT LIKE';
            $agent_area = "-2";
        }
        if ($name) {
            $name_f = 'LIKE';
            $name = $name . "%";
        } else {
            $name_f = 'NOT LIKE';
            $name = "-2";
        }
        $total = Db::name('total_agent')->where("parent_id",$agent_id)->count('id');

        $rows = Db::name('total_merchant')
            ->where(['name|contact|phone' => [$name_f, $name],
                'status' => [$status_f, $status], 'agent_id' => ['eq', $agent_id]])
            ->count('id');
        $pages = page($rows);

        $data['list'] = Db::name('total_merchant')
            ->where(['name|contact|phone' => [$name_f, $name],
                'status' => [$status_f, $status], 'agent_id' => ['eq', $agent_id]])
            ->field(['id', 'agent_name', 'address', 'status', 'abbreviation', 'opening_time',"name"])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        check_data($data["list"], $data);

    }


    /**
     * 获取对应类型的交易量
     * @param $id
     * @param $type
     * @return int|string
     * @throws \Exception
     */
    protected function get_type_num($id, $type, $time = 0)
    {
        $time = intval($time);
        if ($time == 7) {
            $time = strtotime("-7 days");
        } elseif ($time == 30) {
            $time = strtotime("-30 days");
        } elseif ($time == 0) {

            $time = "yesterday";
        }
        $res = TotalMerchant::alias("mer")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where([
                "mer.agent_id" => $id,
                "o.pay_type" => $type
            ])
            ->whereTime("pay_time", $time)
            ->count("o.id");
        return $res;
    }

    /**
     * 检验账号是存在数据库
     * @param $value
     * @param string $type
     * @param $exist
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function check_exist($value, $type = 'phone', $exist)
    {
        $type_num = $type == 'phone' ? 2 : 4;
        $flag = $type_num + $exist;
        $phone_res = Db("total_agent")->where("agent_phone", $value)->find();
//        $email_res = db("total_admin")->where("email", $value)->find();
        $email_res = 0;
        switch ($flag) {
            /* 2+0 phone need no exist */
            case 2:
                if ($phone_res) {
                    return_msg(400, '手机号已经被占用');
                }
                break;
            /*  2+1 phone need exist */
            case 3:
                if (!$phone_res) {
                    return_msg(400, '手机号不存在！');
                }
                break;
            /* 4+0 email need no exist */
            case 4:
                if ($email_res) {
                    return_msg(400, '此邮箱已经被占用！');
                }
                break;
            /* 4+1 email need exist */
            case 5:
                if (!$email_res) {
                    return_msg(400, '此邮箱不存在！');
                }
                break;

        }

    }
}
