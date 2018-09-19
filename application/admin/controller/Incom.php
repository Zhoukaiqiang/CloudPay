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
        /*$data=$request->param();
        //生成签名
        $signValue=get_sign($data);
        $where=[
            'serviceId'=>$data['serviceId'],
            'version'=>$data['version'],
            'mercId'=>$data['mercId'],
            'orgNo'=>$data['orgNo']
        ];
        $info=MerchantStore::where($where)->find();
        //发给第三方*/
        $merchant_id=1;
        $data=MerchantStore::where('merchant_id',1)->field('serviceId,version,mercId,orgNo')->find();
    }

    /**
     * 商户进件
     *
     * @return \think\Response
     */
    public function merchant_incom(Request $request)
    {
        $data=$request->post();
        $data['status']=0;
        $info=MerchantStore::insert($data);
        $data['signValue']=get_sign($data);
        if($info){
            //发送给新大陆
            $url="https://gateway.starpos.com.cn/emercapp";
            $result=json_decode(curl_request($url,true,$data),true);
            if($result['msg_cd']=='000000'){
                //审核通过
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
        $where=[
            'merchant_id'=>$merchant_id,
        ];
        $data=MerchantStore::where($where)->column('value_key,value');
        $str1=$data['serviceId'];
        $str2=$data['version'];
        $str3=$data['mercId'];
        $str4=$data['log_no'];
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
        $info=$request->post();
        $img=upload_logo();
        $img=json_encode($img);
        $info['imgFile']=$img;
        $data=MerchantStore::insert($info);
        //获取签名
        $signValue=get_sign($data);
        if($data){
            //发送给新大陆
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
