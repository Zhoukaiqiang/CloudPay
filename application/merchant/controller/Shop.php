<?php

namespace app\merchant\controller;

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

        return_msg(200, 'success', $data);
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
     * @param Request
     * @throws DbException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     */
    public function shop_incom(Request $request)
    {

        $data = $request->post();
        $Merc = Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->find();
        $key = $Merc["key"] ? $Merc["key"] : null;
        if (!$key) {
            return_msg(400, "失败");
        }
        $query = [
            "serviceId" => "6060605",
            "version" => "V1.0.1",
            "mercId" => (string)$Merc["mercId"],
            "orgNo" => (string)$Merc["orgNo"],
        ];
        $query["signValue"] = sign_ature(0000, $query, $key);
        /** 商户修改申请 */
        $res = curl_request($this->url, true, $query, true);
        $res = json_decode($res, true);
        /** 如果错误则返回 */
        if ($res['msg_cd'] != "000000") {
            return_msg(400, $res["msg_dat"]);
        } else {
            Db::name("merchant_incom")->where("merchant_id", $this->merchant_id)->update([
                "status" => 2,  //商户为  ·修改未完成· 状态
                "log_no" => $res["log_no"], // 操作完修改流水号
            ]);
        }
        /** 新增门店参数准备 */

        $data['serviceId'] = "6060602";
        $data["mercId"] = $Merc["mercId"];
        $data["log_no"] = $res["log_no"];
        $data['version'] = 'V1.0.4';
        $data['stl_sign'] = $Merc["stl_sign"];
        $data['stl_typ'] = $Merc["stl_typ"];
        $data['orgNo'] = $Merc["orgNo"];
        $data['stoe_nm'] = $data['shop_name'];
        check_params("add_store", $data, 'MerchantValidate');
        //签名域
        $data['signValue'] = sign_ature(0000, $data);
        //向新大陆接口发送请求信息
        $shop_api = curl_request($this->url, true, $data, true);
        $shop_api = json_decode($shop_api, true);
        if ($shop_api["msg_cd"] != "000000") {
            return_msg(400, $shop_api["msg_dat"]);
        } else {
            //mark 新增门店成功返回的数据还待确认
            /** 成功把返回的门店信息存入数据库 */
            $data["merchant_id"] = $this->merchant_id;
            $data["shop_name"] = $data["stoe_nm"];
            $data["log_no"] = $shop_api["log_no"];
            $data["mercId"] = $shop_api["mercId"];
            $data["stoe_id"] = $shop_api["stoe_id"];

            $shop_id = Db::name("merchant_shop")->insertGetId($data);

            /**商户提交  */
            $query["mercId"] = $shop_api["mercId"];
            $query["log_no"] = $shop_api["log_no"];
            $query["mercId"] = $shop_api["mercId"];
            $query["orgNo"] = "27573";
            $query["shop_id"] = $shop_id;
            $this->merc_commit($query);
        }
    }


    /**
     * 商户提交
     * @param array $param
     */
    public function merc_commit(Array $param)
    {
        $param["serviceId"] = "6060603";
        $param["version"] = "V1.0.1";
        $param["signValue"] = sign_ature("0000", $param);
        $res = curl_request($this->url, true, $param, true);
        $res = json_decode($res, true);
        if ($res["msg_cd"] != "000000") {
            return_msg(400, $res["msg_dat"]);
        } else {
            /** $data 组合返回数据并入库 */
            $data["check_msg"] = $res["check_msg"];
            $data["key"] = $res["key"];
            $data["rec"] = $res["trmNo"];
            $data["merchant_id"] = $this->merchant_id;
            $result = Db::name("merchant_shop")->where($param["shop_id"])->update($data);
            if ($result) {
                return_msg(200, "操作成功！", $param["shop_id"]);
            } else {
                return_msg(400, "操作失败");
            }
            //生成门店二维码
            $this->qrcode();
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

        $file = $request->file('imgFile');

//        var_dump($file->getRealPath());die;
        $data['imgFile'] = bin2hex(file_get_contents($file->getRealPath()));//进件参数
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => 512000, 'ext' => 'jpg,png,jpeg'])->move('uploads/');

        if ($info) {

            $data['imgTyp'] = $val['imgTyp'];//图片类型    6 - 门头照  7 - 场景照   8 - 收银台照
            $data['imgNm'] = $val['imgNm'] . '.' . $info->getExtension(); //图片名称  汉字数字和字母，不允许有特殊字符
//                    var_dump($data);die;
            $result = $this->upload_pictures($data);//调进件公共参数   传入图片信息
            if ($result == 1) {
                $arr['imgTyp'] = $val['imgTyp'];
                $arr['imgFile'] = $info->getPathname();
                $cudle = $this->warehousing($val['shop_id'], $arr); //入库


                if (!$cudle) {
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
     */
    public function warehousing($id, $arr)
    {


        //取出之前存入的图片 整合再存入
        $valls = MerchantShop::where('id', $id)->field('imgFile')->find();

        $val = [];
        if ($valls->imgFile) {   //是否有值
            $valls = json_decode($valls->imgFile, true);
            $valls[$arr['imgTyp']] = $arr['imgFile'];
            $val = json_encode($valls);


        } else {
            $val[$arr['imgTyp']] = $arr['imgFile'];
            $val = json_encode($val);

        }

        $data = MerchantShop::where('id', $id)->update(['imgFile' => json_encode($val)]);
        return $data ? true : false;
    }

    /**
     * 上传图片公共参数进件
     * @param Request $request
     * @throws DbException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upload_pictures($arr)
    {

        $merchant_id = $this->id;

        $data = MerchantIncom::alias('a')
            ->field('a.mercId,a.log_no,b.stoe_id')
            ->join('merchant_shop b', 'b.merchant_id=a.merchant_id')
            ->where('a.merchant_id', $merchant_id)
            ->find();

        $data['imgTyp'] = $arr['imgTyp'];
        $data['imgNm'] = $arr['imgNm'];
        $data['imgFile'] = $arr['imgFile'];
        $data['serviceId'] = '6060606';
        $data['version'] = 'V1.0.1';
        $data['orgNo'] = "518";
//        $data['merchant_id'] = $merchant_id     ;
        $data = $data->toArray();
        return $this->send($data);
    }

    /**
     * 图片上传消息发送接口
     *
     * @param  int $id
     * @return \think\Response
     */
    public function send($data)
    {
        //获取签名
        $data['signValue'] = sign_ature(0000, $data);
//        var_dump($data);die;
        //发送给新大陆
        $result = json_decode(curl_request($this->url, true, $data, true), true);
//        if ($result['msg_cd'] !== '000000') {
//            return_msg(400, $result["msg_dat"]);
//        }
//        var_dump($result);die;
        //生成签名
        $signValue = sign_ature(1111, $result);
//        return_msg(200, 'success', $result);
//        return json_encode($result);
        if ($result['msg_cd'] == '000000' && $result['signValue'] == $signValue) {
            return 1;
        } else {
            return $result;
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

//        $merchant_id = $this->id;
//        //门店名称
//        $shop_name = $request->param('shop_name') ? $request->param('shop_name') : '0';
//        $shopsymbol = $shop_name ? 'like' : '<>';
//
//        //付款顺序   1先上菜后付款   2先付款后上菜
//        $paymentorder = $request->param('paymentorder') ? $request->param('paymentorder') : 0;
//        $paysymbol = $paymentorder ? '=' : '<>';
//        $rows = MerchantShop::where(['merchant_id' => $merchant_id, 'shop_name' => [$shopsymbol, "$shop_name%"], 'paymentorder' => [$paysymbol, $paymentorder]])
//            ->field('id')->count('id');
//        $pages = page($rows);
//        $res['list'] = MerchantShop::where(['merchant_id' => $merchant_id, 'shop_name' => [$shopsymbol, "$shop_name%"], 'paymentorder' => [$paysymbol, $paymentorder]])
//            ->limit($pages['offset'], $pages['limit'])
//            ->field('shop_name,stoe_cnt_nm,id,stoe_adds,stoe_cnt_tel')
//            ->select();
//
//        $res['pages']['rows'] = $rows;
//        $res['pages'] = $pages;
//        if (count($res['list']) < 1) {
//            return_msg(400, 'error', '没有满足条件的门店');
//        } else {
//            return_msg(200, 'success', $res);
//        }


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

        $data['imgFile'] = json_decode($data->imgFile, true);

        return_msg(200, 'success', $data);
    }


}
