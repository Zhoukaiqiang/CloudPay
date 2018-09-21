<?php

namespace app\admin\controller;

use app\admin\model\MerchantStore;
use app\admin\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Incom extends Controller
{
    public $url='http://sandbox.starpos.com.cn/emercapp';

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
        $info=MerchantStore::insert($data);
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
                $res=MerchantStore::where('merchant_id',$data['merchant_id'])->update($arr,true);
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
        $data=MerchantStore::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,log_no,orgNo')->find();
        $data['signValue']=sign_ature(0000,$data);
        $result=curl_request($this->url,true,$data,true);
        $result=json_decode($result,true);
        if($result['msg_cd']==000000){
            //修改数据表状态
            $res=MerchantStore::where('merchant_id',$merchant_id)->update(['check_flag'=>$result['check_flag']]);
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
        $data=MerchantStore::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id')->find();
        $data['imgTyp']=$info['imgTyp'];
        $data['imgNm']=$info['imgNm'];
        $img=$this->upload_logo();
        $img=json_encode($img);
        $data['imgFile']=$img;
//        $data=MerchantStore::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $data['signValue']=sign_ature(0000,$data);
        //发送给新大陆
        $result=json_decode(curl_request($this->url,true,$data,true),true);
        if($result['msg_cd']=='000000'){
            $res=MerchantStore::where('merchant_id',$merchant_id)->update($data);
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

    //上传图片
    private function upload_logo(){
        $files=request()->file('imgFile');
        $goods_pics=[];
        foreach($files as $file){
            $info=$file->validate(['size'=>500*1024,'ext'=>'jpg,jpeg,gif,png'])->move(ROOT_PATH.'public'.DS.'uploads');
            if($info){
                //图片上传成功
                $goods_logo=DS.'uploads'.DS.$info->getSaveName();
                $goods_logo=str_replace('\\','/',$goods_logo);
                $goods_pics[]=$goods_logo;
            }else{
                $error=$info->getError();
                return_msg(400,$error);
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
        $id=$request->param('id');
        $serviceId=6060605;
        $version='v1.0.1';
        $data=Db::name('merchant_store')->where('merchant_id',$id)->field('mercId,orgNo')->select();

        $resul=['serviceId'=>$serviceId,'version'=>$version,'mercId'=>$data[0]['mercId'],'orgNo'=>$data[0]['orgNo']];
        $resul_age=sign_ature(0000,$resul);
        $arr['signValue']=$resul_age;
        $par= curl_request($this->url,true,json_encode($arr),true);

        $bbntu=json_decode($par);
        $return_sign=sign_ature(1111,$resul);
        if($bbntu['msg_cd']===000000 && $return_sign==$bbntu['signValue']){
            $statu=Db::table('merchant_store')->where('merchant_id',$id)->update(['store_sn'=>$bbntu['stoe_id'],'log_no'=>$bbntu['log_no'],'alter'=>1]);
            return_msg(200,'商户修改申请成功');
        }else{
            return_msg(400,'商户修改申请失败');
        }

    }

    /**
     * 商户资料修改
     * @param Request $request
     */
    public function commercial_edit(Request $request)
    {
        $del=$request->post();

        $aa['serviceId']=6060604;
        $aa['version']='V1.0.1';
        $data=Db::name('merchant_store')->where('merchant_id',$del['merchant_id'])->field('log_no,mercId,store_sn')->select();
        $aa=[];
        foreach ($data as $k=>$v)
        {
            $aa=$v;
        }
        $dells=$del+$aa;
//        //获取加密的签名域
        $sign_ature=sign_ature(0000,$dells);
        $del['signValue']=$sign_ature;
        //向新大陆接口发送信息验证
        $par= curl_request($this->url,true,$del,true);

        $par=json_decode($par);
        //返回数据的签名域


        $return_sign=sign_ature(1111,$par);
        if ($par['msg_cd']==000000 && $par['signValue']==$return_sign){
//            $rebul=Db::table('think_user')->where('merchant_id',$del['merchant_id'])->update($del);
           return_msg(200,'修改成功');
        }else{
            return_msg(400,'修改失败');
        }




    }

    /**
     * 商户查询审核结果
     * @param Request $request
     */

    public function mercachant_inquire(Request $request)
    {
        $id=$request->param('id');
        $arr=Db::name('merchant_store')->where('merchant_id',$id)->field('mercId，orgNo')->select();
        $data=['serviceId'=>6060300,'version'=>'V1.0.1','mercId'=>$arr[0]['mercId'],'orgNo'=>$arr[0]['orgNo']];
        //签名域
        $signValue=sign_ature(0000,$data);
        $data['signValue']=$signValue;
        $par= curl_request($this->url,true,$data,true);
        $par=json_decode($par);
        $return_sign=sign_ature(1111,$par);
        if ($par['msg_cd']==000000 && $par['signValue']==$return_sign){

            return_msg('200','审核成功');
        }else{
            return_msg('400','非法报文');
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
        $data=$request->post();
        //查询商户的流水号、识别号
        $log_no=Db::name('merchant_store')->where('merchant_id',$data['merchant_id'])->field('mercId,log_no')->select();
        $data['mercId']=$log_no[0]['mercId'];
        $data['log_no']=$log_no[0]['log_no'];
        //存入门店数据
        $create_id=Db::name('merchant_shop')->insertGetId($data);
        if(!$create_id){
            return_msg(400,'数据不正确');
        }
        $sign_value=sign_ature(0000,$data);
        $data['signValue']=$sign_value;
        $data['serviceId']=6060602;
        $data['version']='V1.0.1';
        $data['mercId']=$log_no[0]['mercId'];
        $data['log_no']=$log_no[0]['log_no'];
        $shop_api=curl_request($this->url,true,$data,true);
//
        $shop_api=json_decode($shop_api);
        $array=[];
        foreach ($shop_api as $k=>$v){
            $array[$k]=$v;
        }
//        dump($array);
        $return_sign=sign_ature(1111,$array);
        if($array['msg_cd']==000000 && $array['signValue']==$return_sign)
        {
            $datle=['id'=>$create_id,'stoe_id'=>$array['stoe_id']];
            //返回成功
            Db::name('merchant_shop')->update($datle);
            return_msg(200,'操作成功');
        }else{

    }

    }
}
