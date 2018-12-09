<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 14:06
 */

namespace app\merchant\controller;

use app\agent\model\MerchantIncom;
use app\agent\model\Order;
use app\agent\model\TotalMerchant;
use app\merchant\model\MerchantShop;
use think\Controller;
use think\Db;
use think\Request;

class Deposit extends Commonality
{
    public $url = "https://gateway.starpos.com.cn/emercapp";

    /**
     * 商户提现页面
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw_list(Request $request)
    {
        //获取商户id
        $merchant_id = $this->id;
        //账户可提现余额 = 总共可提现金额 - 已经提现金额
        $data = TotalMerchant::where('id', $merchant_id)->field(['money', 'password', 'phone'])->select();
        $total_money = Order::where(['merchant_id' => $merchant_id, 'pay_type' => 'alipay','status'=>1])->whereOr('pay_type', 'wxpay')->sum('received_money');

        $new_money = Db::name('merchant_withdrawal')->where('merchant_id', $merchant_id)->field('money')->find()['money'];
        if ($new_money == null) {
            $new_money = 0;
        }
        //可提现金额
        $money = $total_money - $new_money;
        if ($money < 0) {
            $money = 0;
        }
//        return json_encode($data);
        if (Request::instance()->isGet()) {
            return_msg(200, 'success', ['money' => $money]);
            //return json_encode(['money'=>$data[0]['money']]);
        } else {
            $money = $request->param('money');
            //判断密码是否正确
//
            if ($data[0]['password'] == encrypt_password($request->param('password'), $data[0]['phone'])) {

                //orgNo机构号 tot_fee提现费率 mercId商户编号 stl_oac结算卡号 wc_lbnk_no开户行
                $del = MerchantIncom::where('merchant_id', $merchant_id)
                    ->field(['orgNo', 'mercId', 'stoe_id', 'tot_fee', 'stl_oac', 'wc_lbnk_no', 'key'])
                    ->find();
                $merchant_rate = TotalMerchant::where('id', $merchant_id)
                    ->field('merchant_rate')->find()['merchant_rate'];

                //总手续费
                $tot_fee = $money * $merchant_rate;
                //提现金额
                $money = $money - $tot_fee;
                //流水号
                list($usec, $sec) = explode(" ", microtime());
                $times = str_replace('.', '', $usec + $sec);
                $timese = date('YmdHis', time());
                $serial_number = $timese . $times;

                $bese = ['serial_number' => $serial_number, 'poundage' => $tot_fee, 'money' => $money, 'status' => 2, 'create_time' => time(), 'bank' => $del['wc_lbnk_no'], 'bank_card' => $del['stl_oac'], 'merchant_id' => $merchant_id];

                //提现记录id
                /*$id=Db::name('merchant_withdrawal')->insertGetId($bese);
                if(!$id){
                    return_msg(400,'error','提现失败');
                }*/
                //体现处理中返回的数据 bank开户行 bank_card结算卡号 money提现金额 poundage手续费
                $resu = ['bank' => $del['wc_lbnk_no'], 'bank_card' => $del['stl_oac'], 'money' => $money, 'poundage' => $tot_fee];
                $money = $money * 100;
                $tot_fee = $tot_fee * 100;
                $res = $this->withdraw();

                if ($res['repCode'] == 000000) {
                    $par = $this->confirm_withdrawal($money, $del, $tot_fee);
                    //返回数据的签名域

                    if ($par['repCode'] == '000000') {
                        $info = Db::name('merchant_withdrawal')->where('merchant_id', $merchant_id)->find();
                        if ($info != null) {
                            //原来可提现金额
                            $old_money = Db::name('merchant_withdrawal')->where('merchant_id', $merchant_id)->field('money')->find()['money'];

                            Db::name('merchant_withdrawal')->where('merchant_id', $merchant_id)->update(['end_time' => $par['serviceTime'], 'status' => 1, 'money' => ($old_money + $money / 100)]);
                        } else {
                            Db::name('merchant_withdrawal')->insertGetId($bese);
                        }

                        return_msg(200, 'success', $par['repMsg']);
                        /* } else {
                             Db::name('merchant_withdrawal')->update(['end_time'=>$par['serviceTime'],'status'=>3,'id'=>$id,'repMsg'=>$par['repMsg']]);

                             return_msg(400, 'error',$par['repMsg']);
                         }*/
                    } else {
//                                Db::name('merchant_withdrawal')->update(['end_time'=>$par['serviceTime'],'status'=>3,'id'=>$id,'repMsg'=>$par['repMsg']]);

                        return_msg(400, 'error', $par['repMsg']);
                    }

                } else {
                    return_msg(400, 'error', $res['repMsg']);
                }


            } else {
                return_msg(400, 'error', '密码错误，请重新输入');
            }
        }
    }

    /**
     * 提现记录
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_record()
    {

        $merchant_id = $this->id;
        $data = Db::name('merchant_withdrawal')->where(['merchant_id' => $merchant_id])->select();
        check_data($data);
    }

    /**
     * 提现记录 查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_record_query(Request $request)
    {
        $merchant_id = $this->id;
        $merchant_id = 1;
        //提现方式 1普通提现 2自动提现 3快速提现 0全部方式
        $way = $request->param('way') ? $request->param('way') : 0;
        $waysymo = '=';
        if ($way == 0) {
            $waysymo = '<>';
        }
        //提现状态 1 提现成功 2提现中 3提现失败 0全部方式
        $status = $request->param('status') ? $request->param('status') : 0;
        $statussyom = '=';
        if ($status == 0) {
            $statussyom = '<>';
        }
        //时间
        $create_time = $request->param('create_time') ? strtotime($request->param('create_time')) : 1533950117;
        $end_time = $request->param('end_time') ? strtotime($request->param('end_time')) + 60 * 60 * 24 - 1 : 1912641317;

        $data = Db::name('merchant_withdrawal')
            ->where(['merchant_id' => $merchant_id, 'status' => [$statussyom, $status], 'way' => [$waysymo, $way],
                'create_time' => ['between time', [$create_time, $end_time]]])
            ->select();
        if ($data) {
            return_msg(200, 'success', $data);

        } else {
            return_msg(400, 'error', '查无此记录');
        }

    }

    /**
     * 开通提现权限接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw(Request $request)
    {
        $param = $request->param();
        check_params("deposit", $param, 'MerchantValidate');
        $sid = $param["sid"];

        $store = Db::name("merchant_shop")->where("id", $sid)->find();
        $regBody['merc_id'] = $store['mercId'];
        $regBody['stoe_id'] = $store['stoe_id'];

        $del['reqBody'] = "[" . json_encode($regBody) . "]";
        $del['serviceId'] = "6060660";
        $del['version'] = "V1.0.0";
        $del['signType'] = 'MD5';
        $del['orgNo'] = '27573';

        $del['signValue'] = sign_ature(0000, $del);

        //新大陆开启提现权限接口
        $par = $this->curl_request($this->url, true, $del, true);
        $par = json_decode($par, true);
        //返回数据的签名域
        if ($par['repCode'] == '000000') {
            $ret = json_decode(urldecode($par["respBody"]), true);
//            halt(urldecode($par["respBody"]));
            if ($ret[0]["sts"] == 1 && $store['stl_typ'] == 0) {
                return_msg(400, "对公账户不支持D0!");
            }elseif ($ret[0]["sts"] == 1) {
                return_msg(400, "失败，请稍后再试!");
            }
            $this->Withdrawal_information($sid);
        } else {
            return_msg(400, "失败，请稍后再试!");
        }

    }

    /**
     * 获取提现信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_information($s_id = null)
    {
//        echo 1;die;
        $sid = \request()->param("sid") ? \request()->param("sid") : $s_id;
        if (empty($sid)) {
            return_msg(400, "门店ID必须");
        }
        $store = Db::name("merchant_shop")->where("id", $sid)->find();

        $arr = ['serviceId' => '6060661', 'version' => 'V1.0.0', 'orgNo' => "27573", 'merc_id' => $store['mercId'], 'signType' => 'MD5'];
        $arr['signValue'] = sign_ature(0000, $arr);

        $par = $this->curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
//        halt($par);
        if ($par['repCode'] == '000000') {
            /** 成功返回体现信息 */
            return_msg(200,  json_decode($par['respBody'], true));
        } else {
            return_msg(500, urldecode($par['repMsg']) );
        }
    }

    /**
     * 确定提现
     * @param Request $request
     * @return bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirm_withdrawal(Request $request)
    {
        $money = $request->post("money");
        $sid = $request->post("sid");
        $tot_fee = (int)$money * 0.0005 * 100;
        $del = Db::name("merchant_shop")->where("id", $sid)->find();

        $reqBody = [
            'merc_id' => $del['mercId'],
            'stoe_id' => $del['stoe_id'],
            'txn_amt' => (string)($money * 100),
            'pre_fee' => (string)$tot_fee
        ];

        $arr = ['serviceId' => '6060662', 'version' => 'V1.0.0', 'orgNo' => "27573", 'signType' => 'MD5', 'tot_amt' => (string)($money * 100), 'tot_fee' => (string)$tot_fee];
        $arr['reqBody'] = "[" . json_encode($reqBody) . "]";
        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue'] = $sign_ature;
        $par = $this->curl_request($this->url, true, $arr, true);
        $par = json_decode($par, true);

        if ($par["repCode"] == "000000"){
            $st = 1;
        }else {
            $st = 3;
        }
        $data = [
            "shop_id" => $sid,
            "merchant_id" => $del["merchant_id"],
            "status"  =>  $st,
            "way"  => 2,
            "poundage" => $tot_fee,
            "money"   => $money/100,
            "create_time" => time(),
            "banck"  => $del["stl_oac"],
            "repMsg"  => urldecode($par["repMsg"]),
        ];
        $info = Db::name('merchant_withdrawal')->where('merchant_id', $this->id)->find();
        if ($info != null) {
            //原来可提现金额
            $old_money = Db::name('merchant_withdrawal')->where('merchant_id', $this->id)->field('money')->find()['money'];

            Db::name('merchant_withdrawal')->where('merchant_id', $this->id)->update(['end_time' => $par['serviceTime'], 'status' => 1, 'money' => ($old_money + $money / 100)]);
        }else{
            $res = Db::name("merchant_withdrawal")->insertGetId($data);

        }
        if ($par["repCode"] == "000000" && $res) {
            /** 提现成功 */

            return_msg(200, urldecode($par["repMsg"]));
        } else {
            return_msg(400, urldecode($par["repMsg"]));
        }

    }
    /**
     * 发起请求
     */
    public function curl_request($url, $post = false, $params = [], $https = false)
    {
        $params = json_encode($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array("application/json;charset=UTF-8", "Content-length:" . strlen($params)));
        }
        if ($https) {
            //https协议，禁止curl从服务器端验证本地证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //③使用curl_exec执行，发送请求
        //设置 让curl_exec 直接返回接口的结果数据
        $res = curl_exec($ch);
        //④使用curl_close关闭请求会话
        curl_close($ch);
        return $res;
    }

    /**
     * 2.4    提现结果查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function results_query(Request $request)
    {
        //获取日期
        $ac_dt = $request->param('create_time');
        $merchant_id = $this->id;
        $del = MerchantIncom::where('merchant_id', $merchant_id)
            ->field(['orgNo', 'mercId'])
            ->select();
        $del = $del[0];
        $arr = ['serviceId' => '6060661', 'version' => 'V1.0.0', 'orgNo' => $del['orgNo'], 'merc_id' => $del['mercId'], 'signType' => 'MD5'];
        if ($ac_dt) {
            $arr['ac_dt'] = $ac_dt;
        }
        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue'] = $sign_ature;
        $arr = json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);
        //$par['respBody']  商户的json数组
        if ($par['repCode'] == '000000') {
            if ($par['signValue'] == $return_sign) {
                return_msg(200, 'success', $par['repMsg']);
            } else {
                return_msg(400, 'error', $par['repMsg']);
            }
        } else {
            return_msg(500, 'error', $par['repMsg']);
        }
    }

    public function close_jurisdiction(Request $request)
    {

        //获取门店号
        $shop_id = $request->param('shop_id');
        $merchant_id = $this->id;
        $del = Db::name('merchant_incom')->alias('a')
            ->field(['a.orgNo', 'a.mercId', 'b.stoe_id'])
            ->join('merchant_shop b', 'a.merchant_id=b.merchant_id', 'left')
            ->where(['merchant_id' => $merchant_id, 'b.id' => ['in', $shop_id]])
            ->select();

        $arr = ['serviceId' => '6060661', 'version' => 'V1.0.0', 'orgNo' => $del[0]['orgNo'], 'signType' => 'MD5'];
        $array = [];
        foreach ($del as $k => $v) {
            $array[] = $v['stoe_id'];
        }
        $arr['reqBody']['stoe_id'] = $array;
        $arr['reqBody']['merc_id'] = $del[0]['mercId'];

        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue'] = $sign_ature;
        $arr = json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);
        //$par['respBody']  商户的json数组
        if ($par['repCode'] == '000000') {
            if ($par['signValue'] == $return_sign) {
                return_msg(200, 'success', $par['repMsg']);
            } else {
                return_msg(400, 'error', $par['repMsg']);
            }
        } else {
            return_msg(500, 'error', $par['repMsg']);
        }
    }
}