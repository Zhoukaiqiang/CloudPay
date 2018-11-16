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
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
use think\Request;
use think\Session;

class Index extends Common
{

    /**
     * APP首页展示
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        //role 1服务员 2店长 3收银员 -1商户
        $role = Session::get("username_", "app")["role"];
        //用户ID
        $id = Session::get("username_", "app")["id"];
        //获取门店ID
        $k = MerchantUser::where('id', $id)->field('shop_id')->find();
        $data['shop_id'] = $k->shop_id;
        if ($role == 1) {
            $name = 'user_id';
        } elseif ($role == 3) {
            $name = 'person_info_id';
        } elseif ($role == 2) {
            $id = $k->shop_id;
            $name = 'shop_id';
        } else {
            $k = MerchantShop::where('merchant_id', $id)->field('id')->find();
            $data['shop_id'] = $k['id'];
            $name = 'merchant_id';
        }


        //查询今天的收银金额和订单笔数
        $data['list'] = Order::where([$name => $id])
            ->whereTime('pay_time', 'd')
            ->field('count(id) countid,sum(received_money) received_money')
            ->select();


        return_msg(200, 'success', $data);

    }

    /******* 商户PC端后台--首页数据  ********/
    /**
     * PC端首页交易数据--统计
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
            if ($this->merchant_id) {
                $mid = $this->merchant_id;
            } else {
                $mid = Db::name("merchant_user")->where("id", $this->user_id)->find()["merchant_id"];
            }
            $result = Order::where("merchant_id", $mid)
                ->whereTime("pay_time", $time_flag, $time)
                ->select();

            check_data($result, '', 0);
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
                "merchant_id" => $mid,
                "pay_time" => [$time_flag, $time]
            ];

            /** 退款金额 */
            $data["count"]['refund_money'] = Order::where($where)->where("status = 2")->sum("refund_money");
            /** 银联，微信，支付宝，现金交易笔数和金额 */

            $data["received"]["union_money"] = Order::where($where)->where(["pay_type" => "etc"])->sum("received_money");
            $data["received"]["union_num"] = Order::where($where)->where(["pay_type" => "etc"])->count("id");
            $data["received"]["wx_money"] = Order::where($where)->where(["pay_type" => "wxpay"])->sum("received_money");
            $data["received"]["wx_num"] = Order::where($where)->where(["pay_type" => "wxpay"])->count("id");
            $data["received"]["ali_money"] = Order::where($where)->where(["pay_type" => "alipay"])->sum("received_money");
            $data["received"]["ali_num"] = Order::where($where)->where(["pay_type" => "alipay"])->count("id");
            $data["received"]["cash_money"] = Order::where($where)->where(["pay_type" => "cash"])->sum("received_money");
            $data["received"]["cash_num"] = Order::where($where)->where(["pay_type" => "cash"])->count("id");

            check_data($data);
        }
    }

    /**
     * PC端首页数据-- 今日交易数据
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_list(Request $request)
    {
        if ($request->isGet()) {
            $time = $request->param("time");
            $excel = $request->get("excel");

            if (isset($time)) {
                $time_flag = "between";

            } else {
                $time = strtotime("today");
                $time_flag = ">=";
            }
            /** @var [Array] 条件 $where */
            if ($this->merchant_id) {
                $mid = $this->merchant_id;
            } else {
                $mid = Db::name("merchant_user")->where("id", $this->user_id)->find()["merchant_id"];
            }
            $where = [
                "merchant_id" => $mid,
                "pay_time" => [$time_flag, $time]
            ];

            $where_join = [
                "o.merchant_id" => $mid,
                "o.pay_time" => [$time_flag, $time],
            ];

            $this->return_list($where, $where_join, $excel);

        }

    }

    /**
     * PC端---图表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function pc_diagram(Request $request)
    {
        if ($request->get()) {
            $shop_id = $request->param("shop_id");

            if (!empty($shop_id)) {
                $shop_flag = "eq";
            } else {
                $shop_flag = "<>";
                $shop_id = -2;
            }

            $i = -7;
            /** 默认展示7天数据 */
            while ($i < 0) {
                $morning = strtotime(date('Y-m-d 00:00:00', strtotime($i . ' days')));
                $night = strtotime(date('Y-m-d 23:59:59', strtotime($i . ' days')));
                $date = [$morning, $night];

                $data['chartData'][] = Order::where("merchant_id", $this->merchant_id)
                    ->where(["shop_id" => [$shop_flag, $shop_id]])
                    ->whereTime('pay_time', "between", $date)
                    ->field("sum(received_money) received_money, count(id) num")
                    ->find();
                $i++;
            }

            check_data(collection($data["chartData"])->toArray(), '', false);
            foreach ($data["chartData"] as $k => $v) {

                $filted_data[] = [
                    "amount" => $v["received_money"],
                    "count" => $v["num"],
                    "average" => (int)$v["num"] ? (int)$v["received_money"] / (int)$v["num"] : 0,
                    "pay_time" => date('Y-m-d', strtotime($i + $k . ' days')),
                ];
            }

            check_data($filted_data);
        }
    }

    /**
     * PC端首页数据-- 今日交易数据
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_search_list(Request $request)
    {
        if ($request->isPost()) {

            $cashier = $request->param("cashier");
            $shop = $request->param("shop");
            $status = $request->param("status");
            $time = $request->param("time");
            //是否导出Excel
            if (isset($time)) {
                $time_flag = "between";

            } else {
                $time = strtotime("today");
                $time_flag = ">=";
            }
            if ($cashier) {
                $cashier_flag = "LIKE";
            } else {
                $cashier_flag = "<>";
                $cashier = -2;
            }

            if (isset($shop)) {
                $shop_flag = "eq";
            } else {
                $shop_flag = "<>";
                $shop = -2;
            }

            if (isset($status)) {
                $status_flag = "eq";
            } else {
                $status_flag = "<>";
                $status = -2;
            }
            $where = [
                "merchant_id" => $this->merchant_id,
                "pay_time" => [$time_flag, $time]
            ];

            $where_join = [
                "o.merchant_id" => $this->merchant_id,
                "o.cashier" => [$cashier_flag, $cashier],
                "o.shop_id" => [$shop_flag, $shop],
                "o.status" => [$status_flag, $status],
                "o.pay_time" => [$time_flag, $time],
            ];

            $this->return_list($where, $where_join);

        }

    }


    public function pc_search_it(Request $request)
    {
        if ($request->isGet()) {
            if ($this->merchant_id) {
                $mid = $this->merchant_id;
            } else {
                $mid = Db::name("merchant_user")->where("id", $this->user_id)->find()["merchant_id"];
            }
            /** 搜索可选项--------- 返回所有门店 */
            $data["shop_list"] = MerchantShop::where(["merchant_id" => $mid])->field("shop_name,id")->select();

            /** 搜索可选项--------- 返回所有收银员 */
            $data["cashier_list"] = MerchantUser::where(["merchant_id" => ["eq", $mid], "role" => ["eq", 1]])->field("name,id")->select();

            check_data($data);
        }
    }

    /**
     * 返回列表数据
     * @param $where
     * @param $where_join
     * @param $excel [bool] 是否需要返回Excel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function return_list($where, $where_join, $excel = null)
    {
        $Order = new Order();
        /** 默认展示今天的 门店数据 */
        $rows = $Order::where($where)->count("id");
        $pages = page($rows);
        $field = ['o.id', 'o.order_number', 'o.pay_time', "shop.shop_name", "o.cashier", "o.pay_type", "o.received_money", "o.order_money", "o.discount", "o.status", "c.name"];

        $data["list"] = $Order::where($where_join)
            ->alias("o")
            ->field($field)
            ->join("cloud_merchant_shop shop", "o.shop_id", "left")
            ->join('cloud_merchant_user c', 'o.user_id=c.id', "left")
            ->group("o.id")
            ->limit($pages["offset"], $pages["limit"])->field($field)->select();
        $data["pages"] = $pages;
        $data["pages"]["rows"] = $rows;

        if ($excel) {
            /** 导出Excel */
            $this->excelExport("data.xlsx", $data["list"]);
        } else {
            check_data($data["list"], $data);
        }


    }

    /**
     * excel表格导出
     * @param string $fileName 文件名称
     * @param array $headArr 表头名称
     * @param array $data 要导出的数据
     * @author static7
     * @throws Exception
     */

    public function excelExport($fileName = '', $data = [])
    {

        ini_set("memory_limit", "-1");
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue("A1", "订单号");
        $sheet->setCellValue("B1", "交易时间");
        $sheet->setCellValue("C1", "交易门店");
        $sheet->setCellValue("D1", "收款人");
        $sheet->setCellValue("E1", "支付方式");
        $sheet->setCellValue("F1", "订单金额");
        $sheet->setCellValue("G1", "优惠金额");
        $sheet->setCellValue("H1", "实收金额");
        $sheet->setCellValue("I1", "订单状态");

        $i = 1;
        foreach ($data as $dd) {
            $i++;
            $sheet->setCellValue('A' . $i, $dd['order_number']);
            $sheet->setCellValue('B' . $i, $dd['pay_time']);
            $sheet->setCellValue('C' . $i, $dd['shop_name']);
            $sheet->setCellValue('D' . $i, $dd['cashier']);
            $sheet->setCellValue('E' . $i, $dd['pay_type']);
            $sheet->setCellValue('F' . $i, $dd['received_money']);
            $sheet->setCellValue('G' . $i, $dd['order_money']);
            $sheet->setCellValue('H' . $i, $dd['discount']);
            $sheet->setCellValue('I' . $i, $dd['status']);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save("uploads/" . $fileName);
        return_msg(200, "success", "uploads/" . $fileName);
    }

}
