<?php

namespace app\merchant\controller;

use app\agent\model\MerchantIncom;
use app\merchant\model\MerchantShop;

use app\merchant\model\SubBranch;
use think\Controller;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Request;
use think\Session;
use app\admin\controller\Incom;
use app\admin\model\IncomImg;

class Shop extends Controller
{

    public $url = 'https://gateway.starpos.com.cn/emercapp';

    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

    }
    public function index()
    {
        $merchant_id=\session('merchant_id');
        $merchant_id=87;
        //bnk_acnm 户名  wc_lbnk_no 开户行  stl_sign 结算标志  stl_oac结算账户 icrp_id_no 结算人身份证号    crp_exp_dt_tmp结算人身份证有限期
        $data=MerchantIncom::where('merchant_id',$merchant_id)->field('icrp_id_no,crp_exp_dt_tmp,stl_oac,bnk_acnm,wc_lbnk_no,stl_sign')->find();
        //1 - 对私  0 - 对公
        if($data->stl_sign==1){
            $data=[];
            $data['stl_sign']=1;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 门店进件
     * @param Request
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index_query(Request $request)
    {
        //lbnk_nm 支行名称
        $name=$request->param('lbnk_nm');
        if($name){
            $data=SubBranch::where('lbnk_nm','like',"%$name%")->field('lbnk_nm,lbnk_no')->select();
            if($data){
                return_msg(200,'success',$data);
            }else{
                return_msg(400,'error','支行名称填写错误,请重新填写');
            }
        }else{
            return_msg(500,'error','请输入支行名称');
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
        //stl_typ 结算类型
        //tranTyps 交易类型 suptDbfreeFlg免密免签 cardTyp卡种（银行
        //卡必选） stl_sign结算标志 orgNo机构号 stl_oac结算账户 bnk_acnm户名 icrp_id_no结算人身份
        //证号 crp_exp_dt_tmp结算人身份证有限期  wc_lbnk_no开户行  mailbox联系人邮箱 alipay_flg扫码产品
        //  yhkpay_flg银行卡产品 fee_rat_scan扫码费率(%) fee_rat1_scan银联二维码费率 fee_rat2 _scan银联标准费率
        //fee_rat借记卡费率(%)  max_fee_amt借记卡封顶(元） fee_rat1贷记卡费率（%）
        $datatel = $request->post();
        $data = $datatel;


        //查询商户的log_no流水号、mercId识别号    stl_sign结算标志 1对私 2对公
        $log_no = Db::name('merchant_incom')->where('merchant_id', $data['merchant_id'])
            ->field('mcc_cd,stl_sign,wc_lbnk_no,stl_oac,icrp_id_no,crp_exp_dt_tmp,bnk_acnm ,mercId,status,suptDbfreeFlg,cardTyp,alipay_flg, yhkpay_flg,
           fee_rat_scan,fee_rat1_scan,fee_rat,max_fee_amt,fee_rat1')
            ->find();
//        dump($log_no);die;

        //status判断商户状态是否是注册未完成、修改未完成
//        if (in_array($log_no[ 0 ][ 'status' ], [1, 2])) {


        //存入门店数据
        $result = $log_no;
        $result = array_merge($result, $datatel);
        $result = array_diff_key($result, ['mercId' => 1, 'log_no' => 2, 'status' => 3]);

        $create_id = Db::name('merchant_shop')->insertGetId($result);
        if (!$create_id) {
            return_msg(400, '数据不正确');
        }

        $data['orgNo'] = ORG_NO;
        //fee_rat2_scan

        $data['fee_rat2_scan'] = "0.6";
        $data['serviceId'] = 6060602;
        $data['version'] = 'V1.0.1';
        $data['log_no'] = "201810110001103896";
        $data['tranTyps'] = "C1";
        $data = array_merge($data, $log_no);
//        unset($data['']);
        unset($data['merchant_id']);
        unset($data['status']);
//        return json_encode($data);
        //签名域
        $sign_value = sign_ature(0000, $data);
//            dump($sign_value);die;
        $data['signValue'] = $sign_value;

        //向新大陆接口发送请求信息
//        var_dump($data);die;

        $shop_api = curl_request($this->url, true, $data, true);
//            return $shop_api;
        $shop_api = json_decode($shop_api, true);
        //获取签名域
//        var_dump($shop_api);die;
        $return_sign = sign_ature(1111, $shop_api);
        if ($shop_api['msg_cd'] === 000000) {
            if ($shop_api['signValue'] == $return_sign) {
                $datle = ['id' => $create_id, 'stoe_id' => $shop_api['stoe_id'], 'log_no' => $shop_api['log_no']];
                Db::name('merchant_incom')->where('merchant_id', $data['merchant_id'])->update(['status' => 0]);
                //返回成功
                Db::name('merchant_shop')->update($datle);
                return_msg(200, 'success', $shop_api['msg_dat']);

            } else {
                return_msg(400, 'error', $shop_api['msg_dat']);
            }
        } else {
            return_msg(500, 'error', $shop_api['msg_dat']);
        }
//        }else{
//            return_msg(100,'error','请先申请商户修改');
//        }
    }

    public function upload_pictures(Request $request)
    {
        $info = $request->post();
        $merchant_id = Session::get("username_")["id"];

        $data = MerchantIncom::alias('a')
            ->field('a.mercId,a.orgNo,b.log_no,b.stoe_id')
            ->join('merchant_shop b', 'b.merchant_id=a.merchant_id')
            ->where('a.merchant_id', $info['merchant_id'])
            ->find();

        $data['serviceId'] = '6060606';
        $data['version'] = 'V1.0.1';
        $data['imgTyp'] = $info['imgTyp'];
        $data['imgNm'] = $info['imgNm'];
        $data['merchant_id'] = $info['merchant_id'];
        $data = $data->toArray();
        $file = $request->file('imgFile');
        $data['imgFile'] = bin2hex(file_get_contents($file->getRealPath()));
        $img = $this->upload_pics($file);
        $this->send($data, $img);


    }

    /**
     * 图片上传消息发送接口
     *
     * @param  int $id
     * @return \think\Response
     */
    public function send($data, $img)
    {
        //获取签名
        $data['signValue'] = sign_ature(0000, $data);
        //发送给新大陆
        $result = json_decode(curl_request($this->url, true, $data, true), true);
        if ($result['msg_cd'] !== '000000') {
            return_msg(400, $result["msg_dat"]);
        }
        //生成签名
        $signValue = sign_ature(1111, $result);
        return_msg(200, 'success', $result);
//        return json_encode($result);
        if ($result['msg_cd'] == '000000' && $result['signValue'] == $signValue) {
            //取出数据库中的图片
            $file_img = IncomImg::field('img')->where('merchant_id', $data['merchant_id'])->find();
            if ($file_img == null) {
                //没有图片
                $data['img'] = json_encode($img);
                IncomImg::create($data, true);
            } elseif (is_array(json_decode($file_img['img']))) {
                //有多张图片

                $arr = json_decode($file_img['img']);
                $arr[] = $img;
                IncomImg::where('merchant_id', $data['merchant_id'])->update(['img' => json_encode($arr)]);
            } else {
                //只有一张图片

                $arr[] = json_decode($file_img['img']);
                $arr[] = $img;

                IncomImg::where('merchant_id', $data['merchant_id'])->update(['img' => json_encode($arr)]);
            }
//            $file_img=$file_img['img'].$img;
            $res = [
                'merchant_id' => $data['merchant_id'],
                'img' => $img
            ];
            return_msg(200, 'success', $res);
        } else {
            return_msg(400, 'failure');
        }
    }

    public function upload_pics()
    {
        //移动图片
        $file = request()->file('imgFile');
        $info = $file->validate(['size' => 5 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            //文件上传成功,生成缩略图
            //获取文件路径
            $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
            $goods_logo = str_replace('\\', '/', $goods_logo);
            return $goods_logo;
        } else {
            $error = $file->getError();
            $this->error($error);
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
            $param['merchant_id'] = Session::get("merchant_id", "merchant");
            /** 身份为员工 */
            $param['user_id'] = Session::get("user_id", "merchant");
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
                ->field("shop_name,stoe_adds")
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
                return_msg(200, "success", $res->toArray());
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
}
