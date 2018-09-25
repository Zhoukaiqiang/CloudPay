<?php

namespace app\admin\controller;

use app\admin\model\MerchantIncom;
use app\admin\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Incom extends Controller
{
    public $url='https://gateway.starpos.com.cn/emercapp';


    /**
     * 商户查询
     *
     * @return \think\Response
     */
//    public function merchant_query(Request $request)
//    {
//        //apam:value1, Cpam:value2, bpam:Value3
//        /*$data=[
//            'apam'=>'value1',
//            'cpam'=>'value2',
//            'bpam'=>'Value3'
//        ];
//        $info=get_sign($data);
//        dump($info);die;*/
//       /* $data=$request->param();
//        //生成签名
//        $signValue=get_sign($data);
//        $where=[
//            'serviceId'=>$data['serviceId'],
//            'version'=>$data['version'],
//            'mercId'=>$data['mercId'],
//            'orgNo'=>$data['orgNo']
//        ];*/
//        $info=MerchantIncom::where($where)->find();
//        //发给第三方
//        $merchant_id=1;
//        $data=MerchantIncom::where('merchant_id',1)->field('serviceId,version,mercId,orgNo')->find();
//    }


    /**
     * 商户进件
     *
     * @return \think\Response
     *
     */
    public function merchant_incom(Request $request)
    {
        $data=$request->post();
        $data['serviceId']=6060601;
        $data['version']='V1.0.3';
        //获取商户id
        $data['merchant_id']=1;
        $info=MerchantIncom::insert($data);
        $data['signValue']=sign_ature(0000,$data);
//        var_dump(json_encode($data));die;
        if($info){
            //发送给新大陆
            $result=curl_request($this->url,true,$data,true);
            $result=json_decode($result,true);
            $signValue=sign_ature(1111,$result);
            if($result['msg_cd']=='000000' && $result['signValue']==$signValue){
                //审核通过
                //跟新数据库
                $arr['log_no']=$result['log_no'];
                $arr['mercId']=$result['mercId'];
                $arr['stoe_id']=$result['stoe_id'];
                $res=MerchantIncom::where('merchant_id',$data['merchant_id'])->update($arr,true);
                if($res){
                    return_msg(200,'success',$result['msg_dat']);
                }else{
                    return_msg(400,'failure');
                }
            }
        }else{
            return_msg(400,'failure');
        }
    }

    /**
     * 商户提交
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function merchant_create(Request $request)
    {
        $merchant_id=1;
        //取出数据表中数据
        $data=MerchantIncom::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,log_no,orgNo')->find();
        $data['signValue']=sign_ature(0000,$data);
        $result=curl_request($this->url,true,$data,true);
        $result=json_decode($result,true);
        if($result['msg_cd']==000000){
            //修改数据表状态
            $res=MerchantIncom::where('merchant_id',$merchant_id)->update(['check_flag'=>$result['check_flag']]);
            if($res){
                return_msg(200,'success',$result['msg_dat']);
            }else{
                return_msg(400,'failure');
            }
        }
    }

    /**
     * 图片上传
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function img_upload(Request $request)
    {
        $merchant_id=1;
        $info=$request->post();
        dump($info);die;
        //取出当前商户信息
        $data=MerchantIncom::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id')->find();
        $data['imgTyp']=$info['imgTyp'];
        $data['imgNm']=$info['imgNm'];
        $img=$this->upload_logo();
        $img=json_encode($img);
        $data['imgFile']=$img;
//        $data=MerchantIncom::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $data['signValue']=sign_ature(0000,$data);
        //发送给新大陆
        $result=json_decode(curl_request($this->url,true,$data,true),true);
        if($result['msg_cd']=='000000'){
            $res=MerchantIncom::where('merchant_id',$merchant_id)->update($data);
            if($res){
                return_msg(200,'success',$result['msg_dat']);
            }else{
                return_msg(400,'failure');
            }
        }else{
            return_msg(400,'failure');
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }

    /**
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
        if($data[0]['check_flag']==1) {
            $resul = ['serviceId' => $serviceId, 'version' => $version, 'mercId' => $data[ 0 ][ 'mercId' ], 'orgNo' => $data[ 0 ][ 'orgNo' ]];
            //获取签名域
            $resul_age = sign_ature(0000, $resul);
            $resul[ 'signValue' ] = $resul_age;
            //向新大陆接口发送信息验证
            $par = curl_request($this->url, true, $resul, true);

            $bbntu = json_decode($par, true);
            $return_sign = sign_ature(1111, $resul);

//              dump($bbntu);die;
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
        }else{
            return_msg(100,'error','商户审核未通过');
        }

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
        dump($par);die;
        //获取签名域
        $return_sign=sign_ature(1111,$par);
        if ($par['msg_cd']==000000){
            if($par['signValue']==$return_sign){
                Db::name('merchant_incom')->where('merchant_id',$id)->update(['status'=>0]);
                return_msg(200,'success',$par['msg_dat']);
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


