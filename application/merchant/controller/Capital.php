<?php

namespace app\merchant\controller;

use app\admin\model\MerchantIncom;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use app\merchant\model\TotalMerchant;
use Endroid\QrCode\QrCode;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;

class Capital extends Common
{

    public $url = 'https://gateway.starpos.com.cn/emercapp';//正式
//     public $url = 'http://sandbox.starpos.com.cn/emercapp';//测试

    /**
     * 结算列表
     * @status  1--成功 2--提现中 3--失败
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cash(Request $request)
    {
        if ($request->isGet()) {
            $param["status"] = $request->param("status");

            if (isset($param["status"])) {
                $param["status_flag"] = "eq";
            } else {
                $param["status_flag"] = "<>";
                $param["status"] = -1;
            }
            $where = [
                "merchant_id" => $this->merchant_id,
                "status" => [$param["status_flag"], $param["status"]],
            ];
            $rows = Db::name("merchant_withdrawal")->where($where)->count("id");
            $pages = page($rows);

            $res["list"] = Db::name("merchant_withdrawal")->where($where)->select();

            $res["pages"] = $pages;
            $res["pages"]["rows"] = $rows;
            check_data($res["list"], $res);
        }
    }

    /**
     * @param array $param
     * @return array [处理并返回数组参数]
     */
    protected function search_item(Array $param)
    {
        $arr = [];
        foreach ($param as $k => &$v) {
            $flag = $k . "_flag";
            if (!$v) {
                $arr[$flag] = "<>";
                $v = -2;
            } else {
                $arr[$flag] = "eq";
            }
        }
        $query = $arr + $param;
        return $query;
    }

    /**
     * PC端---提现搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cash_search(Request $request)
    {
        $time = $request->param("time");
        $status = $request->param("status");

        if (!empty($time)) {
            $time_flag = "between";
            $time = explode(',', $time);
        } else {
            $time_flag = ">";
            $time = 0;
        }

        if (!empty($status)) {
            $status_flag = "eq";
        } else {
            $status_flag = ">";
            $status = -2;
        }

        $where = [
            "merchant_id" => $this->merchant_id,
            "create_time" => [$time_flag, $time],
            "status" => [$status_flag, $status],
        ];


        $rows = Db::name("merchant_withdrawal")->where($where)->count("id");
        $pages = page($rows);

        $res["list"] = Db::name("merchant_withdrawal")->where($where)->select();

        $res["pages"] = $pages;
        $res["pages"]["rows"] = $rows;
        check_data($res["list"], $res);

    }

    /**
     * PC端资金---个人资料
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function pc_profile()
    {
        if (\request()->isGet()) {
            $res = TotalMerchant::get($this->merchant_id);

            $amount = Order::where(["merchant_id" => $this->merchant_id, "pay_time" => [">", 0]])->sum("received_money");

            $data = $res->visible(["name", "phone", "id_card", "account_no"], true)->toArray();
            $data["amount"] = $amount;
            check_data($data);
        }
    }

    /**
     * PC端--绑定银行卡
     * @param Request $request
     */
    public function pc_bind(Request $request)
    {
        if ($request->isPost()) {
            $param = [];

            $card = TotalMerchant::get($this->merchant_id)["id_card"];

            $param["id"] = $this->merchant_id;
            $param["id_card"] = $card;
            $param["account_name"] = $request->param("account_name");
            $param["account_no"] = $request->param("account_no");
            $param["phone"] = $request->param("phone");

            $param["open_bank"] = $request->param("open_bank");

            /** 获取开户行联行行号 */
            $param["open_branch"] = $request->param("open_branch");

            check_params("bind_card", $param, "MerchantValidate");

//            $this->check_bank($param);

            /** 商户修改 */
            $this->merchant_edit($param);


            /** 存入数据库 */
            unset($param["id"]);
            $res = TotalMerchant::where($this->merchant_id)->save($param);
            /** @var [int] 处理存入InCom表的数据 $incom */
            $data = [
                "stl_oac" => $param["account_no"],
                "bnk_acnm" => $param["account_name"],
                "wc_lbnk_no"  => $param["open_branch"],
                "status"  => 2,
            ];

            $incom = MerchantIncom::where("merchant_id", $this->merchant_id)->save($data);
            if ($res && $incom) {
                return_msg(200, "success");
            }else {
                return_msg(400, "fail");
            }


        }
    }


    /**
     * 银行卡绑定查询 -- 走聚合数据接口
     * @param $arg
     * @return bool / msg
     */
    protected function check_bank($arg)
    {

        $param = [
            "realname" => $arg["account_name"],//账户名
            "idcard" => (string)$arg["id_card"],//身份证号
            "bankcard" => (string)$arg["account_no"],//账户号
            "mobile" => (string)$arg["phone"],//联系电话
            "key" => "4c0307cd5fafa19a2da4ffe877370313",
        ];
        /** @var [string] 聚合支付请求API $url */
        $url = "http://v.juhe.cn/verifybankcard4/query";
        $res = $this->curl_request($url, true, $param, false);

        $res = json_decode($res, true);
        if ($res["result"]["res"] == "2") {
            return_msg(400, $res["result"]["message"]);
        } else {
            return true;
        }
    }


    /**
     * Curl 请求
     * @param $url
     * @param bool $post
     * @param array $params
     * @param bool $https
     * @return mixed
     */
    protected function curl_request($url, $post = false, $params = [], $https = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array("application/json;") );
        }
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    /**
     * 商户修改申请
     * 商户审核通过后，修改商户为 修改未完成状态
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function merchant_edit($param)
    {
        $id = $param['id'];
//        $id = 87; //mark
        $data["serviceId"] = "6060605";
        $data["version"] =  "V1.0.1";

        $res = Db::name('merchant_incom')->where('merchant_id', $id)->field('mercId,orgNo,check_flag')->find();

        $res = collection($res)->toArray();
        $data["mercId"] = $res["mercId"];
        $data["orgNo"] = $res["orgNo"];

        $data["signValue"] = sign_ature(0000, $data);
        //向新大陆接口发送信息验证
        $par = curl_request($this->url, true, $data, true);

        $bbntu = json_decode($par, true);
        $bbntu["status"] = 2;
        $return_sign = sign_ature(1111, $data);


        if ($bbntu['msg_cd'] === 000000) {
            if ($return_sign == $bbntu['signValue']) {
                //商户状态修改为修改未完成
                Db::name('merchant_incom')->where('merchant_id', $id)->update($bbntu);
                $this->commercial_edit($param);

            } else {
                return_msg(400, 'error', $bbntu['msg_dat']);
            }
        } else {
            return_msg(500, 'error', $bbntu['msg_dat']);
        }

    }

    /**
     * @rule 商户资料修改
     * @rule 商户修改时商户状态（注册未完成，修改未完成，注册拒绝，修改拒绝）
     * @param Request $request
     * @throws Exception
     * @return [bool / string] msg
     */

    public function commercial_edit()
    {
        $arg=request()->post();
        $del['serviceId'] = '6060604';
        $del['version'] = 'V1.0.4';
        $data = Db::name('merchant_incom')->where('merchant_id', $arg['id'])->field('log_no,mercId,stoe_id,status,fee_rat1_scan,fee_rat3_scan,fee_rat_scan, incom_type,stl_typ,stl_sign,bus_lic_no,bse_lice_nm,crp_nm,mercAdds,bus_exp_dt,crp_id_no,crp_exp_dt,stoe_nm,stoe_area_cod,stoe_adds,trm_rec,mailbox,yhkpay_flg,alipay_flg,orgNo,cardTyp,suptDbfreeFlg,tranTyps,crp_exp_dt_tmp,fee_rat,max_fee_amt,fee_rat1,ysfcreditfee,ysfdebitfee')->find();
        //商户为未完成状态才可以修改
        $del['stl_oac']=$arg['account_no'];
        $del['bnk_acnm']=$arg['account_name'];
        $del['wc_lbnk_no']=$arg['open_branch'];
        $del['icrp_id_no']=$arg['id_card'];
        $del['stoe_cnt_tel']=$arg['mobile'];
        $del['stoe_cnt_nm']=$arg['account_name'];
        $data['orgNo']=ORG_NO;
        //halt($data);

        if ($data['status'] == 2){

            $aa = [];
            foreach ($data as $k => $v) {
                $aa[$k] = $v;
            }
            $dells = $del + $aa;
                halt($dells);
            //mark 调试到这里 by--端木
            $sign_ature = sign_ature(0000, $dells);
//                die;
            $dells['signValue'] = $sign_ature;
//                halt($del);
            //向新大陆接口发送信息验证

            $par = curl_request($this->url, true, $dells, true);

            $par = json_decode($par, true);
            //返回数据的签名域
//            halt($par);
            $return_sign = sign_ature(1111, $par);

            if ($par['msg_cd'] == 000000) {
                if ($par['signValue'] == $return_sign) {
                    $del['status'] = 0;
                    Db::name('merchant_incom')->where('merchant_id', $arg['id'])->update($del);
                    Db::name('total_merchant')->where('id',$arg['id'])->update($arg);
                    //商户提交
                    $this->merchant_create($arg['id']);
//                    return_msg(200, 'success', $par['msg_dat']);
                } else {
                    return_msg(400, 'error', $par['msg_dat']);
                }
            } else {
                return_msg(500, 'error', $par['msg_dat']);
            }
        } else {
            return_msg(100, 'error', '商户为审核完成状态，请先申请修改');
        }

    }

    /**
     *商户修改完成提交
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_create($id)
    {
//        $merchant_id=$request->param('merchant_id');
        //取出数据表中数据
//        $id=102;
        $data=MerchantIncom::where('merchant_id',$id)->field('mercId,orgNo,log_no')->find();
        $data = $data->toArray();
        $data['serviceId']='6060603';
        $data['version']='V1.0.1';
//        $data['log_no']="201810110001103896";

        $data['signValue'] = sign_ature(0000,$data);
//        halt($data);
        $result=curl_request($this->url,true,$data,true);

        $result = json_decode($result,true);

//        halt($result);
        //生成签名
        $signValue = sign_ature(1111,$result);
        if($result['msg_cd']=='000000') {

            if ($signValue == $result[ 'signValue' ]) {
                if ($result[ 'check_flag' ]==3) {
                    //修改数据表状态
                    $res = MerchantIncom::where('merchant_id', $id)->update(['check_flag' => $result[ 'check_flag' ]]);

                    if ($res) {
                        return_msg(200, 'success');
                    } else {
                        return_msg(400, 'error');
                    }
                }elseif($result['check_flag']==1){
                    //修改数据表状态
                    $res = MerchantIncom::where('merchant_id', $id)->update(['check_flag' => $result[ 'check_flag' ],
                        'key' => $result[ "key" ],
                        'rec' => $result[ 'REC' ]
                    ]);

                    if ($res) {
                        return_msg(200, 'success');
                    } else {
                        return_msg(400, 'error');
                    }
                } else {
                    return_msg(400, 'error', $result[ 'msg_dat' ]);
                }
            }
        }else{
            return_msg(400, 'error', $result[ 'msg_dat' ]);
        }
    }

    /**
     * PC端--对账列表
     * @param Request $request [name] 门店搜索
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_bill(Request $request)
    {
        if ($request->isGet()) {
            /** @var  $rows */
            $param["name"] = $request->param("name");
            $rows = MerchantShop::where(["merchant_id" => $this->merchant_id])->count("id");
            $pages = page($rows, 2);
            $res = MerchantShop::where(["merchant_id" => $this->merchant_id])->limit($pages["offset"], $pages["limit"])->select();

            check_data($res, $res, 0);
            $shop_ids = [];
            foreach ($res as $v) {
                $shop_ids[] = $v["id"];
            }
            $query = $this->search_item($param);

            $where = [
                "ms.shop_name" => [$query["name_flag"], $query["name"]],
            ];
            $field = ["ms.id", "ms.shop_name", "o.pay_type", "count(o.id) money_num", "sum(o.order_money) order_money", "sum(o.discount) discount", "sum(o.refund_money) refund_money", "sum(o.received_money) received_money"];


            $pay_type = ["wxpay", "alipay", "etc", "cash"];



            foreach ($shop_ids as $i) {

                foreach ($pay_type as $type) {
                    /** 退款次数 */
                    $refund[$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("refund_time", ">", 0)
                        ->where($where)
                        ->where("ms.id", $i)
                        ->where("o.pay_type", $type)
                        ->whereTime("pay_time", "today")
                        ->group("o.id")
                        ->count("ms.id");

                    $data["list"][$i][$type] = MerchantShop::alias("ms")
                        ->join("cloud_order o", "o.shop_id = ms.id", "RIGHT")
                        ->where("ms.id", $i)
                        ->where($where)
                        //mark
//                        ->whereTime("pay_time", "today")
                        ->where("o.pay_type", $type)
                        ->field($field)
//                        ->group("o.id")
                        ->find();


                    if (empty($data["list"][$i][$type]["id"])) {
                        unset($data["list"][$i]);
                    } elseif (empty($data["list"][$i][$type]["pay_type"])) {
                        unset($data["list"][$i][$type]);
                    }
                }
                if (isset($data["list"][$i]) && !count($data["list"][$i])) {
                    unset($data["list"][$i]);
                }

            }


            foreach ($data["list"] as $k => &$list) {

                /** 退款笔数 */
                foreach ($pay_type as $type) {
                    if (isset($list[$type])) {
                        $list[$type]["refund_num"] = $refund[$k][$type];

                    }
                }
            }

            $data["pages"] = $pages;
            $data["pages"]["rows"] = $rows;

            check_data($data["list"], $data);
        }
    }

}

