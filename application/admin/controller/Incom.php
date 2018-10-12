<?php

namespace app\admin\controller;

use app\admin\model\AreaCode;
use app\admin\model\Mcc;
use app\admin\model\MerchantIncom;
use app\admin\model\TotalMerchant;
use app\admin\model\IncomImg;
use app\agent\model\AgentCategory;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class Incom extends Controller
{

   public $url = 'https://gateway.starpos.com.cn/emercapp';



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
        $query = [
            'serviceId' => "6060203",
            'version' => "V1.0.1",
            'orgNo' => ORG_NO,
        ];
        $query['signValue'] = sign_ature(0000, $query);

        $res = curl_request($this->url, true, $query, true);

        /** json转成数组 */
//
        $res = json_decode($res, true);
//        halt($res);
        if($res['msg_cd']==000000){
            foreach($res['REC'] as $v){
                $data=Mcc::where('mcc_cd',$v['mcc_cd'])->find();
                if(empty($data)){
                    //可能修改也可能新增
                    $info=Mcc::where('mcc_nm',$v['mcc_nm'])->find();
                    if(empty($info)){
                        //新增
                        Mcc::insert($v);
                    }else{
                        //修改
                        Mcc::where('mcc_nm',$v['mcc_nm'])->update(['mcc_cd'=>$v['mcc_cd']]);
                    }
                }
            }
            return_msg(200,'success');
        }else{
            return_msg(400,$res['msg_dat']);
        }

//        halt($res['REC']);
        /*$check = $this->check_sign_value($query['signValue'], $res);
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

            $this->insert_to_incom_table("cloud_merchant_incom", $where, $data);
        }*/

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
        /*$id = $request->param("merchant_id");
        if (empty($id)) {
            return false;
        }
        $arr = Db::name("area_code")->where("merchant_id = $id")->field("orgNo,prov_nm,city_nm")->find();*/
        $query = [
            'serviceId' => "6060206",
            'version' => "V1.0.1",
            'orgNo'=>ORG_NO
            /*'orgNo' => $arr->getData("orgNo"),
            'prov_nm' => $arr->getData("prov_nm"),
            'city_nm' => $arr->getData("city_nm"),*/
        ];
        $query['signValue'] = sign_ature(0000, $query);

        $res = curl_request($this->url, true, $query, true);
        /** json转成数组 */
        $res = json_decode($res, true);
        /*for($i=0;$i<count($res['REC']);$i++){
            AreaCode::insert($res['REC'][$i]);

        }*/
        if($res['msg_cd']==000000){
            foreach($res['REC'] as $v){
                $data=AreaCode::where('merc_area',$v['merc_area'])->find();
                if(empty($data)){
                    //可能修改也可能新增
                    $info=AreaCode::where('area_nm',$v['area_nm'])->find();
                    if(empty($info)){
                        //新增
                        AreaCode::insert($v);
                    }else{
                        //修改
                        AreaCode::where('area_nm',$v['area_nm'])->update(['merc_area'=>$v['merc_area']]);
                    }
                }
            }
            return_msg(200,'success');
        }else{
            return_msg(400,$res['msg_dat']);
        }
        /*$check = $this->check_sign_value($query['signValue'], $res);

        if ($check === true) {
            $where = [
                "merchant_id" => $id,
            ];

            $data = [
                'merc_area' => $res['merc_area'],
                'area_nm' => $res['area_nm'],
                'merc_prov' => $res['merc_prov'],
                'prov_nm' => $res['prov_nm'],
                'merc_city' => $res['merc_city'],
                'city_nm' => $res['city_nm'],
            ];

            $this->insert_to_incom_table("cloud_area_code",$where, $data);
        }*/

    }

    /**
     *bank_query 支行名称模糊查询
     * @param Request $request
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bank_query($open_branch=null) {
        if(empty($open_branch)){
            $branch=request()->param('open_branch');
            $str_len=strlen($branch)/3;
            if($str_len<10){
                return_msg(400,'长度不能低于10位');
            }
            $query = [
                'serviceId' => "6060208",
                'version' => "V1.0.1",
                'lbnk_nm' => $branch,  //最少输入不低于5个字
                'orgNo' => ORG_NO,
            ];
            $query['signValue'] = sign_ature(0000, $query);
//        halt($query);
            $res = curl_request($this->url, true, $query, true);
            /** json转成数组 */
            $res = json_decode($res, true);
            if($res['msg_cd']==000000){
                $check = $this->check_sign_value($res['signValue'], $res);
                if($check==true){
                    return_msg(200,'success',$res['REC']);
                }
            }else{
                return_msg(400,$res['msg_dat']);
            }
        }else{
            //用户自己输入支行名称
            $query = [
                'serviceId' => "6060208",
                'version' => "V1.0.1",
                'lbnk_nm' => $open_branch,  //最少输入不低于5个字
                'orgNo' => ORG_NO,
            ];
            $query['signValue'] = sign_ature(0000, $query);
//        halt($query);
            $res = curl_request($this->url, true, $query, true);
            /** json转成数组 */
            $res = json_decode($res, true);
            if($res['msg_cd']==000000){
                $check = $this->check_sign_value($res['signValue'], $res);
                if($check==true){
                    return $res['REC'][0]['wc_lbnk_no'];
                }
            }else{
                return_msg(400,$res['msg_dat']);
            }
        }

        /*$arr = $request->param();
        $id = $request->param("merchant_id");
        if (empty($id)) {
            return false;
        }
        $result = MerchantIncom::where("merchant_id=$id")->field("orgNo")->find();*/


    }
    /**
     * 商户进件
     *
     * @return \think\Response
     *
     */
    public function merchant_incom($merchant_id)
    {

        $data = MerchantIncom::field('fee_rat1_scan,fee_rat3_scan,fee_rat_scan, incom_type,stl_typ,stl_sign,stl_oac,bnk_acnm,wc_lbnk_no,bus_lic_no,bse_lice_nm,crp_nm,mercAdds,bus_exp_dt,crp_id_no,crp_exp_dt,stoe_nm,stoe_cnt_nm,stoe_cnt_tel,mcc_cd,stoe_area_cod,stoe_adds,trm_rec,mailbox,yhkpay_flg,alipay_flg,orgNo,cardTyp,suptDbfreeFlg,tranTyps,crp_exp_dt_tmp,icrp_id_no,fee_rat,max_fee_amt,fee_rat1')->where('merchant_id', $merchant_id)->find();
        $data['serviceId'] = 6060601;
        $data['version'] = 'V1.0.3';
        //获取商户id
//        $data['merchant_id']=11;
//        $info=MerchantIncom::insert($data);
        $data=$data->toArray();
        $data['signValue'] = sign_ature(0000, $data);
//        var_dump(json_encode($data));die;
//        dump($data);die;
//        var_dump(json_encode($data));die;
//        if($info){
        //发送给新大陆
        $result = curl_request($this->url, true, $data, true);
        $result = json_decode($result, true);
        $signValue = sign_ature(1111, $result);
        if ($result['msg_cd'] == '000000' && $result['signValue'] == $signValue) {
            //审核通过
            //跟新数据库
            $arr['log_no'] = $result['log_no'];
            $arr['mercId'] = $result['mercId'];
            $arr['stoe_id'] = $result['stoe_id'];
            $res = MerchantIncom::where('merchant_id', $merchant_id)->update($arr, true);
            if ($res) {
                //返回商户自增id
                $a = [
                    'merchant_id' => $merchant_id
                ];
                return_msg(200, 'success', $a);
//                    return $result['msg_dat'];
            } else {
                return_msg(400, 'failure');
            }
        }else {
            //审核未通过
            MerchantIncom::where('merchant_id',$merchant_id)->delete();
            TotalMerchant::where('id',$merchant_id)->delete();
            return_msg(400, 'failure', $result['msg_dat']);
        }
//        }else{
//            return_msg(400,'failure');
//        }
//    }
    }


    /**
     * 商户提交
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function merchant_create(Request $request)
    {
        $merchant_id=$request->post('merchant_id');
        //取出数据表中数据
        $data=MerchantIncom::where('merchant_id',$merchant_id)->field('mercId,log_no,orgNo')->find();
        $data = $data->toArray();
        $data['serviceId']='6060603';
        $data['version']='V1.0.1';

        $data['signValue'] = sign_ature(0000,$data);

        $result=curl_request($this->url,true,$data,true);
        $result = json_decode($result,true);
        if (!empty($result)) {
            return_msg(400, $result);
        }
        //生成签名
        $signValue = sign_ature(1111,$result);
        if( $signValue == $result['signValue'] ){
            if(isset($result['check_flag'])){
                //修改数据表状态
                $res = MerchantIncom::where('merchant_id',$merchant_id)->update(['check_flag'=>$result['check_flag'],
                    'key' => $result["key"],
                    'rec' => $result['REC']
                    ]);

                if($res){
                    return_msg(200,'success');
                }else{
                    return_msg(400,'failure');
                }
            }else{
                return_msg(400,'failure',$result['msg_dat']);
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

        $info=$request->post();
        //取出当前商户信息
        $data=MerchantIncom::where('merchant_id',$info['merchant_id'])->field('mercId,orgNo,log_no,stoe_id')->find();
        $data['serviceId']='6060606';
        $data['version']='V1.0.1';
        $data['imgTyp']=$info['imgTyp'];
        $data['imgNm']=$info['imgNm'];
        $data['merchant_id']=$info['merchant_id'];
        $data = $data->toArray();
        $file = $request->file('imgFile');
        /** 转为二进制 */

        $data['imgFile'] = bin2hex(file_get_contents($file->getRealPath()));

        $img = $this->upload_pics($file);

        $this->send($data,$img);
    }

    /**
     * 图片上传消息发送接口
     *
     * @param  int $id
     * @return \think\Response
     */
    public function send($data,$img)
    {
        //获取签名
        $data['signValue']=sign_ature(0000,$data);
        //发送给新大陆
        $result=json_decode(curl_request($this->url,true,$data,true),true);
        if ($result['msg_cd'] !== '000000') {
            return_msg(400, $result["msg_dat"]);
        }
        //生成签名
        $signValue=sign_ature(1111,$result);
        if($result['msg_cd']=='000000' && $result['signValue']==$signValue){
            //取出数据库中的图片
            $file_img = IncomImg::field('img')->where('merchant_id',$data['merchant_id'])->find();
            if($file_img == null){
                //没有图片
                $data['img']=json_encode($img);
                IncomImg::create($data,true);
            }elseif(is_array(json_decode($file_img['img']))){
                //有多张图片

                $arr=json_decode($file_img['img']);
                $arr[]=$img;
                IncomImg::where('merchant_id',$data['merchant_id'])->update(['img'=>json_encode($arr)]);
            }else{
                //只有一张图片

                $arr[]=json_decode($file_img['img']);
                $arr[]=$img;

                IncomImg::where('merchant_id',$data['merchant_id'])->update(['img'=>json_encode($arr)]);
            }
//            $file_img=$file_img['img'].$img;
            $res=[
                'merchant_id'=>$data['merchant_id'],
                'img'=>$img
            ];
            return_msg(200,'success',$res);
        }else{
            return_msg(400,'failure');
        }
    }

    public function upload_pics(){
        //移动图片
        $file = request()->file('imgFile');
        $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH.'public'.DS.'uploads');
        if($info){
            //文件上传成功,生成缩略图
            //获取文件路径
            $goods_logo = DS.'uploads'.DS.$info->getSaveName();
            $goods_logo = str_replace('\\','/',$goods_logo);
            return $goods_logo;
        }else{
            $error=$file->getError();
            $this->error($error);
        }
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


    /*
     * 商户修改申请
     * 商户审核通过后，修改商户为修改未完成状态
     * @param Request $request
     */
    public function mermachant_edit(Request $request)
    {

        $id=$request->param('id');
        $serviceId=6060605;
        $version='V1.0.1';
        $data=Db::name('merchant_incom')->where('merchant_id',$id)->field('mercId,orgNo,check_flag')->select();

        //商户是否通过审核
//        if($data[0]['check_flag']==1) {
            $resul = ['serviceId' => $serviceId, 'version' => $version, 'mercId' => $data[ 0 ][ 'mercId' ], 'orgNo' => $data[ 0 ][ 'orgNo' ]];
            //获取签名域
            $resul_age = sign_ature(0000, $resul);
            $resul[ 'signValue' ] = $resul_age;
            //向新大陆接口发送信息验证
            $par = curl_request($this->url, true, $resul, true);

            $bbntu = json_decode($par, true);
            $return_sign = sign_ature(1111, $resul);

              dump($bbntu);die;

            if ($bbntu[ 'msg_cd' ] === 000000) {
                if ($return_sign == $bbntu[ 'signValue' ]) {
                    //商户状态修改为修改未完成
                   Db::table('merchant_incom')->where('merchant_id', $id)->update([ 'log_no' => $bbntu[ 'log_no' ], 'status' => 2]);

                    return_msg(200, 'success',$bbntu['msg_dat']);
                } else {
                    return_msg(400, 'error',$bbntu['msg_dat']);
                }
            } else {
                return_msg(500, 'error',$bbntu['msg_dat']);
            }
//        }else{
//            return_msg(100,'error','商户审核未通过');
//        }

    }

    /**
     * 商户资料修改
     * 商户资料修改是的状态（注册未完成，修改未完成，注册拒绝，修改拒绝）
     * @param Request $request
     */
    public function commercial_edit(Request $request)
    {
        $del = $request->post();

        $del[ 'serviceId' ] = 6060604;
        $del[ 'version' ] = 'V1.0.1';
        $data = Db::name('merchant_incom')->where('merchant_id', $del[ 'merchant_id' ])->field('log_no,mercId,stoe_id,mcc_cd,status')->select();
        //查看商户是否是完成状态
        if ($data[0]['status']!=1) {
            $aa = [];
            foreach ($data[0] as $k => $v) {
                $aa[ $k ] = $v;
            }
            $dells=$del+$aa;
            $sign_ature = sign_ature(0000, $del);
            $dells[ 'signValue' ] = $sign_ature;
            //向新大陆接口发送信息验证

            $par = curl_request($this->url, true, $del, true);

            $par = json_decode($par, true);
            //返回数据的签名域
//        dump($par);die;

            $return_sign = sign_ature(1111, $par);

            if ($par[ 'msg_cd' ] === 000000) {
                if ($par[ 'signValue' ] == $return_sign) {
                    $del['status']=0;
                   Db::table('merchant_incom')->where('merchant_id',$del['merchant_id'])->update($del);

                    return_msg(200, 'error',$par['msg_dat']);
                } else {
                    return_msg(400, 'error',$par['msg_dat']);
                }
            } else {
                return_msg(500, 'error',$par['msg_dat']);
            }
        }else{
            return_msg(100,'error','商户为审核完成状态，请先申请修改');
        }


    }

    /**
     * 商户查询
     * 查询商户的审核结果
     * @param Request $request
     */

    public function mercachant_inquire(Request $request)
    {
        $id=$request->param('id');
        $arr=Db::name('merchant_incom')->where('merchant_id',$id)->field('mercId,orgNo')->select();
        $data=['serviceId'=>6060300,'version'=>'V1.0.1','mercId'=>$arr[0]['mercId'],'orgNo'=>$arr[0]['orgNo']];
        //签名域
        $signValue=sign_ature(0000,$data);

        $data['signValue']=$signValue;
        //向新大陆接口发送请求信息
        $par= curl_request($this->url,true,$data,true);
        $par=json_decode($par,true);

        //获取签名域
        $return_sign = sign_ature(1111,$par);
        if ($par['msg_cd']==000000){
            if($par['signValue'] == $return_sign){
                Db::name('merchant_incom')->where('merchant_id',$id)->update(['status'=>0]);
                return_msg(200,'success',$par);
            }else{
                return_msg(400,'error',$par['msg_dat']);
            }

        }else{
            return_msg(400,'error',$par['msg_dat']);
        }

    }

    /**
     * 新增门店
     * 商户新增门店时商户的状态（注册未完成、修改未完成）
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
        $log_no = Db::name('merchant_incom')->where('merchant_id', $data[ 'merchant_id' ])->field('mercId,log_no,status')->select();
        //判断商户状态是否是注册未完成、修改未完成
        if (in_array($log_no[ 0 ][ 'status' ], [1, 2])) {

            $data[ 'mercId' ] = $log_no[ 0 ][ 'mercId' ];
            $data[ 'log_no' ] = $log_no[ 0 ][ 'log_no' ];
            //存入门店数据

            $create_id = Db::name('merchant_shop')->insertGetId($data);
//        dump($create_id);die;
            if (!$create_id) {
                return_msg(400, '数据不正确');
            }

            $sign_value = sign_ature(0000, $data);
            $data[ 'signValue' ] = $sign_value;
            $data[ 'serviceId' ] = 6060602;
            $data[ 'version' ] = 'V1.0.1';
            $data[ 'mercId' ] = $log_no[ 0 ][ 'mercId' ];
            $data[ 'log_no' ] = $log_no[ 0 ][ 'log_no' ];
            //向新大陆接口发送请求信息
            $shop_api = curl_request($this->url, true, $data, true);
            $shop_api = json_decode($shop_api, true);
            //获取签名域
            $return_sign = sign_ature(1111, $shop_api);
            if ($shop_api[ 'msg_cd' ] === 000000) {
                if ($shop_api[ 'signValue' ] == $return_sign) {
                    $datle = ['id' => $create_id, 'stoe_id' => $shop_api[ 'stoe_id']];
                    Db::name('merchant_incom')->where('merchant_id',$data['merchant_id'])->update(['status'=>0]);
                    //返回成功
                    Db::name('merchant_shop')->update($datle);
                    return_msg(200, 'success',$shop_api['msg_dat']);

                } else {
                    return_msg(400, 'error',$shop_api['msg_dat']);
                }
            } else {
                return_msg(500, 'error',$shop_api['msg_dat']);
            }
        }else{
            return_msg(100,'error','请先申请商户修改');
        }
    }

}


