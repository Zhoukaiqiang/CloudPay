<?php

namespace app\admin\controller;


use MongoDB\Driver\ReadConcern;
use think\Controller;
use think\Db;
use think\Request;
use think\Validate;

/**
 * Class Index
 * @package app\index\controller
 */
class Index extends Controller
{

    protected $params = [];
    protected $query = [];
    protected $rules = array(
        "Index" => [
            "get_profit" => [
                'id' => 'require|number',
                'channel' => 'require|number'
            ],
            'echarts' => [
                'pay_type' => 'require',
            ],
            'search_agent' => [
                'channel' => 'number',
                'status' => 'number',
                'contact_time' => 'number',
            ],
        ],
    );

    /*
     *  获取首页统计数据
     *  @param $start_time [int][string] 开始时间 时间戳
     *  @param $end_time  [int]  结束时间 时间戳
     *  @return $data     [json] 筛选后的数据
     * */


    public function index(Request $request)
    {
        //return view();
        $start_time = $request->param('start_time') ? $request->param('start_time') : intval((time() - 100000));
        $end_time = $request->param('end_time') ? $request->param('end_time') : null;
        $channel = $request->param('channel') ? $request->param('end_time') : -1;
        /* 可选取区间为最近2个月 */
        if (time() - $start_time > 5604000) {
            $this->return_msg(400, '您选择的时间大于两个月，请重新选择！');
        }
        /* 获取所有商户、代理商的数量 */
        $agent = Db::name('total_agent')->count('id');
        $user = Db::name('user')->count('id');


        /* 获取昨日全部的交易总额 */
        if (empty($channel) || $channel == -1) {
            $total = Db::name('order')->whereTime('create_time', "yesterday")->sum('order_money');
            $total_num = Db::name('order')->whereTime('create_time', "yesterday")->count('id');
            $wxpay = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'wxpay'])->sum('order_money');
            $wxpay_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'wxpay'])->count('id');
            $alipay = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'alipay',])->sum('order_money');
            $alipay_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'alipay'])->count('id');
            $etc = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'etc'])->sum('order_money');
            $etc_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'etc'])->count('id');
        } else {
            /* 获取昨天 直联/间联 交易总额 */
            $total = Db::name('order')->whereTime('create_time', "yesterday")->where('channel', $channel)->sum('order_money');
            $total_num = Db::name('order')->whereTime('create_time', "yesterday")->where('channel', $channel)->count('id');
            $wxpay = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'wxpay', 'channel' => $channel])->sum('order_money');
            $wxpay_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'wxpay', "channel" => $channel])->count('id');
            $alipay = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'alipay', "channel" => $channel])->sum('order_money');
            $alipay_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'alipay', "channel" => $channel])->count('id');
            $etc = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'etc', "channel" => $channel])->sum('order_money');
            $etc_num = Db::name('order')->whereTime('create_time', "yesterday")->where(['pay_type' => 'etc', "channel" => $channel])->count('id');

        }

        /* 组装数据 */
        $data['agent'] = $agent;
        $data['user'] = $user;
        $data['wxpay'] = ['amount' => $wxpay, 'pay_num' => $wxpay_num];
        $data['alipay'] = ['amount' => $alipay, 'pay_num' => $alipay_num];
        $data['etc'] = ['amount' => $etc, 'pay_num' => $etc_num];
        $data['total'] = ['amount' => $total, 'pay_num' => $total_num];

        return json_encode($data);
    }


    /*
     * 获取平台、代理商、渠道分润数据 根据直联（1），间联（2）， 全部（-1）
     * @param id [int] 当前登录用户id
     *
     * */
    public function get_profit(Request $request)
    {

        /* 检验参数合法性 */
        $this->check_params($this->request->except(['time', 'token']));

        $id = $request->param('id');
        $channel = $request->param('channel');
        /* 检查用户是否有权限查看 */
        $check = $this->is_user_can($id);
        if ($check) {
            switch ($channel) {
                case -1:
                    $total = Db::name('order')->whereTime('create_time', 'yesterday')->sum('order_money');
                    break;
                case 1:
                    $total = Db::name('order')->whereTime('create_time', 'yesterday')->where('channel', 1)->sum('order_money');
                    break;
                case 2:
                    $total = Db::name('order')->whereTime('create_time', 'yesterday')->where('channel', 2)->sum('order_money');
                    break;
            }


            $agent_rate = 1; //平台对代理商费率

            $user_rete = 1;    //代理商对商户费率
            $bank_rate = 0.235;   //银行对平台费率
            $agent_profit = 1;
            $channel_profit = $total * $bank_rate;
            $platform_profit = $total * ($agent_profit) - $channel_profit;
        } else {
            $this->return_msg(400, '当前用户不可以查看！');
        }


    }

    /**
     * 检查用户是否有权限查看
     * @param id [int] 用户id
     * @rule  is_super_vip [int] 1:超级管理员 2：运营专员 ...
     * @return    [boolean]  返回true / 结束
     */
    protected function is_user_can($id)
    {
        /* 检查用户是否存在数据库 */
        $result = Db::name("total_admin")->where("id", $id)->value(['is_super_vip']);
        switch ($result) {
            case 1:
                return true;
                break;
            case 2:
                return false;
                break;
            default:
                $this->return_msg(400, '当前用户不存在！');
                break;
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
    public function search_agent(Request $request) {
        /* 搜索条件项 */
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['channel'] = $request->param('channel') ? $request->param('channel'): -2;
        $query['status'] = $request->param('status') ? $request->param('status') : -2;
        $query['agent_area'] = $request->param('agent_area') ? $request->param('agent_area'): -2;
        $query['contract_time'] = $request->param('contract_time') ? $request->param('contract_time') : -2;


        /* 传入参数并返回数据 */
        $this->get_search_result($query);

    }


    /**
     * @param $db   [string] 要查找的数据表
     * @param array $param   [搜索参数]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return [json] 搜索结果
     */
    public function get_search_result(Array $param) {
        /* 判断参数是否为默认，默认则不进入查询 */
        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        }else {
            $param['keywords_flag'] = 'like';
        }

        if ($param['status'] < -1) {
            $param['status_flag'] = '<>';
        }else {
            $param['status_flag'] = 'eq';
        }

        if ($param['channel'] < -1) {
            $param['channel_flag'] = '<>';
        }else {
            $param['channel_flag'] = 'eq';
        }

        if ($param['agent_area'] < -1) {
            $param['agent_area_flag'] = '<>';
        }else {
            $param['agent_area_flag'] = 'eq';
        }

        switch ($param['channel']) {
            case -2:
                $param['channel_flag'] = '<>';
                break;
            default:
                $param['channel_flag'] = 'eq';
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
                'agent_name|contact_person|agent_phone' => [$param['keywords_flag'], $param['keywords']."%"],
                'status'     => [$param['status_flag'], $param['status']],
                'agent_mode'     => [$param['channel_flag'], $param['channel']],
                'agent_area'     => [$param['agent_area_flag'], $param['agent_area']],
            ])
            ->whereTime('contract_time', $param['contract_time_flag'], $param['contract_time'])
            ->count('id');

        $pages = page($rows);
        /* 根据查询条件获取数据并返回 */
        $res = Db::name('total_agent')
            ->where([
                'agent_name|contact_person|agent_phone' => [$param['keywords_flag'], $param['keywords']."%"],
                'status'     => [$param['status_flag'], $param['status']],
                'agent_mode'     => [$param['channel_flag'], $param['channel']],
                'agent_area'     => [$param['agent_area_flag'], $param['agent_area']],
            ])
            ->whereTime('contract_time', $param['contract_time_flag'], $param['contract_time'])
            ->field(['agent_name','contact_person', 'agent_mode', 'agent_area', 'admin_id','create_time','contract_time','status','id'])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $res['pages'] = $pages;
        $res['pages']['rows'] = $rows;
        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            $this->return_msg(200, '搜索结果', $res);
        }else {
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
    public function vendor_search(Request $request) {
        /* 搜索条件项 */
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['category'] = $request->param('category') ? $request->param('category'): -2;
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
    public function get_vendor_search_res(Array $param) {

        /* 初始化 *_flag */

        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        }else {
            $param['keywords_flag'] = 'like';
        }

        if ($param['category'] < -1) {
            $param['category_flag'] = '<>';
        }else {
            $param['category_flag'] = 'eq';
        }

        if ($param['address'] < -1) {
            $param['address_flag'] = '<>';
        }else {
            $param['address_flag'] = 'eq';
        }

        /* 前端参数 JSON.stringfy([xxx,xxx]) */
        switch (gettype($param['time'])) {
            case 'integer':
                $param['time_flag'] = '>';
                break;
            case 'array':
                $param['time_flag'] = 'between';
                break;
            default:
                $param['time_flag'] = 'between';
        }
        $total = Db::name('total_merchant')->count('id');
        /* 条件搜索查询有N条数据 */
        $rows = Db::name('total_merchant')->alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], $param['keywords']."%"],
                'category'     => [$param['category_flag'], $param['category']],
                'address'     => [$param['address_flag'], $param['address']],
            ])
            ->whereTime('opening_time', $param['time_flag'], $param['time'])
            ->field(['m.name','m.contact', 'm.status', 'm.channel', 'm.address','m.opening_time','a.agent_name','m.id', 'a.agent_phone'])
            ->join('cloud_total_agent a','m.agent_id=a.id', 'left')
            ->count('m.id');

        $pages = page($rows);
        /* 根据查询条件获取数据并返回 */
        $res = Db::name('total_merchant')->alias('m')
            ->where([
                'm.name|m.contact|m.phone|m.agent_name' => [$param['keywords_flag'], $param['keywords']."%"],
                'category'     => [$param['category_flag'], $param['category']],
                'address'     => [$param['address_flag'], $param['address']],
            ])
            ->whereTime('opening_time', $param['time_flag'], $param['time'])
            ->field(['m.name','m.contact', 'm.status', 'm.channel', 'm.address','m.opening_time','a.agent_name','m.id', 'a.agent_phone'])
            ->join('cloud_total_agent a','m.agent_id=a.id', 'left')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $res['pages'] = $pages;
        $res['pages']['rows'] = $rows;
        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            $this->return_msg(200, '搜索结果', $res);
        }else {
            $this->return_msg(400, '没有数据');
        }
    }

    /**
     * @param Request $request
     * @param [string]  $pay_type 支付类型
     * @param [int]  $past 过去的时间戳
     * @param [int]  $present 现在的时间戳
     * @pram [string] $channel 全部： -1 / 直联： 1 / 间联： 2
     * @return [json] $data 返回数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function echarts(Request $request)
    {
        /* 检验参数 */
        $this->check_params($request->param());

        /* 接受参数 */
        $pay_type = $request->param('pay_type');
        $past = $request->param('past') ? date('Y-m-d', $request->param('past')) : 'yesterday';
        $present = $request->param('present') ? date('Y-m-d', $request->param('present')) : null;
        $channel = $request->param('channel') ? $request->param('channel') : -1;
        $data = [];

        switch ($pay_type) {
            case 'total_amount':
                switch ($channel) {
                    // 全部/直联/间联
                    case -1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'between', [$past, $present])
                                ->field(['id','order_money', 'pay_type','create_time'])->select();

                        } else {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'yesterday')->field(['order_money', 'create_time', 'id', 'create_time'])->select();
                        }
                        break;
                    case 1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where('channel', 1)
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where('channel', 1)
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();

                        }
                        break;
                    case 2:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where('channel', 2)
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where('channel', 2)
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        }
                        break;
                }
                break;
            case 'total_quality':
                //
                break;
            case 'wxpay_amount':
                switch ($channel) {
                    // 全部/直联/间联
                    case -1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'between', [$past, $present])
                                ->where('pay_type' , 'wxpay')
                                ->field(['id','order_money', 'pay_type','create_time'])->select();

                        } else {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'yesterday')->field(['order_money', 'create_time', 'id', 'create_time'])->select();
                        }
                        break;
                    case 1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where(['channel' => 1, 'pay_type' => 'wxpay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where(['channel' => 1, 'pay_type' => 'wxpay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        }
                        break;
                    case 2:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where(['channel' => 2, 'pay_type' => 'wxpay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where(['channel' => 2, 'pay_type' => 'wxpay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        }
                        break;
                }
                break;
            case 'wxpay_quality':
                //
                break;
            case 'alipy_amount':
                switch ($channel) {
                    // 全部/直联/间联
                    case -1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'between', [$past, $present])
                                ->where('pay_type' , 'alipay')
                                ->field(['id','order_money', 'pay_type','create_time'])->select();

                        } else {
                            $data['total'] = Db::name('order')->whereTime('create_time', 'yesterday')->field(['order_money', 'create_time', 'id', 'create_time'])->select();
                        }
                        break;
                    case 1:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where(['channel' => 1, 'pay_type' => 'alipay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where(['channel' => 1, 'pay_type' => 'alipay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        }
                        break;
                    case 2:
                        if (!empty($present)) {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'between', [$past, $present])
                                ->where(['channel' => 2, 'pay_type' => 'alipay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        } else {
                            $data['total'] = Db::name('order')
                                ->whereTime('create_time', 'yesterday')
                                ->where(['channel' => 2, 'pay_type' => 'alipay'])
                                ->field(['order_money', 'create_time', 'id', 'pay_type'])->select();
                        }
                        break;
                }
                break;
            case 'alipay_quality':
                //
                break;
        }

        return json_encode($data);


    }

    /**
     *  验证参数是否正确
     * @param   [array] $arr 所有参数
     * @return [json] 参数验证结果/返回参数
     */
    public function check_params($arr)
    {
        /* 获取参数的验证规则 */
        try {
            $rule = $this->rules[$this->request->controller()][$this->request->action()];

        } catch (Exception $e) {
            return true;
        }

        /* 验证参数并返回检验结果 */
        $this->validater = new Validate($rule);
        if (!$this->validater->check($arr)) {
            $this->return_msg(400, $this->validater->getError());
        }

        return $arr;
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


}