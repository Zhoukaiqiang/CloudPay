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
//        $data['merchant_id']=1;
//        $data['status']=0;
        //生成合作商机构号
//        $data['orgNo']=time().rand(10000,99999);
//        $info=MerchantStore::insert($data);
        $data['signValue']=sign_ature(0000,$data,KEY);
//        dump($data['signValue']);die;
//        var_dump(json_encode($data));die;
        $result=curl_request(true,$data,true);
        $result=iconv('GBK','UTF-8',$result);
        dump($result);die;
        if($info){
            //发送给新大陆
            unset($data['merchant_id']);
            unset($data['status']);
            $url="https://gateway.starpos.com.cn/emercapp";
            $result=curl_request($url,true,$data);
            dump($result);die;
            if($result['msg_cd']=='000000'){
                //审核通过
                //跟新数据库
                $merchant_id=1;
                $res=MerchantStore::where('merchant_id',$merchant_id)->update($result,true);
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
        $signValue=get_sign($data);
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
        //取出当前商户信息
       /* $data=MerchantStore::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id,')->find();*/
        $info=$request->post();
        $img=upload_logo();
        $info['imgFile']=$img;
//        $data=MerchantStore::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $info['signValue']=get_sign($info);
        //发送给新大陆
        $url="https://gateway.starpos.com.cn/emercapp";
        $result=json_decode(curl_request($url,true,$info),true);
        if($result['msg_cd']=='000000'){
            $res=MerchantStore::where('merchant_id',$merchant_id)->update($info);
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
            $info=$file->validate(['size'=>5*1024*1024,'ext'=>'jpg,jpeg,gif,png'])->move(ROOT_PATH.'public'.DS.'uploads');
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
