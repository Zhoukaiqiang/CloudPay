<?php

namespace app\admin\controller;

use app\admin\model\MerchantStore;
use app\admin\model\TotalMerchant;
use think\Controller;
use think\Request;

class Incom extends Controller
{
    /**
     * 商户查询
     *
     * @return \think\Response
     */
    public function merchant_query(Request $request)
    {
        //apam:value1, Cpam:value2, bpam:Value3
        /*$data=[
            'apam'=>'value1',
            'cpam'=>'value2',
            'bpam'=>'Value3'
        ];
        $info=get_sign($data);
        dump($info);die;*/
       /* $data=$request->param();
        //生成签名
        $signValue=get_sign($data);
        $where=[
            'serviceId'=>$data['serviceId'],
            'version'=>$data['version'],
            'mercId'=>$data['mercId'],
            'orgNo'=>$data['orgNo']
        ];*/
        $info=MerchantStore::where($where)->find();
        //发给第三方
        $merchant_id=1;
        $data=MerchantStore::where('merchant_id',1)->field('serviceId,version,mercId,orgNo')->find();
    }

    /**
     * 商户进件
     *
     * @return \think\Response
     *
     */
    public function merchant_incom(Request $request)
    {
        $data=$request->post();
//        dump($data);
        //获取商户id
        $data['merchant_id']=1;
        $info=MerchantStore::insert($data);
        $data['signValue']=sign_ature(0000,$data);
//        var_dump(json_encode($data));die;
        if($info){
            //发送给新大陆
            unset($data['merchant_id']);
            $result=curl_request(true,$data,true);
            $result=json_decode($result);
            $signValue=sign_ature(1111,$result);
            if($result['msg_cd']=='000000' && $result['signValue']==$signValue){
                //审核通过
                //跟新数据库
                $merchant_id=1;
                $arr['log_no']=$result['log_no'];
                $arr['mercId']=$result['mercId'];
                $arr['stoe_id']=$result['stoe_id'];
                $res=MerchantStore::where('merchant_id',$merchant_id)->update($arr,true);
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
     * 商户进件
     * @param Request $request
     */
    public function merchant_incoms(Request $request)
    {
        //获取商户id
        $data=$request->post();
        $data['signValue']=sign_ature(0000,$data);
//        unset($data['merchant_id']);
//        var_dump(json_encode($data));die;
        $result=curl_request(true,$data,true);
        $result=json_decode($result);
        dump($result);die;
        //判断签名
        $signValue=sign_ature(1111,$result);
        if($result['msg_cd']==000000 && $result['signValue']==$signValue){
            $data['merchant_id']=$request->post('merchant_id');
            $data['log_no']=$result['log_no'];
            $data['mercId']=$result['mercId'];
            $data['stoe_id']=$result['stoe_id'];
            $info=MerchantStore::insert($data);
            if($info){
                return_msg(200,'success',$result['msg_dat']);
            }else{
                return_msg(400,'failure');
            }
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
        $result=curl_request(true,$data,true);
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
        //取出当前商户信息
        $data=MerchantStore::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id')->find();
        $data['imgTyp']=$info['imgTyp'];
        $data['imgNm']=$info['imgNm'];
        $img=$this->upload_logo();
        $data['imgFile']=$img;
//        $data=MerchantStore::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $data['signValue']=sign_ature(0000,$data);
        //发送给新大陆
        $result=json_decode(curl_request(true,$data,true),true);
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
}
