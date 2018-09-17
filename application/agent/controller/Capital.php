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
//        $agent_id = session('agent_id');
        $agent_id = $request->param('id');
        /**  agent_id不存在返回 */
        if (!$agent_id) {
            return_msg(400, '操作异常。');
        }

        /* 申请前检测是否符合结算规则 */
        $this->apply_rules(time(), $agent_id);

        /** 检测是否已经申请过 */
        $this->apply_limit($agent_id);

        if (request()->isPost()) {
            /** 申请结算操作 **/
            $data = request()->post();
            $data['amount'] = request()->post('amount') ? request()->post('amount') : -2;
            /** 结款时间 **/
            $data['settlement_time'] = request()->post('time') ? request()->post('time') : -2;
            /** 检测必需参数 **/
            $data['agent_id'] = intval($agent_id);
            /** 查找员工费率 */

            $result = TotalCapital::insert($data);
            if ($result != false) {
                /** 填加一次性的限制 */
                $res = TotalAgent::update(["limit" => 0], ["id" => $agent_id]);
                if ($res['limit'] == 0) {
                    /** 发送短信提醒 */
                    $check_send = $this->send_msg_to_phone(18670138762,'申请');

                    /** 检查是否发送成功 */
                    if($check_send) {
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

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
