<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/26
 * Time: 10:06
 */

namespace app\merchant\controller;


use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use think\Db;
use think\Request;
use think\Session;

class Index extends Common
{
    /**
     * APP首页展示
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        //$role 1服务员 2店长 3收银员 -1商户
//            $id=Session::get('username_', 'app')['user_id'];
//            $role=Sessin::get('username_', 'app')['role_id'];
        $role = -1;
        $name = 'merchant_id';
        $id = 2;
        if ($role == 1 || $role == 3) {
            $name = 'person_info_id';
        }
        if ($role == 2) {
            $id = MerchantUser::where('id', $id)->field('shop_id')->find();
            $name = 'shop_id';
        }
        if ($role == -1) {
            $name = 'merchant_id';
        }


        //查询今天的收银金额和订单笔数
        $data = Order::where([$name => $id])
            ->whereTime('pay_time', 'd')
            ->where($name, $id)
            ->field('count(id) id,sum(received_money) received_money')
            ->select();


        return_msg(200, 'success', $data);

    }

    /******* 商户PC后台--首页数据  ********/
    /**
     * 首页交易数据--统计
     *
     * @param Request $request ->  $time  时间戳  格式[12312312,35146112]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_index(Request $request)
    {
        if ($request->isGet()) {
            $time = $request->param("time");
            if (isset($time)) {
                $time_flag = "between";
                $time = [$time];
            } else {
                $time = strtotime("today");
                $time_flag = ">=";
            }
            $Order = new Order();


            $result = $Order::where(["merchant_id" => $this->merchant_id])
                ->whereTime("pay_time", $time_flag, $time)
                ->select();

            check_data($result,'',0);
            /** 实收金额 */
            $data["count"]['true_money'] = 0;
            /** 交易金额 */
            $data["count"]['trad_money'] = 0;
            /** 优惠金额 */
            $data["count"]['discount'] = 0;

            foreach ($result as $k => &$v) {
                $data["count"]['true_money'] += $v["received_money"];

                $data["count"]['trad_money'] += $v["order_money"];

                $data["count"]['trad_num'] = $k;

                $data["count"]['discount'] += $v["discount"];

            }
            $where = [
                "merchant_id" => $this->merchant_id,
                "pay_time" => [$time_flag, $time]
            ];

            /** 退款金额 */
            $data["count"]['refund_money'] = $Order->where($where)->where("status = 2")->sum("refund_money");
            /** 银联，微信，支付宝，现金交易笔数和金额 */

            $data["received"]["union_money"] = $Order->where($where)->where(["pay_type" => "etc"])->sum("received_money");
            $data["received"]["union_num"] = $Order->where($where)->where(["pay_type" => "etc"])->count("id");
            $data["received"]["wx_money"] = $Order->where($where)->where(["pay_type" => "wxpay"])->sum("received_money");
            $data["received"]["wx_num"] = $Order->where($where)->where(["pay_type" => "wxpay"])->count("id");
            $data["received"]["ali_money"] = $Order->where($where)->where(["pay_type" => "alipay"])->sum("received_money");
            $data["received"]["ali_num"] = $Order->where($where)->where(["pay_type" => "alipay"])->count("id");
            $data["received"]["cash_money"] = $Order->where($where)->where(["pay_type" => "cash"])->sum("received_money");
            $data["received"]["cash_num"] = $Order->where($where)->where(["pay_type" => "cash"])->count("id");


            check_data($data);
        }
    }

    /**
     * 首页数据-- 列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_list(Request $request)
    {
        if ($request->isGet()) {
            $time = $request->param("time");

            if (isset($time)) {
                $time_flag = "between";

            } else {
                $time = strtotime("today");
                $time_flag = ">=";
            }
            /** @var [Array] 条件 $where */
            $where = [
                "merchant_id" => $this->merchant_id,
                "pay_time" => [$time_flag, $time]
            ];

            $Order = new Order();

            /** 默认展示今天的 门店数据 */
            $rows = $Order::where($where)->count("id");
            $pages = page($rows);
            $field = ['order_number','pay_time',"shop_name", "cashier","pay_type", "received_money", "order_money", "discount", "status"];

            $data["list"] = $Order::where($where)
                ->alias("o")
                ->join("cloud_merchant_shop shop", "o.shop_id")
                ->limit($pages["offset"], $pages["limit"])->field($field)->select();
            $data["pages"] = $pages;
            $data["pages"]["rows"] = $rows;

            check_data($data);
        }

    }

}