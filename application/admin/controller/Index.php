<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use app\admin\model\TotalMerchant;
use app\agent\model\Order;
use think\Controller;
use think\Db;
use think\Request;
use think\Validate;
use app\admin\model\TotalMerchant as Merchant;
use app\admin\model\Index as indexModel;
use think\Loader;

/**
 * Class Index
 * @package app\index\controller
 */
class Index extends Controller
{
    protected $params = [];
    protected $query = [];


    /*
     *  获取首页统计数据
     *  @param $start_time [int][string] 开始时间 时间戳
     *  @param $end_time  [int]  结束时间 时间戳
     *  @return $data     [json] 筛选后的数据
     * */

    public function index(Request $request)
    {
        //return view();
        $past = $request->param('start_time') ? $request->param('start_time') : "yesterday";
        $present = $request->param('end_time') ? $request->param('end_time') : null;
        //$channel = $request->param('channel') ? $request->param('end_time') : -1;


        /* 获取所有商户、代理商的数量 */
        $agent = Db::name('total_agent')->count('id');
        $merchant = Db::name('total_merchant')->count('id');


        /* 获取昨日全部的交易总额 */
        if ($past == "yesterday") {
            $total = Db::name('order')->whereTime('pay_time', "yesterday")->sum('received_money');
            $total_num = Db::name('order')->whereTime('pay_time', "yesterday")->count('id');
            $wxpay = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'wxpay'])->sum('received_money');
            $wxpay_num = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'wxpay'])->count('id');
            $alipay = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'alipay',])->sum('received_money');
            $alipay_num = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'alipay'])->count('id');
            $etc = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'etc'])->sum('received_money');
            $etc_num = Db::name('order')->whereTime('pay_time', "yesterday")->where(['pay_type' => 'etc'])->count('id');

        } else {
            $time_flag = "between";
            $total = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->sum('received_money');
            $total_num = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->count('id');
            $wxpay = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'wxpay'])->sum('received_money');
            $wxpay_num = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'wxpay'])->count('id');
            $alipay = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'alipay',])->sum('received_money');
            $alipay_num = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'alipay'])->count('id');
            $etc = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'etc'])->sum('received_money');
            $etc_num = Db::name('order')->whereTime('pay_time', $time_flag, [$past, $present])->where(['pay_type' => 'etc'])->count('id');

        }


        /** 返回昨日新增商户/代理商 */
        $merchant_num = Db::name("total_merchant")->whereTime("opening_time", "yesterday")->count("id");
        $agent_num = Db::name("total_agent")->whereTime("create_time", "yesterday")->count("id");
        /** 活跃商户数 */
        $active_merchant = Order::field("merchant_id")->whereTime("pay_time", "yesterday")->select();
        $active_arr = [];
        foreach ($active_merchant as $v) {
            array_push($active_arr, $v->merchant_id);
        }
        $data["active_merchant"] = count(array_unique($active_arr));
        /* 组装数据 */
        $data['new_merchant'] = $merchant_num;
        $data['merchant'] = $merchant;
        $data['new_agent'] = $agent_num;
        $data['agent'] = $agent;
        $data['wxpay'] = $wxpay;
        $data['wxpay_num'] = $wxpay_num;
        $data['alipay'] = $alipay;
        $data['alipay_num'] = $alipay_num;
        $data['etc'] = ['amount' => $etc, 'pay_num' => $etc_num];
        $data['total'] = ['amount' => $total / 10000, 'pay_num' => $total_num / 10000];

        return json_encode($data);
    }


    /*
     * 获取平台、代理商、渠道分润数据
     * @param time [int] 时间戳
     * @param id [int] 当前登录用户id
     * mark
     * */
    public function get_profit(Request $request)
    {

        /* 检验参数合法性 */
        $time = $request->param("time") ? $request->param("time") : -2;

        if ($time < -1) {
            $time = ['yesterday', 'today'];
        } else {
            $time = json_decode($time);
        }


//        $id = $request->param('id');
        /** 从session中获取id */
        $id = session("id");

//        $channel = $request->param('channel');
        /* 检查用户是否有权限查看 */
        $check = is_user_can($id);

        if ($check) {
            $total = Db::name('order')
                ->whereTime("pay_time", "between", $time)
                ->sum('received_money');

            $bank_rate = 0.000235;   //银行对平台费率
            /** 渠道分润 */
            $channel_profit = (float)$total * $bank_rate;
            /** 平台分润 */
            $merchant_arr = [];
            $agent_total = 0;
            $agent_arr = TotalAgent::column("id,agent_rate");
            foreach ($agent_arr as $k => $v) {

                $merchant_arr[$k] = TotalMerchant::alias("m")
                    ->where("agent_id", "eq", $k)
                    ->join("order o", "m.id = o.merchant_id")
                    ->whereTime("pay_time", "between", $time)
                    ->sum("received_money");
            }
            foreach ($agent_arr as $k => $rate) {
                $agent_total += $merchant_arr[$k] * $rate / 100;
            }
            $platform_profit = (float)$agent_total - $channel_profit;

            /** 代理商分润 */

            $merchant_rate = TotalMerchant::column("id,merchant_rate");
            $merchant_total = [];
            foreach ($merchant_rate as $k => $rate) {
                $merchant_total[] = Order::where("merchant_id = $k")
                        ->whereTime("pay_time", "between", $time)
                        ->sum("received_money") * $rate / 100;

            }

            $merchant_total = array_sum($merchant_total);
            $agent_profit = (float)$merchant_total - (float)$agent_total;

            /** 组合数据 */

            $data['platform_profit'] = $platform_profit;
            $data['channel_profit'] = $channel_profit;
            $data['agent_profit'] = $agent_profit;
            return $data;
        } else {
            $this->return_msg(400, '当前用户不可以查看！');
        }


    }


    /**
     * 运营后台代理商管理搜索
     * @param Request $request [array]
     * @param  name  [string] 模糊关键字
     * @param contract_time [int]  例： 合同有限期选择为 30天内 传来 现在的时间戳+30天的时间戳
     * @param channel [int]  如果直联间联都选择，传——3，直联——1，间连——2
     * @description 联系人/商户名称/联系电话
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_agent(Request $request)
    {
        /* 搜索条件项 */

        $this->query['keywords'] = $request->param('keywords');
        $this->query['status'] = $request->param('status');
        $this->query['agent_area'] = $request->param('agent_area');
        $this->query['contract_time'] = $request->param('contract_time');


        /* 传入参数并返回数据 */
        $this->get_search_result($this->query);

    }


    /**
     * @param $db [string] 要查找的数据表
     * @param array $param [搜索参数]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return [json] 搜索结果
     */
    public function get_search_result(Array $param)
    {
        /* 判断参数是否为默认，默认则不进入查询 */
        if (empty($param['keywords'])) {
            $param['keywords_flag'] = '<>';
            $param['keywords'] = '-2';
        } else {
            $param['keywords_flag'] = 'like';
        }

        if (empty($param['status'])) {
            $param['status_flag'] = '<>';
            $param['status'] = '-2';
        } else {
            $param['status_flag'] = 'eq';

        }

        if (empty($param['agent_area'])) {
            $param['agent_area_flag'] = '<>';
            $param['agent_area'] = '-2';
        } else {
            $param['agent_area_flag'] = 'eq';
        }

        switch ($param['contract_time']) {
            case -2:
                $param['contract_time_flag'] = '>';
                break;
            case 'expired':
                $param['contract_time_flag'] = '<';
                $param['contract_time'] = time();
                break;
            default:
                $param['contract_time_flag'] = '>=';
        }


        $total = Db::name('total_agent')->count('id');
        //查询有多少条数据
        $rows = Db::name('total_agent')
            ->where([
                'agent_name|contact_person|agent_phone' => [$param['keywords_flag'], $param['keywords'] . "%"],
                'status' => [$param['status_flag'], $param['status']],
                'agent_area' => [$param['agent_area_flag'], $param['agent_area']],
            ])
            ->whereTime('contract_time', $param['contract_time_flag'], $param['contract_time'])
            ->count('id');

        $pages = page($rows);
        /* 根据查询条件获取数据并返回 */
        $res['data'] = Db::name('total_agent')
            ->where([
                'agent_name|contact_person|agent_phone' => [$param['keywords_flag'], $param['keywords'] . "%"],
                'status' => [$param['status_flag'], $param['status']],
                'agent_area' => [$param['agent_area_flag'], $param['agent_area']],
            ])
            ->whereTime('contract_time', $param['contract_time_flag'], $param['contract_time'])
            ->field(['agent_name', 'contact_person', 'agent_mode', 'agent_area', 'admin_id', 'create_time', 'contract_time', 'status', 'id'])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $res['pages'] = $pages;

        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            $this->return_msg(200, '搜索结果', $res);
        } else {
            $this->return_msg(400, '没有数据');
        }

    }


    /**
     * 商户搜索 —— 正常营业商户
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function vendor_search(Request $request)
    {
        /* 搜索条件项 */
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['category'] = $request->param('category') ? $request->param('category') : -2;
        $query['address'] = $request->param('address') ? $request->param('address') : -2;
        $query['time'] = $request->param('time') ? json_decode($request->param('time')) : -2;


        $this->get_vendor_search_res($query);

    }


    /**
     * @param array $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_vendor_search_res(Array $param)
    {

        /* 初始化 *_flag */


        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        } else {
            $param['keywords_flag'] = 'like';
        }

        if ($param['category'] < -1) {
            $param['category_flag'] = '<>';
        } else {
            $param['category_flag'] = 'eq';
        }

        if ($param['address'] < -1) {
            $param['address_flag'] = '<>';
        } else {
            $param['address_flag'] = 'eq';
        }

        /* 前端参数 JSON.stringfy([xxx,xxx]) */
        switch (gettype($param['time'])) {
            case 'array':
                $param['time_flag'] = 'between';
                break;
            default:
                $param['time_flag'] = '>';
        }
        $total = Db::name('total_merchant')->count('id');
        /* 条件搜索查询有N条数据 */
        $rows = Db::name('total_merchant')->alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], "%" . $param['keywords'] . "%"],
                'm.category' => [$param['category_flag'], $param['category']],
                'm.address' => [$param['address_flag'], $param['address']],
            ])
            ->whereTime('opening_time', $param['time_flag'], $param['time'])
            ->field(['m.name', 'm.contact', 'm.status', 'm.channel', 'm.address', 'm.opening_time', 'a.agent_name', 'm.id', 'a.agent_phone'])
            ->join('cloud_total_agent a', 'm.agent_id=a.id', 'left')
            ->count('m.id');

        $pages = page($rows);
        /* 根据查询条件获取数据并返回 */
        $res = Db::name('total_merchant')->alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], $param['keywords'] . "%"],
                'm.category' => [$param['category_flag'], $param['category']],
                'm.address' => [$param['address_flag'], $param['address']],
            ])
            ->whereTime('opening_time', $param['time_flag'], $param['time'])
            ->field(['m.name', 'm.contact', 'm.status', 'm.channel', 'm.address', 'm.opening_time', 'a.agent_name', 'm.id', 'a.agent_phone'])
            ->join('cloud_total_agent a', 'm.agent_id=a.id', 'left')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $res['pages'] = $pages;

        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            $this->return_msg(200, '获取搜索结果成功', $res);
        } else {
            $this->return_msg(400, '没有找到数据');
        }
    }


    /**
     *                 直联商户搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function direct_connect_search(Request $request)
    {

        /* 直联待审核商户搜索项目 */
        $query['channel'] = [0, 1, 2];
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['review_status'] = $request->param('review_status') ? $request->param('review_status') : -2;
        $query['status'] = $request->param('status') ? $request->param('status') : -2;
        $query['create_time'] = $request->param('create_time') ? $request->param[('create_time')] : -2;

        $this->get_direct_connect_res($query);
    }

    /**
     * 间联商户搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indirect_connect_search(Request $request)
    {

        /* 直联待审核商户搜索项目 */
        $query['channel'] = [3];
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['review_status'] = $request->param('review_status') ? $request->param('review_status') : -2;
        $query['status'] = $request->param('status') ? $request->param('status') : -2;
        $query['create_time'] = $request->param('create_time') ? $request->param[('create_time')] : -2;

        $this->get_direct_connect_res($query);
    }

    /**
     * 直联待审核商户搜索过滤并返回结果
     * @param array $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_direct_connect_res(Array $param)
    {
        /* 过滤参数 设置flag*/

        $param['channel_flag'] = 'IN';
        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        } else {
            $param['keywords_flag'] = 'like';
        }
        if ($param['review_status'] < -1) {
            $param['review_status_flag'] = '<>';
        } else {
            $param['review_status_flag'] = 'eq';
        }
        if ($param['status'] < -1) {
            $param['status_flag'] = '<>';
        } else {
            $param['status_flag'] = 'eq';
        }
        if ($param['create_time'] < -1) {
            $param['create_time_flag'] = '>';
        } else {
            $param['create_time_flag'] = 'between';
        }

        $total = Merchant::all()->count('id');
        /* 条件搜索查询有N条数据 */
        $rows = Merchant::alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], $param['keywords'] . "%"],
                'm.review_status' => [$param['review_status_flag'], $param['review_status']],
                'm.status' => [$param['status_flag'], $param['status']],
//                'm.channel'     => [$param['channel_flag'], $param['channel']],
            ])
            ->whereTime('m.create_time', $param['create_time_flag'], $param['create_time'])
            ->field(['m.name', 'm.contact', 'm.phone', 'm.review_status', 'm.channel', 'm.address', 'a.agent_name', 'm.id', 'a.agent_phone', 'm.create_time', 'm.channel'])
            ->join('cloud_total_agent a', 'm.agent_id=a.id', 'left')
            ->count('m.id');

        $pages = page($rows);
        /* 根据查询条件获取数据并返回 */
        $res = Merchant::alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], $param['keywords'] . "%"],
                'm.review_status' => [$param['review_status_flag'], $param['review_status']],
                'm.status' => [$param['status_flag'], $param['status']],
//                'm.channel'     => [$param['channel_flag'], $param['channel']],
            ])
            ->whereTime('m.create_time', $param['create_time_flag'], $param['create_time'])
            ->field(['m.name', 'm.contact', 'm.phone', 'm.review_status', 'm.address', 'a.agent_name', 'm.id', 'a.agent_phone', 'm.create_time'])
            ->join('cloud_total_agent a', 'm.agent_id=a.id', 'left')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $res['pages'] = $pages;

        $res['pages']['total_row'] = $total;

        /* 过滤取出的数据 */

        if ($rows !== 0) {
            $this->return_msg(200, '获取搜索结果成功', $res);
        } else {
            $this->return_msg(400, '没有找到数据');
        }

    }

    /**
     * @param Request $request
     * @param [string]  $pay_type 支付类型
     * @return [json] $data 返回数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function echarts(Request $request)
    {

        /* 接受参数 */
        $pay_type = $request->param('pay_type');

        if (empty($pay_type)) {
            $pay_type_flag = "<>";
            $pay_type = '-2';
        } else {
            $pay_type_flag = "eq";
        }

        $i = -7; $sum = [];
        //make
        while($i < 0) {
            $past = date('Y-m-d', strtotime($i .' days'));
            $data['chartData'][] = Db::name('order')
                ->where("pay_type", $pay_type_flag, $pay_type)
                ->whereTime('pay_time', $past)
                ->sum("received_money");
            $sum[] = Db::name("order")
                ->where("pay_type", $pay_type_flag, $pay_type)
                ->whereTime('pay_time', $past)
                ->count("id");

            $i++;
        }
        foreach($data["chartData"] as $k => $v) {
            $filted_data[] = [
                "amount" => $v,
                "count"    => $sum[$k],
                "pay_time" => date('Y-m-d', strtotime(-7 + $k . ' days')),
            ];
        }


        if (count($filted_data) > 0) {
            $this->return_msg(200, '成功取出数据', $filted_data);
        } else {
            $this->return_msg(400, '没有数据');
        }

    }

    /**
     * 员工搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_staff(Request $request)
    {

        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['status'] = $request->param('status');
        if ($query['status'] == -1) {
            $query['status'] = 0;
        }

        $this->get_staff_result($query);
    }

    /**
     * 员工搜索结果
     * @param array $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function get_staff_result(Array $param)
    {
        /* 过滤参数 */
        $param['status'] = intval($param['status']);
        /* 设置flag */
        $param['keywords_flag'] = 'LIKE';
        $param['status_flag'] = "eq";

        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        }
        if ($param['status'] != 1 && $param['status'] != 0) {
            $param['status_flag'] = '<>';
        }

        /**
         *
         *
         * 根据条件SQL搜索
         *
         *
         */
        /* 总共有N条数据 */
        $total = indexModel::name('total_admin')->count('id');
        /* 查询结果 */
        $rows = indexModel::name('total_admin')
            ->field("name,phone,status,create_time,role_id,id")
            ->where([
                "name|phone" => [$param['keywords_flag'], $param['keywords'] . "%"],
                "status" => [$param['status_flag'], $param['status']],
            ])
            ->count("id");
        $pages = page($rows);

        $res['list'] = indexModel::name('total_admin')
            ->field("name,phone,status,create_time,role_id,id")
            ->where([
                "name|phone" => [$param['keywords_flag'], $param['keywords'] . "%"],
                "status" => [$param['status_flag'], $param['status']],
            ])
            ->limit($pages['offset'], $pages['limit'])
            ->select();


        $res['pages'] = $pages;
        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            return_msg(200, '获取搜索结果成功', $res);
        } else {
            return_msg(400, '没有找到数据');
        }
    }



    public function return_msg($code, $msg = '', $data = [])
    {
        /* 组合数据 */
        $return_data['code'] = $code;
        $return_data['msg'] = $msg;
        $return_data['data'] = $data;
        /* ---------返回信息并终止脚本---------- */

        echo json_encode($return_data);
        die;
    }

    public function upload_img()
    {
        $file = request()->file('file');
        //移动图片
        $info = $file->validate(['size' => 5 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        if ($info) {
            //文件上传成功
            //获取文件路径
            $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
            $goods_logo = str_replace('\\', '/', $goods_logo);
            $this->return_msg(200, 'success', $goods_logo);
        } else {
            $error = $file->getError();
            $this->return_msg(400, $error);
        }
    }

    /**
     * 接收星POS给的消息
     * @param null $param
     */
    public function callback($param = null)
    {
        $Order =new Order();
        if (empty($param)) {
            return_msg(400, "fail");
        }
        /** @var [array] 接收参数 $data */
        if (isset($param["TxnCode"]) && ($param["TxnCode"] == "L020" || $param["TxnCode"] == "L030") ) {
            /** 银行卡消费 */
            $data["pay_type"] = "etc";
        } elseif (isset($param["PayChannel"])) {
            switch ($param["PayChannel"]) {

                case 1:
                    $data["pay_type"] = "alipay";
                    break;
                case 2:
                    $data["pay_type"] = "wxpay";
                    break;
            }
        }
        $data = [
            "pay_time" => strtotime($param["TxnDate"] + $param["TxnTime"]),    //结算日期
            "merId"  =>        $param["AgentId"],    // 合作商号
            "orderNo"  =>   $param["logNo"],  //交易流水号
            "received_money" => $param["TxnAmt"],  //实际金额
            "order_money" => $param["TxnAmt"],  //交易金额
            "status"      => $param["TxnStatus"], //交易状态
            "order_remark"    => $param["attach"],  //附加信息
        ];

        /** 根据流水号，判断要更新哪个订单 */

        /** @var [int] $res */
        $res = $Order->where("LogNo", $param["logNo"])->save($data);
        if ($res) {
            return_msg(200, "success");
        }else {
            return_msg(400, "fail");
        }

    }

}