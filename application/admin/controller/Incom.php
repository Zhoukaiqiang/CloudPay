<?php

namespace app\admin\controller;

use app\admin\model\MerchantIncom;
use app\admin\model\MerchantStore;
use app\admin\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class Incom extends Controller
{
    public $url = 'https://gateway.starpos.com.cn/emercapp';
//    public $url = 'http://sandbox.starpos.com.cn/emercapp';


    /**
     * 验证签名域是否正确
     * @param $old_sign
     * @param $res
     * @return bool
     * @return string
     */
    protected function check_sign_value($query_sign, Array $res)
    {
        if ($query_sign !== sign_ature(1111, $res)) {
            return_msg(400, "签名域不正确");

        } elseif ($res['msg_cd'] !== "000000") {
            return_msg(400, "操作失败!");
        } else {
            return true;
        }
    }

    /**
     * 验证成功更新数据库
     * @param $where
     * @param $data
     * @return string msg
     */
    protected function insert_to_incom_table($table,$where, $data)
    {

        $result = Db::table($table)->where($where)->save($data);
        if ($result) {
            /** 返回消息 */
            return_msg(200, "操作成功", $result);
        } else {
            return_msg(400, "操作失败");
        }
    }

    /**
     * 商户查询 2.1
     *
     * @param array    [    *参数名            *类型     *描述
     *                     'merchant_id'  =>  int      商户ID
     *                     'mercId'   =>   string      商户识别号（15位数字）
     *                     'orgNo'   =>   string       机构号
     *                 ]
     * @description array  {
     *                      "check_flag"  : "string"          审核结果 1-通过 2-驳回 3-转人工
     *                      "msg_cd"      : "string"          返回码   000000 成功
     *                      "msg_dat"     : "string"          返回信息
     *                      "mercId"      : "string"          商户识别号
     *                      "signValue"   : "string"          签名域
     *                      "key"         : "string"          商户密钥
     *                      **如果check_flag=1
     *                      "trmNo"       : "string"          设备号
     *                      "stoe_id"     : "string"          门店号
     *                }
     * @throws Exception
     * @return NULL
     */
    public function merchant_query(Request $request)
    {
        $merchant_id = $request->param('merchant_id');
        if (empty($merchant_id)) {
            return false;
        }
        $arr = MerchantIncom::where("merchant_id = $merchant_id")->field(["mercId", "orgNo"])->select();
        //mark
        /**  查询参数 */
        $query = [
            'serviceId' => "6060300", //交易码
            'version' => "V1.0.1",
            'mercId' => $arr['mercId'], //商户识别号（15 位数字）
            'orgNo' => $arr['orgNo'], //机构号
        ];

        /** 得到当前请求的签名，用于和返回参数验证 */
        $query['signValue'] = sign_ature(0000, $query);

        /** 获取返回结果 */
        $res = curl_request($this->url, true, $query, true);
        /** json转成数组 */
        $res = json_decode($res, true);

        $check = $this->check_sign_value($query['signValue'], $res);

        if ($check == true) {
            /** 条件验证成功 更新数据库 */
            $where = [
                'merchant_id' => $merchant_id,
                "mercId" => $res['mercId'],
            ];
            $prev_update_data = [
                "trmNo" => $res['trmNo'],
                "stoe_id" => $res['stoeNo'],
            ];
            $this->insert_to_incom_table('cloud_merchant_incom', $where, $prev_update_data);
        }


    }


    /**
     * MCC查询 2.2
     * @param array    [    *参数名            *类型     *描述
     *                     'merchant_id'  =>  int      商户ID
     *                     'orgNo'   =>   string       合作商机构号
     *                 ]
     * @description  array{
     *                      "mcc_cd"      : "string"        MCC 码
     *                      "mcc_nm"      : "string"        MCC 名称
     *                      "sup_mcc_cd"  : "string"        MCC 大类
     *                      "sup_mcc_nm"  : "string"        MCC 大类名称
     *                      "mcc_typ"     : "string"        MCC 小类
     *                      "mcc_typ_nm"  : "string"        MCC 小类名称
     *                }
     * @throws Exception
     * @return NULL
     *
     */
    public function merchant_MCC(Request $request)
    {

        $id = $request->param('id');
        if (empty($id)) {
            return false;
        }
        $arr = MerchantIncom::where("merchant_id = $id")->field("orgNo")->find();

        $query = [
            'serviceId' => "6060203",
            'version' => "V1.0.1",
            'orgNo' => $arr->getData("orgNo"),
        ];
        $query['signValue'] = sign_ature(0000, $query);

        $res = curl_request($this->url, true, $query, true);

        /** json转成数组 */
        $res = json_decode($res, true);

        $check = $this->check_sign_value($query['signValue'], $res);

        if ($check === true) {
            $where = [
                "merchant_id" => $id,
                "mercId" => $res['mercId'],
            ];
            $data = [
                'mcc_cd' => $res['mcc_cd'],
                'mcc_nm' => $res['mcc_nm'],
                'sup_mcc_cd' => $res['sup_mcc_cd'],
                'sup_mcc_nm' => $res['sup_mcc_nm'],
                'mcc_typ' => $res['mcc_typ'],
                'mcc_typ_nm' => $res['mcc_typ_nm'],
            ];
            /** 检验无误插入数据库 */
            $this->insert_to_incom_table("cloud_merchant_incom", $where, $data);
        }

    }

    /**
     * 区域码查询
     * @param Request $request
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_area_code(Request $request)
    {
        $id = $request->param("merchant_id");
        if (empty($id)) {
            return false;
        }
        $arr = Db::name("area_code")->where("merchant_id = $id")->field("orgNo,prov_nm,city_nm")->find();
        $query = [
            'serviceId' => "6060206",
            'version' => "V1.0.1",
            'orgNo' => $arr->getData("orgNo"),
            'prov_nm' => $arr->getData("prov_nm"),
            'city_nm' => $arr->getData("city_nm"),
        ];
        $query['signValue'] = sign_ature(0000, $query);

        $res = curl_request($this->url, true, $query, true);

        /** json转成数组 */
        $res = json_decode($res, true);
        $check = $this->check_sign_value($query['signValue'], $res);

        if ($check === true) {
            $where = [
                "merchant_id" => $id,
            ];
            /** @var array 要更新/插入的数据 $data */
            $data = [
                'merc_area' => $res['merc_area'],
                'area_nm' => $res['area_nm'],
                'merc_prov' => $res['merc_prov'],
                'prov_nm' => $res['prov_nm'],
                'merc_city' => $res['merc_city'],
                'city_nm' => $res['city_nm'],
            ];
            /** 检验无误插入数据库 */
            $this->insert_to_incom_table("cloud_area_code",$where, $data);
        }

    }


    public function bank_query(Request $request) {


        $arr = $request->param();
        $id = $request->param("merchant_id");
        if (empty($id)) {
            return false;
        }
        $result = MerchantIncom::where("merchant_id=$id")->field("orgNo")->find();
        $query = [
            'serviceId' => "6060208",
            'version' => "V1.0.1",
            'lbnk_nm' => $arr['lbnk_nm'],  //最少输入不低于5个字
            'orgNo' => $result->getData('orgNo'),
        ];
        $query['signValue'] = sign_ature(0000, $query);

        $res = curl_request($this->url, true, $query, true);

        /** json转成数组 */
        $res = json_decode($res, true);
        $check = $this->check_sign_value($query['signValue'], $res);

        $data['wc_lbnk_no'] = $res['wc_lbnk_no'];
        $data['lbnk_nm'] = $res['lbnk_nm'];
        if ($check === true) {
            return_msg(200,"查询成功",$data);
        }
    }
    /**
     * 商户进件
     *
     * @return \think\Response
     *
     */
    public function merchant_incom(Request $request)
    {
        $data = $request->post();

        $signValue = sign_ature(518, $data, "9773BCF5BAC01078C9479E67919157B8");

        $data = [
            'serviceId' => 6060601,
            'version' => 'V1.0.1',
            'stl_sign' => 1,
            'orgNo' => 518,
            'stl_oac' => 6230582000005972594,
            'bnk_acnm' => '蜡笔小新',
            'wc_lbnk_no' => 783290000010,
            'bus_lic_no' => "9111010506281144",
            'bse_lice_nm' => '北京城南春贸有限公司',
            'crp_nm' => '蜡笔小新',
            'mercAdds' => '北京市朝阳区西大望路甲12号（国家广告产业园区）2号楼2层20182',
            'bus_exp_dt' => '2033-02-21',
            'crp_id_no' => 230221197907042813,
            'crp_exp_dt' => '2027-05-16',
            'stoe_nm' => '北京城南春贸有限公司',
            'stoe_cnt_nm' => '蜡笔小新',
            'stoe_cnt_tel' => 18811198886,
            'mcc_cd' => 5039,
            'stoe_area_cod' => 110105,
            'stoe_adds' => '北京市朝阳区西大望路甲12号（国家广告产业园区）2号楼2层20182',
            'trm_rec' => 1,
            'mailbox' => 'yunshangfu@163.com',
            'alipay_flg' => 'Y',
            'yhkpay_flg' => 'N',
            'signValue' => $signValue,
        ];
//        dump($data);
        //获取商户id
        $data['merchant_id'] = 1;
        $info = MerchantStore::insert($data);
        $data['signValue'] = sign_ature(0000, $data);

//        var_dump(json_encode($data));die;
        if ($info) {
            //发送给新大陆
            unset($data['merchant_id']);
            $result = curl_request($this->url, true, $data, true);
            $result = json_decode($result);
            $signValue = sign_ature(1111, $result);
            if ($result['msg_cd'] == '000000' && $result['signValue'] == $signValue) {
                //审核通过
                //跟新数据库
                $merchant_id = 1;
                $arr['log_no'] = $result['log_no'];
                $arr['mercId'] = $result['mercId'];
                $arr['stoe_id'] = $result['stoe_id'];
                $res = MerchantStore::where('merchant_id', $merchant_id)->update($arr, true);
                if ($res) {
                    return_msg(200, 'success', $result['msg_dat']);
                } else {
                    return_msg(400, 'failure');
                }
            }
        } else {
            return_msg(400, 'failure');
        }
    }

    /**
     * 商户进件
     * @param Request $request
     */
    public function merchant_incoms(Request $request)
    {
        //获取商户id
        $data = $request->post();
//        $data['serviceId']=6060601;
//        $data['version']='V1.0.3';
        $data = [
            'serviceId' => 6060601,
            'version' => 'V1.0.1',
            'stl_sign' => 1,
            'orgNo' => 518,
            'stl_oac' => 6230582000005972594,
            'bnk_acnm' => '蜡笔小新',
            'wc_lbnk_no' => 783290000010,
            'bus_lic_no' => "9111010506281144",
            'bse_lice_nm' => '北京城南春贸有限公司',
            'crp_nm' => '蜡笔小新',
            'mercAdds' => '北京市朝阳区西大望路甲12号（国家广告产业园区）2号楼2层20182',
            'bus_exp_dt' => '2033-02-21',
            'crp_id_no' => 230221197907042813,
            'crp_exp_dt' => '2027-05-16',
            'stoe_nm' => '北京城南春贸有限公司',
            'stoe_cnt_nm' => '蜡笔小新',
            'stoe_cnt_tel' => 18811198886,
            'mcc_cd' => 5039,
            'stoe_area_cod' => 110105,
            'stoe_adds' => '北京市朝阳区西大望路甲12号（国家广告产业园区）2号楼2层20182',
            'trm_rec' => 1,
            'mailbox' => 'yunshangfu@163.com',
            'alipay_flg' => 'Y',
            'yhkpay_flg' => 'N',
        ];
//        dump($data);die;
        $data['signValue'] = get_sign($data);

//        dump($data['signValue']);die;
        $data = json_encode($data);

//        echo $data;die;
//        unset($data['merchant_id']);
//        var_dump(json_encode($data));die;
        $result = curl_request('http://sandbox.starpos.com.cn/emercapp', true, $data, true);
//        echo $result;die;
        $result = json_decode($result);

        //判断签名
        $signValue = sign_ature(1111, $result);
        if ($result['msg_cd'] == 000000 && $result['signValue'] == $signValue) {
            $data['merchant_id'] = $request->post('merchant_id');
            $data['log_no'] = $result['log_no'];
            $data['mercId'] = $result['mercId'];
            $data['stoe_id'] = $result['stoe_id'];
            $info = MerchantStore::insert($data);
            if ($info) {
                return_msg(200, 'success', $result['msg_dat']);
            } else {
                return_msg(400, 'failure');
            }
        }
    }

    /**
     * 商户提交
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function merchant_create(Request $request)
    {
        $merchant_id = 1;
        //取出数据表中数据
        $data = MerchantStore::where('merchant_id', $merchant_id)->field('serviceId,version,mercId,log_no,orgNo')->find();
        $data['signValue'] = sign_ature(0000, $data);
        $result = curl_request($this->url, true, $data, true);
        $result = json_decode($result, true);
        if ($result['msg_cd'] == 000000) {
            //修改数据表状态
            $res = MerchantStore::where('merchant_id', $merchant_id)->update(['check_flag' => $result['check_flag']]);
            if ($res) {
                return_msg(200, 'success', $result['msg_dat']);
            } else {
                return_msg(400, 'failure');
            }
        }
    }

    /**
     * 图片上传
     *
     * @param  int $id
     * @return \think\Response
     */
    public function img_upload(Request $request)
    {
        $merchant_id = 1;
        $info = $request->post();
        //取出当前商户信息
        $data = MerchantStore::where('merchant_id', $merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id')->find();
        $data['imgTyp'] = $info['imgTyp'];
        $data['imgNm'] = $info['imgNm'];
        $img = $this->upload_logo();
        $img = json_encode($img);
        $data['imgFile'] = $img;
//        $data=MerchantStore::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $data['signValue'] = sign_ature(0000, $data);
        //发送给新大陆
        $result = json_decode(curl_request($this->url, true, $data, true), true);
        if ($result['msg_cd'] == '000000') {
            $res = MerchantStore::where('merchant_id', $merchant_id)->update($data);
            if ($res) {
                return_msg(200, 'success', $result['msg_dat']);
            } else {
                return_msg(400, 'failure');
            }
        } else {
            return_msg(400, 'failure');
        }
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

    //上传图片
    private function upload_logo()
    {
        $files = request()->file('imgFile');
        $goods_pics = [];
        foreach ($files as $file) {
            $info = $file->validate(['size' => 500 * 1024, 'ext' => 'jpg,jpeg,gif,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                //图片上传成功
                $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
                $goods_logo = str_replace('\\', '/', $goods_logo);
                $goods_pics[] = $goods_logo;
            } else {
                $error = $info->getError();
                return_msg(400, $error);
            }
        }
        return $goods_pics;
    }

    /**
     * 商户修改申请
     * @param Request $request
     */
    public function mermachant_edit(Request $request)
    {
        $id = $request->param('id');
        $serviceId = 6060605;
        $version = 'v1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $id)->field('mercId,orgNo')->select();

        $resul = ['serviceId' => $serviceId, 'version' => $version, 'mercId' => $data[0]['mercId'], 'orgNo' => $data[0]['orgNo']];
        $resul_age = sign_ature(0000, $resul);
        $arr['signValue'] = $resul_age;
        $par = curl_request($this->url, true, json_encode($arr), true);

        $bbntu = json_decode($par);
        $return_sign = sign_ature(1111, $resul);
        if ($bbntu['msg_cd'] === 000000 && $return_sign == $bbntu['signValue']) {
            $statu = Db::table('merchant_incom')->where('merchant_id', $id)->update(['store_sn' => $bbntu['stoe_id'], 'log_no' => $bbntu['log_no'], 'alter' => 1]);
            return_msg(200, '商户修改申请成功');
        } else {
            return_msg(400, '商户修改申请失败');
        }

    }

    /**
     * 商户资料修改
     * @param Request $request
     */
    public function commercial_edit(Request $request)
    {
        $del = $request->post();

        $aa['serviceId'] = 6060604;
        $aa['version'] = 'V1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $del['merchant_id'])->field('log_no,mercId,store_sn')->select();
        $aa = [];
        foreach ($data as $k => $v) {
            $aa = $v;
        }
        $dells = $del + $aa;
//        //获取加密的签名域
        $sign_ature = sign_ature(0000, $dells);
        $del['signValue'] = $sign_ature;
        //向新大陆接口发送信息验证
        $par = curl_request($this->url, true, $del, true);

        $par = json_decode($par);
        //返回数据的签名域


        $return_sign = sign_ature(1111, $par);
        if ($par['msg_cd'] == 000000 && $par['signValue'] == $return_sign) {
//            $rebul=Db::table('think_user')->where('merchant_id',$del['merchant_id'])->update($del);
            return_msg(200, '修改成功');
        } else {
            return_msg(400, '修改失败');
        }


    }

    /**
     * 商户查询审核结果
     * @param Request $request
     */

    public function mercachant_inquire(Request $request)
    {
        $id = $request->param('id');
        $arr = Db::name('merchant_incom')->where('merchant_id', $id)->field('mercId，orgNo')->select();
        $data = ['serviceId' => 6060300, 'version' => 'V1.0.1', 'mercId' => $arr[0]['mercId'], 'orgNo' => $arr[0]['orgNo']];
        //签名域
        $signValue = sign_ature(0000, $data);
        $data['signValue'] = $signValue;
        $par = curl_request($this->url, true, $data, true);
        $par = json_decode($par);
        $return_sign = sign_ature(1111, $par);
        if ($par['msg_cd'] == 000000 && $par['signValue'] == $return_sign) {

            return_msg('200', '审核成功');
        } else {
            return_msg('400', '非法报文');
        }

    }

    /**
     * 新增门店
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function shop_add(Request $request)
    {
        $data = $request->post();
        //查询商户的流水号、识别号
        $log_no = Db::name('merchant_incom')->where('merchant_id', $data['merchant_id'])->field('mercId,log_no')->select();
        $data['mercId'] = $log_no[0]['mercId'];
        $data['log_no'] = $log_no[0]['log_no'];
        //存入门店数据
        $create_id = Db::name('merchant_shop')->insertGetId($data);
        if (!$create_id) {
            return_msg(400, '数据不正确');
        }
        $sign_value = sign_ature(0000, $data);
        $data['signValue'] = $sign_value;
        $data['serviceId'] = 6060602;
        $data['version'] = 'V1.0.1';
        $data['mercId'] = $log_no[0]['mercId'];
        $data['log_no'] = $log_no[0]['log_no'];
        $shop_api = curl_request($this->url, true, $data, true);
//
        $shop_api = json_decode($shop_api);
        $array = [];
        foreach ($shop_api as $k => $v) {
            $array[$k] = $v;
        }
//        dump($array);
        $return_sign = sign_ature(1111, $array);
        if ($array['msg_cd'] == 000000 && $array['signValue'] == $return_sign) {
            $datle = ['id' => $create_id, 'stoe_id' => $array['stoe_id']];
            //返回成功
            Db::name('merchant_shop')->update($datle);
            return_msg(200, '操作成功');
        } else {

        }

    }

}
