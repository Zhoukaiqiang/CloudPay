<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalCapital;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Session;

class Capital extends Agent
{

    /**
     * 申请结算列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws Exception
     */
    public function index(Request $request)
    {
        //获取当前代理商id
        $time = $request->param("time");

        if ($time) {
            $tf = "between";
        } else {
            $tf = ">";
            $time = -2;
        }

        /**  设定 时间1-现在的月份 $date */
        date_default_timezone_set('Asia/Shanghai');

        $first_day_of_month = date('Y-m', time()) . '-01 00:00:01';
        $t = strtotime($first_day_of_month);

        /** 一级代理商 */
//        $rows = TotalMerchant::alias("a")
//            ->join($join)
//            ->where($map)
//            ->group('a.id')
//            ->count("a.id");
        $rows = date('m', $t);
        $pages = page($rows);

        $join = [
            ["cloud_total_merchant m", "o.merchant_id = m.id"],
            ["cloud_total_agent ag", "m.agent_id = ag.id"]
        ];

        $map = [
            "m.agent_id" => $this->aid
        ];

        $field = ["ag.agent_name","sum(o.received_money) total","count(o.id) num"];
        $i = 0;
        while ($i <= $rows ) {
            $ni = $i - 1;
            $time = [strtotime("- $i month", $t), strtotime( -$ni ." month", $t) ];

            $data[] = Db::name("order")->alias("o")
                ->join($join)->field($field)
                ->where($map)
                ->whereTime("o.pay_time", "between", $time)
                ->find();
            $i++;
        }

        check_data($data);


        $data["count"]["total_money"] = 0;
        $data["count"]["child_money"] = 0;
        $data["count"]["partner_money"] = 0;
        $data["count"]["deserve_money"] = 0;
        //取出商户支付宝和微信交易额交易量
        foreach ($data['list'] as &$v) {
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count('id');
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['id']
            ];
            //取出微信交易额
            $wxpay = Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number = Order::where($where1)->count();
            //计算佣金
            $money = $alipay + $wxpay;//总交易额

            //间联预估佣金 = 本代理商所负责商户的交易额*（二级代理费率-一级代理费率-平台与代理商约定的费率）


            /** 如果有子代理商 */
            if (!empty($v["agent_name"])) {

                $v["child_money"] = $money * (($v["merchant_rate"] - $v["agent_rate"]) / 100);    //子代理商佣金
            } else {
                $v["child_money"] = 0;
            }
//                $v['agent_money'] = $money * $v['merchant_rate'] / 100;  //
            /** 合伙人佣金 如果有合伙人*/
            if (!empty($v["partner_id"])) {
                if ($v['model'] == 0) {
                    //安比例
                    $v['partner_money'] = $money * $v['proportion'] / 100;
                } elseif ($v['model'] == 1) {
                    //按费率
                    $v['partner_money'] = $money * ($v['merchant_rate'] - $v['rate']) / 100;
                }
            } else {
                $v["partner_money"] = 0;
            }
            $v['total_money'] = $v["child_money"] + $v["partner_money"];
            $v["deserve_money"] = $money * ($v["merchant_rate"] - $v["agent_rate"]) / 100;
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
            $data["count"]["total_money"] += $v['total_money'];
            $data["count"]["deserve_money"] += $v['deserve_money'];
            $data["count"]["child_money"] += $v['child_money'];
            $data["count"]["partner_money"] += $v['partner_money'];
        }

        $data["pages"] = $pages;
        $data["pages"]['rows'] = $rows;

        /** 获取结算统计数据 */

        $data["index_count"]["pre_settle"] = abs($this->check_apply_money_by_month());
        $data["index_count"]["settled_num"] = TotalCapital::where(["agent_id" => $this->aid, "status" => 1])
            ->whereTime("settlement_time", ">", $this->time_limit)
            ->count("id");
        $data["index_count"]["pre_settle_num"] = (int)date("m", time()) - $data["index_count"]["settled_num"];
        $data["index_count"]["settled"] = TotalCapital::where(["agent_id" => $this->aid, "status" => 1])
            ->whereTime("settlement_time", ">", $this->time_limit)
            ->sum("settlement_money");

        check_data($data);
    }


    /**
     * 已结算记录
     * @param [query]  $time $status 0-未结款 1-已结款 2-暂缓处理
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_list(Request $request)
    {


        /**  时间查询 $rows */
        $time = $request->param("time");
        $status = $request->param("status");

        /** 搜索条件 */
        if (empty($time)) {
            $time_flag = ">";
            $time = 0;
        } else {
            $time_flag = "between";
        }

        if ($status == null) {
            $status_flag = "<>";
            $status = -2;
        } else {
            $status_flag = "eq";
        }
        /** @var [array] 准备查询条件 $query */
        $query = [
            "status" => [$status_flag, $status],
            "settlement_time" => [$time_flag, $time],
        ];
        $rows = TotalCapital::where("agent_id", $this->aid)
            ->where($query)
            ->count("id");
        $pages = page($rows);
        $data["list"] = TotalCapital::where("agent_id", $this->aid)
            ->where($query)
            ->limit($pages["offset"], $pages["limit"])
            ->select();

        $data["pages"] = $pages;
        $data["pages"]["rows"] = $rows;
        /** 已申请佣金 */
        $data["count"]["applied"] = $this->get_agent_sum($this->aid, 0);
        /** 已结算佣金 */
        $data["count"]["settled"] = $this->get_agent_sum($this->aid, 1);
        /** 待结算佣金 */
        $data["count"]["unsettle"] = $this->get_agent_sum($this->aid, 2);

        check_data($data["list"], $data);

    }

    /**
     * 子代结算记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function child_list(Request $request)
    {
        if ($request->isGet()) {
            $id = Session::get("username_")["id"];
            /** @var [string] 获取查询参数 $time */
            $time = $request->param("time");
            $keywords = $request->param("keywords");
            /** 搜索条件 */
            if (empty($time)) {
                $time_flag = ">";
                $time = 0;
            } else {
                $time_flag = "between";
                $firstday = (int)$time;
                $lastday = strtotime(date("Y-m-d", $firstday) . ' +1 month -1 day');
                $time = [$firstday, $lastday];
            }

            if ($keywords == null) {
                $keywords_flag = "<>";
                $keywords = -2;
            } else {
                $keywords_flag = "LIKE";
            }
            /** @var [array] 准备查询条件 $query */
            $query = [
                "a.agent_name" => [$keywords_flag, $keywords . "%"],
                "cap.settlement_start" => [$time_flag, $time],
            ];
            $rows = TotalAgent::alias("a")->where("a.parent_id", $id)
                ->where($query)
                ->join("cloud_total_capital cap", "cap.agent_id = a.id", "RIGHT")
                ->count("a.id");
            $pages = page($rows);
            $data["list"] = TotalAgent::alias("a")->where("a.parent_id", $id)
                ->join("cloud_total_capital cap", "cap.agent_id = a.id", "RIGHT")->where($query)
                ->where($query)
                ->limit($pages["offset"], $pages["limit"])
                ->select();

            $data["pages"] = $pages;
            $data["pages"]["rows"] = $rows;
            /** 已申请佣金 */
            $data["count"]["applied"] = $this->get_agent_sum($id, 0, 2);
            /** 已结算佣金 */
            $data["count"]["settled"] = $this->get_agent_sum($id, 1, 2);
            /** 待结算佣金 */
            $data["count"]["unsettle"] = $this->get_agent_sum($id, 2, 2);


            if (count($data["list"])) {
                return_msg(200, "success", $data);
            } else {
                return_msg(400, "没有数据");
            }
        }
    }

    protected function get_agent_sum($id, $status, $level = 1)
    {
        if ($level = 1) {
            $res = TotalCapital::where("agent_id = $id")
                ->where("status = $status")
                ->sum("settlement_money");
        } else {
            $res = TotalAgent::alias("a")->where("a.parent_id", $id)
                ->join("cloud_total_capital cap", "cap.agent_id = a.id", "RIGHT")
                ->sum("cap.settlement_money");
        }


        return $res;
    }

    /**
     * 时间查询
     * @param  [int]  $time  时间戳
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agent_time()
    {
        //获取时间
        $time = request()->param('time');

        if (empty($time)) {
            $time_flag = ">";
            $time = 0;
        } else {
            $time_flag = "between";
            $firstday = (int)$time;
            $lastday = strtotime(date("Y-m-d", $firstday) . ' +1 month -1 day');
            $time = [$firstday, $lastday];
        }

        //获取当前代理商id
        $this->aid = Session::get("username_")['id'];

        $join = [
            ['cloud_agent_partner b', 'a.partner_id=b.id'],
            ['cloud_total_agent c', 'a.agent_id=c.id'],
            ['cloud_order d', 'd.merchant_id=a.id']
        ];

        //mark
        /** 一级代理商 */
        $rows = TotalMerchant::alias("a")
            ->join($join)
            ->whereTime("pay_time", $time_flag, $time)
            ->where([
                "a.agent_id" => $this->aid,     //属于当前代理商的 商户
            ])
            ->group('a.id')
            ->count("a.id");

        $pages = page($rows);

        $data['list'] = TotalMerchant::alias('a')
            ->field(['a.id, a.name, b.partner_name, a.partner_id ,b.commission as partner_money, a.merchant_rate,c.agent_name,c.agent_rate,b.rate,b.proportion,b.model'])
            ->join($join)
            ->where([
                "a.agent_id" => $this->aid,     //属于当前代理商的 商户
            ])
            ->whereTime("pay_time", $time_flag, $time)
            ->group('a.id')
            ->limit($pages["offset"], $pages["limit"])
            ->select();


        if (!count($data['list'])) {
            return_msg(400, "没有数据");
        }
        $data["count"]["total_money"] = 0;
        $data["count"]["child_money"] = 0;
        $data["count"]["partner_money"] = 0;
        $data["count"]["deserve_money"] = 0;
        //取出商户支付宝和微信交易额交易量
        foreach ($data['list'] as &$v) {
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count('id');
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['id']
            ];
            //取出微信交易额
            $wxpay = Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number = Order::where($where1)->count();
            //计算佣金
            $money = $alipay + $wxpay;//总交易额

            /** 如果有子代理商 */
            if (!empty($v["agent_name"])) {

                $v["child_money"] = $money * (($v["merchant_rate"] - $v["agent_rate"]) / 100);    //子代理商佣金
            } else {
                $v["child_money"] = 0;
            }
//                $v['agent_money'] = $money * $v['merchant_rate'] / 100;  //
            /** 合伙人佣金 如果有合伙人*/
            if (!empty($v["partner_id"])) {
                if ($v['model'] == 0) {
                    //安比例
                    $v['partner_money'] = $money * $v['proportion'] / 100;
                } elseif ($v['model'] == 1) {
                    //按费率
                    $v['partner_money'] = $money * ($v['merchant_rate'] - $v['rate']) / 100;
                }
            } else {
                $v["partner_money"] = 0;
            }
            $v['total_money'] = $v["child_money"] + $v["partner_money"];
            $v["deserve_money"] = $money * ($v["merchant_rate"] - $v["agent_rate"]) / 100;
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
            $data["count"]["total_money"] += $v['total_money'];
            $data["count"]["deserve_money"] += $v['deserve_money'];
            $data["count"]["child_money"] += $v['child_money'];
            $data["count"]["partner_money"] += $v['partner_money'];
        }
        $data["pages"] = $pages;
        $data["pages"]['rows'] = $rows;

        /** 获取结算统计数据 */

        $data["index_count"]["pre_settle"] = $this->check_apply_money_by_month();
        $data["index_count"]["settled_num"] = TotalCapital::where(["agent_id" => $this->aid, "status" => 1])
            ->whereTime("settlement_time", ">", $this->time_limit)
            ->count("id");
        $data["index_count"]["pre_settle_num"] = (int)date("m", time()) - $data["index_count"]["settled_num"];
        $data["index_count"]["settled"] = TotalCapital::where(["agent_id" => $this->aid, "status" => 1])
            ->whereTime("settlement_time", ">", $this->time_limit)
            ->sum("settlement_money");

        return_msg(200, 'success', $data);
    }

    /**
     * 代理商申请结算
     * @param time invoice amount
     * @param [int] agent_id  代理商id 从session获取
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply_money(Request $request)
    {

        /* 申请前检测是否符合结算规则 */
//        $this->apply_rules(time(), $this->aid);
        $res = TotalAgent::get($this->aid);

        /** 检测是否超过结算次数 */
        $this->apply_limit($this->aid);

        if ($request->isGet()) {
            /** 申请结算操作 **/
            $query = $request->get();

            unset($query["id"]);
            /** 结算时间范围 **/
//            $query['time'] = $request->post('time');
            /** 有无发票 */
//            $query['invoice'] = $request->post('invoice');

            /** 可结算金额 */
            $query['settlement_money'] = $this->check_apply_money_by_month(time());
            $query['agent_id'] = $this->aid;
            $query['settlement_start'] = time();
            $query['date'] = time();
            $query["settlement_type"] = "银行";
            $query["account"] = $res["account"];
            $result = Db::name("total_capital")->insertGetId($query);

            if ($result) {
                /** 发送短信提醒 */
                $check_send = $this->send_msg_to_phone($res["agent_phone"], '申请');
                return_msg(200, "成功");
                /** 填加一次性的限制 */
//                $res = TotalAgent::update(["limit" => 0], ["id" => $this->aid]);
            } else {
                return_msg(400, '申请失败');
            }

        } else {

            $data = TotalAgent::field(['agent_name,agent_area,contact_person,agent_phone,account'])->where('id', $this->aid)->find();
            $data['create_time'] = time();

            return_msg(200, '成功', $data);
        }
    }

    /**
     *
     * 可结算金额
     * @description 无下级代理 f() total =（ 商户a费率-代理商费率） * 商户总交易额 +...  (商户n按费率-代理商费率） * 商户总交易额
     *              有下级代理 Func total =（ 商户a费率 - 代理商费率） * 总交易额 +...  (商户n按费率-代理商费率） * 总交易额  +
     *（二级代理商a - 代理商费率）* 二级代理商A的总交易额 +.. （二级代理商n - 代理商费率）* 二级代理商N的总交易额
     * @param $time [int] 时间戳 格式 ['1533345935','1538384935']
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return float total 总额
     */
    public function check_apply_money_by_month($time = null)
    {

        if (!$time) {
            $time_flag = ">";
            $time = $this->time_limit;
        } else {
            $time_flag = "between";
        }

        /** 时间区间 */
        $id = $this->aid;
        $res = TotalAgent::get($id);
        $agent_rate = TotalAgent::get($id)->getData()["agent_rate"];
        $merchant_total = [];
        $this->aid_arr = [];
        $sum = 0;
        $agent_sec_rate_arr = [];
        $per_sec_agent_merchant_id = [];
        /** 代理商费率 $agent_rate */


        /** 所有直接下属商户总交易额 $merchant_total */
        $result = TotalMerchant::alias('m')
            ->field('m.merchant_rate, o.received_money')
            ->join('cloud_order o', 'm.id = o.merchant_id')
            ->where([
                "m.agent_id" => $id,
            ])
            ->whereTime('pay_time', $time_flag, $time)
            ->select();

        foreach ($result as $v) {

            array_push($merchant_total, ((float)$v['merchant_rate'] - $agent_rate) / 100 * (float)$v['received_money']);
        }


        /** 如果parent_id  |0 -- 一级代理 | 二级代理 */
        if ($res->getData()['parent_id'] == 0) {
            /**  所有当前代理商的下属代理商id,agent_rate $second_level */
            $second_level = TotalAgent::all(["parent_id" => $id]);


            foreach ($second_level as $v) {

                array_push($this->aid_arr, $v->getData()["id"]);
                array_push($agent_sec_rate_arr, $v->getData()["agent_rate"]);
            }


            /** 当前代理商的下属代理商a/b/c的所有商户 $all_merchant */

            foreach ($this->aid_arr as $this->aid) {
                $per_sec_agent_merchant_id[] = TotalMerchant::field("m.id")->alias("m")
                    ->where("m.agent_id", "eq", $this->aid)
                    ->select();
            }


            $sec_merchant_total = [];
            foreach ($per_sec_agent_merchant_id as $v) {
                foreach ($v as $vv) {
                    /** 二级代理商a的总交易额 */
                    $sec_merchant_total[] = Order::where("merchant_id", $vv['id'])->sum("received_money");
                }
            }

            foreach ($sec_merchant_total as $k => $v) {
                $sum += ($agent_sec_rate_arr[$k] - (float)$agent_rate) * $sec_merchant_total[$k];
            }

            return array_sum($merchant_total) + $sum;

        } else {
            return array_sum($merchant_total);
        }
    }

    /**
     * 检验当前代理商是否已经申请过
     * @param $id [int]当前代理商id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function apply_limit($id)
    {
        $res = TotalAgent::field("limit")->where("id", $id)->find();
        if ($res['limit'] > 0) {
            return true;
        } else {
            return_msg(400, '申请结算失败，结算次数不足！');
        }
    }


    /**
     * 申请规则： 每月27日至10日可申请结算且时间段内仅限1次
     * @param [int] $now 当前的时间戳
     * @return string/bool  检测结果
     */
    protected function apply_rules($now, $id)
    {
        $date['year'] = intval(date('Y', $now));
        $date['month'] = intval(date('m', $now));
        $date['day'] = intval(date('d', $now));

        $prev_limit = mktime(0, 0, 0, $date['month'], 27, $date['year']);
        $after_limit = mktime(0, 0, 0, $date['month'] + 1, 10, $date['year']);

        if ($now >= $prev_limit && $now <= $after_limit) {
            TotalAgent::get($id)->setDec("limit", 1);
            return true;
        } else {
            return_msg(400, '申请失败，请在每月27日至下月10日之间申请。');
        }

    }


    /**
     * 发送短信到手机 -- [结算操作]
     * @param $phone
     * @param $msg
     */
    public function send_msg_to_phone($phone, $msg = '')
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend.json');
        //curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        //配置submail
        $data = [
            'appid' => '29499', //应用id
            'to' => $phone,     //要接受短信的电话
            'project' => "8hBHG4", //模板标识
            'vars' => "{'code': '" . $msg . "'}",
            'signature' => '759a042683c8fe0160f069c8dd8d577d', //应用签名
        ];

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($res);
        if ($res->status !== 'success') {
            return false;
        } else {
            return true;
        }

    }

    /**
     * 合伙人佣金结算
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function partner_list(Request $request)
    {

        $id = $this->aid;
        $time = $request->param("time");
        if (!$time) {
            $time_flag = ">";
            $time = $this->time_limit;
        } else {
            $time_flag = "between";
        }
        $map = [
            "create_time" => [$time_flag, $time]
        ];
        $rate = TotalAgent::get($id)->toArray()["agent_rate"];

        $rows = $data["list"] = AgentPartner::where(["agent_id" => $id])->where($map)->count("id");
        $pages = page($rows);
        $data["list"] = AgentPartner::where(["agent_id" => $id])
            ->where($map)
            ->field("id,partner_name,model,proportion,rate")
            ->limit($pages["offset"], $pages["limit"])
            ->select();

        check_data($data["list"], '', 0);

        $commission = 0;
        $partner_money = 0;
        $money = 0;
        $count = 0;
        foreach ($data["list"] as &$v) {

            /** 获取商户微信交易额 */
            $merchant["mer_wx_amount"] = $this->get_partner_amount($v["id"], "wxpay");

            /** 获取商户支付宝交易额 */
            $merchant["mer_ali_amount"] = $this->get_partner_amount($v["id"], "alipay");

            /** 获取商户微信交易量 */
            $merchant["mer_wx_num"] = $this->get_partner_num($v["id"], "wxpay");

            /** 获取商户支付宝交易量 */
            $merchant["mer_ali_num"] = $this->get_partner_num($v["id"], "alipay");

            $raw_data = TotalMerchant::all(["partner_id" => $v["id"]]);

            $merchant_rate = collection($raw_data)->toArray();
            /** 获取商户数量 */
            $merchant["mer_num"] = count($merchant_rate);

            if ($v['model'] == 0) {
                //按比例
                foreach ($merchant_rate as $j) {


                    /** 该合伙人下1个商户总交易额 */
                    $money = Order::where(["merchant_id" => $j["id"]])->sum("received_money");

                    $partner_money += $money * ($j["merchant_rate"] - $v["proportion"]) / 100;
                    $count += $money;
                }
                $commission = $count * ($v["proportion"] - $rate) / 100;
            } elseif ($v['model'] == 1) {
                //按费率
                foreach ($merchant_rate as $j) {
                    $money = Order::where(["merchant_id" => $j["id"]])->sum("received_money");

                    $partner_money += $money * ($j['merchant_rate'] - $v["rate"]) / 100;
                    $count += $money;
                }
                $commission = $count * ($v["rate"] - $rate) / 100;
            }

            /** 商户总交易额 */

            $v["mer_amount"] = $merchant["mer_ali_amount"] + $merchant["mer_wx_amount"];

            /** 合伙人佣金 */
            $v["partner_money"] = $partner_money;
            /** 所得佣金数 */
            $v["commission"] = $commission;
            /** 商户数 */
            $v["mer_num"] = $merchant["mer_num"];
            /** 微信交易额 */
            $v["mer_wx_amount"] = $merchant["mer_wx_amount"];
            /** 微信交易量 */
            $v["mer_wx_num"] = $merchant["mer_wx_num"];
            /** 支付宝交易量 */
            $v["mer_ali_num"] = $merchant["mer_ali_num"];
            /** 支付宝交易额 */
            $v["mer_ali_amount"] = $merchant["mer_ali_amount"];


        }

        $index_commission = 0;
        $index_deserve = 0;
        foreach ($data["list"] as $val) {
            $index_commission += $val["commission"];
            $index_deserve += $val["partner_money"];
        }
        $data["count"]["commission"] = $index_commission;
        $data["count"]["deserve"] = $index_deserve;
        $data["pages"] = $pages;

        return_msg(200, "success", $data);
    }


    /**
     * 子代时间搜索
     * @param [int] $time 时间戳
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function child_agent_search()
    {
        //获取时间
        $time = request()->param('time');

        /** 时间转换 */
        if (empty($time)) {
            $time_flag = ">";
            $time = 0;
        } else {
            $time_flag = "between";
            $firstday = (int)$time;
            $lastday = strtotime(date("Y-m-d", $firstday) . ' +1 month -1 day');
            $time = [$firstday, $lastday];
        }
        $join = [
            ["cloud_total_merchant mer", "mer.agent_id = a.id"],
            ["cloud_order o ", "o.merchant_id = mer.id"]
        ];

        $this->aid = Session::get('username_')["id"];


        /** @var [object] 检查是否具有权限 $res */
        $res = TotalAgent::get($this->aid);
        if ($res->getData()["parent_id"]) {
            return_msg(400, "没有查看权限");
        }

        $rows = TotalAgent::alias("a")->where(["a.parent_id" => $this->aid])
            ->join($join)
            ->whereTime("pay_time", $time_flag, $time)
            ->count("a.id");

        $pages = page($rows);
        /** 获取所有子代理商 */
        $data["list"] = TotalAgent::alias("a")->where(["a.parent_id" => $this->aid])
            ->field("a.id,a.agent_name,a.agent_rate")
            ->join($join)
            ->limit($pages["offset"], $pages["limit"])
            ->whereTime("pay_time", $time_flag, $time)
            ->select();

        if (!count($data["list"])) {
            return_msg(400, "没有数据");
        }

        $data["list"] = collection($data["list"])->toArray();
        $commission = 0;
        foreach ($data["list"] as &$v) {

            /** 获取商户数量 */
            $merchant["mer_num"] = TotalAgent::alias("ag")
                ->join("cloud_total_merchant mer", "mer.agent_id = ag.id")
                ->where("ag.id", $v["id"])
                ->count("mer.id");

            /** 获取商户微信交易额 */
            $merchant["mer_wx_amount"] = $this->get_type_amount($v["id"], "wxpay");

            /** 获取商户支付宝交易额 */
            $merchant["mer_ali_amount"] = $this->get_type_amount($v["id"], "alipay");

            /** 获取商户微信交易量 */
            $merchant["mer_wx_num"] = $this->get_type_num($v["id"], "wxpay");

            /** 获取商户支付宝交易量 */
            $merchant["mer_ali_num"] = $this->get_type_num($v["id"], "alipay");


            $raw_data = TotalMerchant::where(["agent_id" => $v["id"]])->field("merchant_rate,id")->select();

            $merchant_rate = collection($raw_data)->toArray();

            foreach ($merchant_rate as $j) {
                $commission += Order::where(["merchant_id" => $j["id"]])->sum("received_money") * ($j['merchant_rate'] - $v["agent_rate"]) / 100;

            }
            /** 商户总交易额 */

            $v["mer_amount"] = $merchant["mer_ali_amount"] + $merchant["mer_wx_amount"];

            //mark
            $v["commission"] = $commission;

            $v["mer_num"] = $merchant["mer_num"];
            $v["mer_wx_amount"] = $merchant["mer_wx_amount"];
            $v["mer_wx_num"] = $merchant["mer_wx_num"];
            $v["mer_ali_num"] = $merchant["mer_ali_num"];
            $v["mer_ali_amount"] = $merchant["mer_ali_amount"];

        }

        $data["pages"] = $pages;
        $data["pages"]["rows"] = $rows;


        return_msg(200, 'success', $data);


    }

    /**
     * 子代佣金统计
     *
     * @param  \think\Request $request
     * @return \think\Response
     * @throws Exception
     */
    public function agent_count(Request $request)
    {
        $this->aid = Session::get('username_')["id"];
        /** @var [object] 检查是否具有权限 $res */
        $res = TotalAgent::get($this->aid);
        if ($res->getData()["parent_id"]) {
            return_msg(400, "没有查看权限");
        }
        $rows = TotalAgent::where(["parent_id" => $this->aid])
            ->where("create_time")
            ->field("id,agent_name,agent_rate")
            ->count("id");

        $pages = page($rows);
        /** 获取所有子代理商 */
        $data["list"] = TotalAgent::where(["parent_id" => $this->aid])
            ->field("id,agent_name,agent_rate")
            ->limit($pages["offset"], $pages["limit"])
            ->select();

        if (!count($data["list"])) {
            return_msg(400, "没有数据");
        }

        $data["list"] = collection($data["list"])->toArray();
        $commission = 0;
        foreach ($data["list"] as &$v) {

            /** 获取商户数量 */
            $merchant["mer_num"] = TotalAgent::alias("ag")
                ->join("cloud_total_merchant mer", "mer.agent_id = ag.id")
                ->where("ag.id", $v["id"])
                ->count("mer.id");

            /** 获取商户微信交易额 */
            $merchant["mer_wx_amount"] = $this->get_type_amount($v["id"], "wxpay");

            /** 获取商户支付宝交易额 */
            $merchant["mer_ali_amount"] = $this->get_type_amount($v["id"], "alipay");

            /** 获取商户微信交易量 */
            $merchant["mer_wx_num"] = $this->get_type_num($v["id"], "wxpay");

            /** 获取商户支付宝交易量 */
            $merchant["mer_ali_num"] = $this->get_type_num($v["id"], "alipay");


            $raw_data = TotalMerchant::where(["agent_id" => $v["id"]])->field("merchant_rate,id")->select();

            $merchant_rate = collection($raw_data)->toArray();

            foreach ($merchant_rate as $j) {
                $commission += Order::where(["merchant_id" => $j["id"]])->sum("received_money") * ($j['merchant_rate'] - $v["agent_rate"]) / 100;

            }
            /** 商户总交易额 */

            $v["mer_amount"] = $merchant["mer_ali_amount"] + $merchant["mer_wx_amount"];

            //mark
            $v["commission"] = $commission;

            $v["mer_num"] = $merchant["mer_num"];
            $v["mer_wx_amount"] = $merchant["mer_wx_amount"];
            $v["mer_wx_num"] = $merchant["mer_wx_num"];
            $v["mer_ali_num"] = $merchant["mer_ali_num"];
            $v["mer_ali_amount"] = $merchant["mer_ali_amount"];

        }

        $data["pages"] = $pages;
        $data["pages"]["rows"] = $rows;


        return_msg(200, 'success', $data);
    }

    protected function get_type_num($id, $type)
    {
        $res = TotalAgent::alias("ag")
            ->join("cloud_total_merchant mer", "mer.agent_id = ag.id")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where("ag.id", $id)
            ->where("o.pay_type", $type)
            ->count("mer.id");
        return $res;
    }

    protected function get_type_amount($id, $type)
    {
        $res = TotalAgent::alias("ag")
            ->join("cloud_total_merchant mer", "mer.agent_id = ag.id")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where("ag.id", $id)
            ->where("o.pay_type", $type)
            ->sum("received_money");
        return $res;

    }

    protected function get_partner_num($id, $type)
    {
        $res = TotalAgent::alias("ap")
            ->join("cloud_total_merchant mer", "mer.partner_id = ap.id")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where("ap.id", $id)
            ->where("o.pay_type", $type)
            ->count("o.id");
        return $res;
    }

    protected function get_partner_amount($id, $type)
    {
        $res = AgentPartner::alias("ap")
            ->join("cloud_total_merchant mer", "mer.partner_id = ap.id")
            ->join("cloud_order o", "o.merchant_id = mer.id")
            ->where("ap.id", $id)
            ->where("o.pay_type", $type)
            ->sum("received_money");
        return $res;

    }

}
