<?php

namespace app\merchant\controller;

use app\admin\model\Mcc;
use app\admin\model\TotalQrcode;
use app\agent\model\MerchantIncom;
use app\merchant\model\MerchantShop;

use app\merchant\model\SubBranch;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Request;
use think\Session;


class Shop extends Commonality
{

    public $url = 'https://gateway.starpos.com.cn/emercapp';


    /**
     * unknow - Func
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $merchant_id = $this->merchant_id;
        //bnk_acnm 户名  wc_lbnk_no 开户行  stl_sign 结算标志  stl_oac结算账户 icrp_id_no 结算人身份证号    crp_exp_dt_tmp结算人身份证有限期
        $data = MerchantIncom::where('merchant_id', $merchant_id)->field('icrp_id_no,crp_exp_dt_tmp,stl_oac,bnk_acnm,wc_lbnk_no,stl_sign')->find();

        if (!$data['stl_sign']) {
            $name = SubBranch::where('lbnk_no', $data['wc_lbnk_no'])->field('lbnk_nm')->find();
            $data['lbnk_nm'] = $name['lbnk_nm'];
        }
        //显示所有一级分类
        $data['category']=Mcc::field('sup_mcc_cd,sup_mcc_nm')->group('sup_mcc_cd')->select();
        return_msg(200, 'success', $data);
    }

    //获取二级分类和三级分类
    public function getCatePid()
    {
        $sup_mcc_cd=request()->param('sup_mcc_cd');
        $data=Mcc::field('mcc_nm,mcc_cd')->where('sup_mcc_cd',$sup_mcc_cd)->select();
        return_msg(200,'success',$data);
    }

    /**
     * 查询支行名称   联号
     * @param Request $request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index_query(Request $request)
    {
        //lbnk_nm 支行名称
        $name = $request->param('lbnk_nm');
        if ($name) {
            $data = SubBranch::where('lbnk_nm', 'like', "$name%")->field('lbnk_nm,lbnk_no')->select();
            if ($data) {
                return_msg(200, 'success', $data);
            } else {
                return_msg(400, 'error', '支行名称填写错误,请重新填写');
            }
        } else {
            return_msg(500, 'error', '请输入支行名称');
        }

    }

    /**
     * 门店进件
     * @param [array] Request
     * @throws DbException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     */
    public function shop_incom(Request $request)
    {
        //echo 1;die;
//        echo $this->merchant_id;die;
        $data = $request->post();
        check_params("add_shop", $data, "MerchantValidate");
        $Merc = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        if ( $Merc["status"] != 2) {

            //$key = "7B7B64051113E3EB09725CBD69A55E79";
            $query["serviceId"] = "6060605";
            $query["version"] = "V1.0.1";
            $query["mercId"] = (string)$Merc["mercId"];
            $query["orgNo"] = (string)$Merc["orgNo"];

            $query["signValue"] = sign_ature(0000,$query);
//            halt($query);
            /** 商户修改申请 */

            $res = json_decode(curl_request($this->url, true, $query, true), true );
            //halt($res);
            /** 如果错误则返回 */
            if ($res['msg_cd'] != "000000") {
                return_msg(400, "商户修改" . $res["msg_dat"]);
            } else {
                Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->update([
                    "status" => 2,  //商户为  ·修改未完成· 状态
                    "log_no" => $res["log_no"], //操作完修改流水号
                ]);
                $this->merchant_shop($data);
            }
        }else{
            $this->merchant_shop($data);
        }


    }

    public function merchant_shop($data)
    {

        /** 新增门店参数准备 */
        $Merc = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        check_params("add_shop", $data, "MerchantValidate");
        if ($data["stl_sign"] == 1) {
            $data["stl_typ"] = "1";
        }
        $data["yhkpay_flg"] = "Y";
        $data["alipay_flg"] = "Y";
        $data['serviceId'] = "6060602";
        $data["mercId"] = $Merc["mercId"];
        $data['tranTyps'] = 'C1';
        $data["log_no"] = $Merc["log_no"];
        $data["stoe_area_cod"] = (string)$data["stoe_area_cod"];
        $data["fee_rat_scan"] = $Merc["fee_rat_scan"];
        $data["fee_rat1_scan"] = $Merc["fee_rat1_scan"];
        $data["fee_rat2_scan"] = $Merc["fee_rat2_scan"];
        $data["fee_rat3_scan"] = $Merc["fee_rat3_scan"];
        $data["mcc_cd"] = (string)$Merc["mcc_cd"];
        $data['version'] = 'V1.0.4';
        $data['fee_rat'] = $Merc['fee_rat'];
        $data['max_fee_amt'] = $Merc['max_fee_amt'];
        $data['fee_rat1'] = $Merc['fee_rat1'];
        $data['ysfcreditfee'] = $Merc['ysfcreditfee'];
        $data['ysfdebitfee'] = $Merc['ysfdebitfee'];
        $data['stl_typ'] = $Merc["stl_typ"];
        $data['icrp_id_no'] = $data["icrp_id_no"];
        $data['crp_exp_dt_tmp'] = $data["crp_exp_dt_tmp"];
        $data['stl_oac'] = $data["stl_oac"];
        $data['bnk_acnm'] = $data["bnk_acnm"];
        $data['wc_lbnk_no'] = $data["wc_lbnk_no"];
        $data['stoe_nm'] = $data["stoe_nm"];
        $data['stoe_cnt_nm'] = $data["stoe_cnt_nm"];
        $data['stoe_cnt_tel'] = $data["stoe_cnt_tel"];
        $data['mcc_cd'] = $data["mcc_cd"];
        $data['stoe_adds'] = $data["stoe_adds"];
        $data['trm_rec'] = $data["trm_rec"];
        $data['mailbox'] = $data["mailbox"];
        $data['cardTyp'] = '00';
        //halt($data);
        /*$pc = substr($data["stoe_adds"] , 0,strripos($data["stoe_adds"] , ","));
        $data['stoe_adds'] = str_replace(",",'',$data["stoe_adds"]);*/
        $data['orgNo'] = $Merc["orgNo"];
//        halt($data);
//        $pc = str_replace(",","", $pc);
//        $data['stoe_nm'] = $pc . $data['shop_name'];
//        check_params("add_store", $data, 'MerchantValidate');
        //签名域
        $data['signValue'] = sign_ature(0000, $data);
        //halt($data);
        //向新大陆接口发送请求信息
        $shop_api = json_decode(curl_request($this->url, true, $data, true), true );
//        halt($shop_api);
        if ($shop_api["msg_cd"] != "000000") {
            return_msg(400, $shop_api["msg_dat"]);
        } else {
            /** 成功把返回的门店信息存入数据库 */
            $data["merchant_id"] = $this->merchant_id;
            $data["shop_name"] = $data["shop_name"];
            $data["log_no"] = $shop_api["log_no"];
            $data["mercId"] = $shop_api["mercId"];
            $data["stoe_id"] = $shop_api["stoe_id"];
            $data["scan_stoe_cnm"] = "云商付"; //扫码小票商户名
            $shop_id = Db::name("merchant_shop")->insertGetId($data);
            $shop_info = Db::name("merchant_shop")->where("id",$shop_id)->find();
            if($shop_id) {
                return_msg(200, "成功", $shop_id);
            }else {
                return_msg(400, "失败");
            }

        }
    }


    /**
     * 商户提交
     * @param array $param
     */
    public function merc_commit()
    {
        $shop_id = request()->param('shop_id');
        $res = Db::name("merchant_shop")->where("id", $shop_id)->find();
        $param["mercId"] = $res["mercId"];
        $param["log_no"] = $res["log_no"];
        $param["serviceId"] = "6060603";
        $param["version"] = "V1.0.1";
        $param["orgNo"] = "27573";
        $param["signValue"] = sign_ature("0000", $param);

        $res = curl_request($this->url, true, $param, true);
        $res = json_decode($res, true);
        //halt($res);
        if ($res["msg_cd"] != "000000") {
            return_msg(400, $res["msg_dat"]);
        } else {
            /** $data 组合返回数据并入库 */
            if ($res["check_flag"] == "1") {
                $data["status"] = 1;
                $data["key"] = $res["key"];
                $data["rec"] = $res["trmNo"];
                $data["merchant_id"] = $this->merchant_id;
                //$data["mercId"] = (int)$res["mercId"];
                $result = Db::name("merchant_shop")->where('id',$param["shop_id"])->update($data);
                if ($result) {
                    return_msg(200, "操作成功！", $param["shop_id"]);
                } else {
                    return_msg(400, "操作失败");
                }
            }elseif($res["check_flag"] == "3"){
                $data["status"] = 1;

                $data["merchant_id"] = $this->merchant_id;
                //$data["mercId"] = (int)$res["mercId"];
                $result = Db::name("merchant_shop")->where('id',$param["shop_id"])->update($data);
                if ($result) {
                    return_msg(200, "转人工！", $param["shop_id"]);
                } else {
                    return_msg(400, "操作失败");
                }

            }elseif($res["check_flag"] == "2"){
                return_msg(400, "驳回");
            }
            //生成门店二维码
            //$this->qrcode(null,$shop_id);
        }
    }

    /**
     * 上传图片
     * imgTyp   图片类型    6 - 门头照  7 - 场景照   8 - 收银台照
     * imgNm  图片名称  汉字数字和字母，不允许有特殊字符
     * imgFile   图片（不参与验签）  图片转成十六进制，图片不能超过500KB
     * @param Request $request
     */
    public function image(Request $request)
    {
        // 获取表单上传文件
        $val = $request->post();
        $shop_id = $request->post("shop_id");
        if(empty($shop_id)){
            return_msg(400, "请输入门店ID");
        }
        $dbs = Db::name("merchant_shop")->where("id", $shop_id)->find();
        $file = $request->file('imgFile');
        $data['imgFile'] = bin2hex(file_get_contents($file->getRealPath()));//进件参数
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => 512000, 'ext' => 'jpg,png,jpeg'])->move(ROOT_PATH.'public'.DS);

        if ($info) {
            $data['imgTyp'] = $val['imgTyp'];//图片类型    6 - 门头照  7 - 场景照   8 - 收银台照
            $data['imgNm'] = $val['imgNm']; //图片名称  汉字数字和字母，不允许有特殊字符
            $data['log_no'] = $dbs['log_no'];
            $data["stoe_id"] = $dbs["stoe_id"];
            $data['mercId'] = $dbs['mercId'];
            $result = $this->upload_pictures($data);//调进件公共参数   传入图片信息
            if ($result == 1) {
                $arr['imgTyp'] = $val['imgTyp'];
                $arr['imgFile'] = $goods_logo = DS.'uploads'.DS.$info->getSaveName();
                $arr['imgFile'] = str_replace('\\','/',$arr['imgFile']);
                //halt($arr['imgFile']);
                $db = $this->hosting($shop_id, $arr); //入库

                if (!$db) {
                    return_msg(400, 'error', '图片保存失败');
                } else {   //保存成功
                    return_msg(200, 'success', ['file_path' => $arr['imgFile']]);

                }
            } else {
                return_msg(400, 'error', $result['msg_dat']);

            }

        } else {
            // 上传失败获取错误信息
            return_msg(400, 'error', '图片格式错误或照片过大，照片不得大于500KB');
        }


    }

    /**
     * 图片入库
     * @param $id
     * @param $arr
     * @param $val
     * @return bool
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \Exception
     */
    public function hosting($id, $arr)
    {
        //取出之前存入的图片 整合再存入
        $ms = MerchantShop::where('id', $id)->field('imgFile')->find();
        check_data($ms,'', 0);
        $ms = $ms->toArray();

        if ($ms["imgFile"] !== "") {
            $newArr = $ms["imgFile"] . json_encode($arr);
        }else {
            $newArr = json_encode($arr);
        }

        $data = MerchantShop::where('id', $id)->update(['imgFile' => $newArr]);
        return $data ? true : false;

    }

    /**
     * 上传图片公共参数进件
     * @param array $arr
     * @param Request $request
     * @throws DbException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @return bool
     */
    public function upload_pictures($arr)
    {

        /*$merchant_id = "210";

        $data = MerchantIncom::alias('a')
            ->field('a.mercId,a.log_no,b.stoe_id')
            ->join('merchant_shop b', 'b.merchant_id=a.merchant_id')
            ->where('a.merchant_id', $merchant_id)
            ->find();*/

        $data['imgTyp'] = $arr['imgTyp'];
        $data['imgNm'] = $arr['imgNm'];
        $data['imgFile'] = $arr['imgFile'];
        $data['serviceId'] = '6060606';
        $data['version'] = 'V1.0.1';
        $data['orgNo'] = "27573";
        $data['mercId'] = $arr['mercId'];
        $data["stoe_id"] = $arr["stoe_id"];
        $data['log_no']  = $arr['log_no'];
//        $data['merchant_id'] = $merchant_id     ;
        //$data = $data->toArray();
        //halt($data);
        return $this->send($data);
    }

    /**
     * 图片上传消息发送接口
     * @param  array $data
     * @return int / Exception
     */
    protected function send($data)
    {
        //获取签名
        $data['signValue'] = sign_ature(0000, $data);

        //发送给新大陆
        $result = json_decode(curl_request($this->url, true, $data, true), true);
        //halt($result);
        if ($result['msg_cd'] !== '000000') {
            return_msg(400, $result["msg_dat"]);
        }else {
            return 1;
        }

    }


    /**
     * 新增门店页面展示及mcc码查询
     * @param Request $request
     * @return string
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function shop_query(Request $request)
    {
        if ($request->post()) {
            $name = $request->param('name');
            $data = Db::name('cloud_mcc')->where('name|comment|explain', 'like', "%$name%")->select();
            return json_encode($data);
        } else if ($request->get()) {
            $merchant_id = \session('merchant_id');
            $merchant_id = 1;
            //stl_oac结算账户 bnk_acnm户名 icrp_id_no结算人身份证号
            // crp_exp_dt_tmp结算人身份证有限期  wc_lbnk_no开户行
            $data = Db::name('merchant_incom')->where('merchant_id', $merchant_id)
                ->field('stl_sign,stl_oac,bnk_acnm,icrp_id_no,crp_exp_dt_tmp,wc_lbnk_no')
                ->find();
            $data = array_merge($data, [1 => '对私', 0 => '对公']);
            if ($data['stl_sign'] == 1) {
                $data = [1 => '对私'];
            }
            return json_encode($data);
        }
    }

    /**
     * 上传图片
     * @param Request $request
     * @return string
     */
    public function image_uplode(Request $request)
    {
        $file = $request->file('image');

        $a = substr($file->getMime(), 0, 5);


        if ($a === 'image') {
            return image_thumbnail($file);
        } else {
            return_msg(400, 'error', '图片类型错误');
        }
    }


    /**
     * 显示当前商户门店
     *
     * @return \think\Response
     * @throws Exception
     */
    public function shop_list(Request $request)
    {
        if ($request->isGet()) {
            $merchant_id = Session::get("username_", "app")["id"];
            $rows = MerchantShop::where("merchant_id= $merchant_id")->count("id");
            $pages = page($rows);
            $res['list'] = MerchantShop::where("merchant_id= $merchant_id")
                ->limit($pages['offset'], $pages['limit'])
                ->field("shop_name,stoe_adds,id")
                ->select();

            $res['pages']['rows'] = $rows;
            $res['pages'] = $pages;
            check_data($res["list"]);
        }
    }


    /**
     *
     * @param [string] keywords  关键字 模糊搜索
     * @param  \think\Request $request
     * @return \think\Response
     * @throws Exception DbException
     */
    public function search_shop(Request $request)
    {
        if ($request->isPost()) {
            $param = $request->param();
            /** 身份为商户 */
            $param['merchant_id'] = $this->id;
            /** 身份为员工 */
//            $param['user_id'] = Session::get("user_id", "merchant");
            $rows = MerchantShop::where([
                "merchant_id" => ["eq", $param['merchant_id']],
                "shop_name" => ["LIKE", $param["keywords"] . "%"]
            ])->count("id");
            $pages = page($rows);
            $res['list'] = MerchantShop::where([
                "merchant_id" => ["eq", $param['merchant_id']],
                "shop_name" => ["LIKE", $param["keywords"] . "%"]
            ])
                ->limit($pages['offset'], $pages['limit'])
                ->field("id,shop_name,stoe_adds")
                ->select();

            $res['pages']['rows'] = $rows;
            $res['pages'] = $pages;
            if (count($res['list']) < 1) {
                return_msg(400, "没有数据");
            }
            return (json_encode($res));


        }
    }

    /**
     * 显示门店详情
     * @param [int] $id 门店ID
     * @method GET
     * @param  int $id
     * @return \think\Response
     * @throws Exception
     */
    public function shop_detail(Request $request)
    {
        $param = $request->param();
        if ($request->isGet()) {
            $res = MerchantShop::where("id", $param["id"])->field([
                'id', "shop_name", "stoe_adds", "stoe_cnt_tel", "imgFile"
            ])->find();
            if (count($res->toArray()) > 0) {
                $res['imgFile'] = json_decode($res['imgFile']);
                return_msg(200, "success", $res);
            }
            //mark  没有门店评价表
        }
    }


    /**
     * 获取门店评价 0-好评 1-差评
     * @param Request $request
     * @throws DbException
     */
    public function shop_comment(Request $request)
    {
        if ($request->isGet()) {
            $param['shop_id'] = $request->param('id');

            $comments = MerchantShop::get($param['shop_id']);
            $c_list = collection($comments->comments)->toArray();

            if (count($c_list) > 0) {
                return_msg(200, "success", $c_list);
            } else {
                return_msg(400, "没有数据");
            }
        }
    }

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
     * 区域码查询
     * @param $data
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function address($data)
    {

        $name = explode(',', $data);

        if (!count($name) < 4) {

            $area = $name[2];//区
            $city = $name[1];//市

            $data = Db::name('area_code')->where(['area_nm' => ['like', "%$area"], 'city_nm' => ['like', "%$city"]])->field('merc_area')->find();
            if ($data) {
                return $data->merc_area;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    /**
     * 我的门店
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pc_myShop()
    {

        if ($this->name == "user_id") {
            $merchant_id = Db::name("merchant_user")->where("id",$this->id)->find()["merchant_id"];
        }else {
            $merchant_id = $this->id;
        }
        $rows = MerchantShop::where('merchant_id', $merchant_id)->field('id')->count('id');
        $pages = page($rows);
        $res['list'] = MerchantShop::where('merchant_id', $merchant_id)
            ->limit($pages['offset'], $pages['limit'])
            ->field('shop_name,stoe_cnt_nm,id,stoe_adds,stoe_cnt_tel')
            ->select();

        $res['pages']['rows'] = $rows;
        $res['pages'] = $pages;
        check_data($res["list"], $res);
    }

    /**
     * 我的门店 查询
     * shop_name   门店名称
     * paymentorder  付款顺序   1先上菜后付款   2先付款后上菜
     * @param Request $request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pc_myShopQuery(Request $request)
    {

        $ky = $request->param("shop_name");
        if ($ky) {
            $k_f = "LIKE";
            $ky = $ky."%";
        }else {
            $k_f = "NOT LIKE";
            $ky = "-2";
        }
        if ($this->name == "user_id") {
            $user = Db::name("merchant_user")->where("id", $this->id)->find();
            $user = $user["merchant_id"];
        }else {
            $user = $this->id;
        }
        $where = [
            "merchant_id" => ["eq", $user],
            "shop_name"  => [$k_f, $ky],
        ];
        $row = Db::name("merchant_shop")->where($where)->count();

        $pages = page($row);
        $shop["list"] = Db::name("merchant_shop")->where($where)->limit($pages["offset"], $pages["limit"])->select();

        $shop["pages"] = $pages;
        check_data($shop["list"], $shop);



    }

    /**
     * 门店详情
     * shop_id  门店id
     * @param Request $request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pc_particulars(Request $request)
    {

        $shop_id = $request->param('shop_id');
        $data = MerchantShop::where(['id' => $shop_id])
            ->field('shop_name,id,stoe_adds,stoe_cnt_tel,mailbox,stoe_cnt_nm,imgFile')->find();

        $res = TotalQrcode::where('shop_id',$shop_id)->find();
        $data['qrcode'] = $res['qrcode'];
        $data['imgFile'] = json_decode($data->imgFile, true);

        return_msg(200, 'success', $data);
    }

}