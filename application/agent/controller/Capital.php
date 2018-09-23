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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取当前代理商id mark
        $agent_id = session('agent_id');
        $agent_id = 1;
        $join = [
            ['cloud_agent_partner b', 'a.partner_id=b.id'],
            ['cloud_total_agent c', 'a.agent_id=c.id'],
            ['cloud_order d', 'd.merchant_id=a.id']
        ];
        //获取总行数
        $rows = TotalMerchant::alias('a')
            ->join($join)
            ->where('a.agent_id', $agent_id)
            ->group('a.id')
            ->count();
        //分页
        $pages = page($rows);
        $data = TotalMerchant::alias('a')
            ->field(['a.id,a.name,b.partner_name,a.merchant_rate,c.agent_name,c.agent_rate,b.rate,b.proportion,b.model'])
            ->join($join)
            ->where('a.agent_id', $agent_id)
            ->group('a.id')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
//        dump($data);die;
        //取出商户支付宝和微信交易额交易量
        foreach ($data as &$v) {
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
            //计算佣金
            $money = $alipay + $wxpay;//总交易量
            //间联预估佣金=本代理商所负责商户的交易额*（代理商与商户约定的费率-平台与代理商约定的费率）
            $v['total_money'] = $money * (($v['merchant_rate'] - $v['agent_rate']) / 100);//所得佣金
            $v['agent_money'] = $money * $v['merchant_rate'] / 100;//代理商佣金
            if ($v['model'] == 0) {
                //安比例
                $v['partner_money'] = $money * $v['proportion'] / 100;
            } elseif ($v['model'] == 1) {
                //按费率
                $v['partner_money'] = $money * $v['rate'] / 100;
            }
            $v['alipay'] = $alipay;
            $v['alipay_number'] = $alipay_number;
            $v['wxpay'] = $wxpay;
            $v['wxpay_number'] = $wxpay_number;
        }
        $data['pages'] = $pages;

        return_msg(200, 'success', $data);
    }

    /**
     * 代理商申请结算
     * @param [int] agent_id  代理商id 从session获取
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply_money(Request $request)
    {
        // mark test
        // $agent_id = session('agent_id');
        $agent_id = $request->param('id');
        /**  agent_id不存在返回 */
        if (!$agent_id) {
            return_msg(400, 'id不存在');
        }

        /* 申请前检测是否符合结算规则 */
        $this->apply_rules(time(), $agent_id);

        /** 检测是否已经申请过 */
        $this->apply_limit($agent_id);

        if (request()->isPost()) {
            /** 申请结算操作 **/
            $data = request()->post();
            /** 结算时间范围 **/

            $data['time'] = request()->post('time') ? request()->post('time') : -2;
            /** 有无发票 */

            $data['invoice'] = request()->post('invoice') ? request()->post('time') : 0;

            /** 可结算金额 */

            $data['amount'] = $this->check_apply_money_by_month($data['time']);

            $result = TotalCapital::insert($data);
            if ($result !== false) {
                /** 填加一次性的限制 */
                $res = TotalAgent::update(["limit" => 0], ["id" => $agent_id]);
                if ($res['limit'] == 0) {
                    /** 发送短信提醒 */
                    //$check_send = $this->send_msg_to_phone(18670138762,'申请');

                    /** 检查是否发送成功 *//*$check_send*/
                    if(0 ) {
                        return_msg(200, '申请成功');
                    }else {
                        return_msg(400 , '短信发送失败');
                    }

                } else {
                    return_msg(400, '操作失败');
                }

            } else {
                return_msg(400, '申请失败');
            }

        } else {

            $data = TotalAgent::field(['agent_name,agent_area,contact_person,agent_phone,account'])->where('id', $agent_id)->find();
            $data['create_time'] = time();

            return_msg(200, 'success', $data);
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
    public function check_apply_money_by_month($time) {
//        $id = session("agent_id");
//        if (!empty($time)) {
//            return_msg();
//        }
        /** 时间区间 */
        $id = 3; //mark
        $res = TotalAgent::get($id)->field('parent_id')->find();
        $merchant_total = [];$agent_id_arr = []; $sum =0;$agent_sec_rate_arr = [];$per_sec_agent_merchant_id=[];
        /** 代理商费率 $agent_rate */
        $agent_rate = (float)TotalAgent::get($id)->field('agent_rate')->find()->toArray()['agent_rate'];

        /** 所有直接下属商户总交易额 $merchant_total */
        $result = TotalMerchant::field('m.merchant_rate, o.received_money')
            ->alias('m')->join('cloud_order o','m.id = o.merchant_id')
            ->where([
                "m.agent_id"  => $id,
            ])
//            ->whereTime('pay_time', 'between', [$time])
            ->select();
        foreach($result as $v) {
            array_push($merchant_total, ( (float)$v['merchant_rate'] - $agent_rate )/100 * (float)$v['received_money'] );
        }

        /** 如果存在parent_id为一级代理否则为二级代理 */
        if ($res->parent_id == 0) {
            /**  所有当前代理商的下属代理商id,agent_rate $second_level */
            $second_level = TotalAgent::field('id,agent_rate')->where("parent_id=$id")->select();
            foreach ($second_level as $v) {
                array_push($agent_id_arr, $v['id']);
                array_push($agent_sec_rate_arr, $v['agent_rate']);
            }

            /** 当前代理商的下属代理商a/b/c的所有商户 $all_merchant */

            foreach ($agent_id_arr as $agent_id) {
                $per_sec_agent_merchant_id[] = TotalMerchant::field("m.id")->alias("m")
                    ->where("m.agent_id","eq",$agent_id)
                    ->select();
            }


            $sec_merchant_total = [];
            foreach ($per_sec_agent_merchant_id as $v) {
                foreach($v as $vv) {
                    /** 二级代理商a的总交易额 */
                    $sec_merchant_total[] = Order::where("merchant_id",'eq',$vv['id'])->sum("received_money");
                }
            }
            for ($i =0; $i< count($agent_id_arr); $i++) {
                $sum += ($agent_sec_rate_arr[$i] - $agent_rate[$i] ) * $sec_merchant_total[$i];
            }

            return array_sum($merchant_total) + $sum;

        }else {
            return array_sum($merchant_total);
        }
    }
    /**
     * 检验当前代理商是否已经申请过
     * @param $id 当前代理商id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function apply_limit($id)
    {
        $res = TotalAgent::field("limit")->where("id", $id)->find();
        if ($res['limit'] == 1) {
            return true;
        } else {
            return_msg(400, '申请结算失败，一个时间段只能申请一次。');
        }
    }


    /**
     * 申请规则： 每月27日至10日可申请结算且时间段内仅限1次
     * @param [int] $now 当前的时间戳
     * @return string/bool  检测结果
     */
    protected function apply_rules($now,$id)
    {
        $date['year'] = intval(date('Y', $now));
        $date['month'] = intval(date('m', $now));
        $date['day'] = intval(date('d', $now));

        $prev_limit = mktime(0, 0, 0, $date['month'], 7, $date['year']);
        $after_limit = mktime(0, 0, 0, $date['month'] + 1, 10, $date['year']);

        if ($now >= $prev_limit && $now <= $after_limit) {
            return true;
        } else {
            /** 移除限制 */
            $res = TotalAgent::update(["limit" => 1], ["id" => $id]);
            if($res['limit'] !== 1) {
                return_msg(400, '操作失败');
            }
            return_msg(400, '申请失败，请在每月27日至下月10日之间申请。');
        }

    }


    /**
     * 发送短信到手机
     * @param $phone
     * @param $msg
     */
    public function send_msg_to_phone($phone, $msg)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend.json');
        //curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        //配置submail
        $data = [
            'appid' => '27075', //应用id
            'to'  => $phone,     //要接受短信的电话
            'project' => 'Jaayb', //模板标识
            'vars'  => '',
            'signature' => '5ac305ef38fb126d2a0ec5304040ab7d', //应用签名
        ];

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($res);
        if($res->status !== 'success') {
            return false;
        }else {
            return true;
        }

    }
    /**
     * 子代佣金统计
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function agent_count(Request $request)
    {
        $agent_id = session('agent_id');
        $agent_id = 1;
        $data = TotalAgent::alias('a')
            ->field('a.id,a.agent_name,b.id merchant_id')
            ->join('cloud_total_merchant b', 'a.id=b.agent_id')
            ->join('cloud_order c', 'b.id=c.merchant_id')
            ->where('a.parent_id', $agent_id)
            ->group('a.id')
            ->select();
        foreach ($data as &$v) {
            $v['count'] = TotalMerchant::where('agent_id', $v['id'])->count();
            //计算微信交易额和支付宝交易额
            //根据商户id获取交易额
            $where = [
                'status' => 1,
                'pay_type' => 'alipay',
                'merchant_id' => $v['merchant_id']
            ];
            $alipay = Order::where($where)->sum('received_money');
            $alipay_number = Order::where($where)->count();
            $where1 = [
                'status' => 1,
                'pay_type' => 'wxpay',
                'merchant_id' => $v['merchant_id']
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
//        dump($data);die;
        return_msg(200, 'success', $data);
    }


}
