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
        /*$data['merchant_id']=1;
        $data['status']=0;
        $info=MerchantStore::insert($data);*/
        $data['signValue']=sign_ature(0000,$data,KEY);
//        var_dump(json_encode($data));die;
        $result=curl_request(true,$data,true);
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

    public function merchant_incoms(Request $request)
    {
        $data=$request->post();
        $data['signValue']=sign_ature(0000,$data,KEY);
//        var_dump(json_encode($data));die;
        $result=curl_request(true,$data,true);
        $result=json_decode($result);
        $signValue=sign_ature(1111,$result,KEY);
        if($result['msg_cd']==000000 || $result['signValue']==$signValue){

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
        $data['signValue']=sign_ature(0000,$data,KEY);
        $data['signValue']=get_sign($data);
        $result=curl_request(true,$data,true);
        $result=json_decode($result,true);
        if($result['msg_cd']==0000){

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
        //取出当前商户信息
       /* $data=MerchantStore::where('merchant_id',$merchant_id)->field('serviceId,version,mercId,orgNo,log_no,stoe_id,')->find();*/
        $info=$request->post();
        $img=upload_logo();
        $info['imgFile']=$img;
//        $data=MerchantStore::where('merchant_id',$merchant_id)->update($info);
        //获取签名
        $info['signValue']=get_sign($info);
        //发送给新大陆
        $result=json_decode(curl_request(true,$info,true),true);
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

    /**
     * 商户申请修改
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
        $par= curl_request('http://sandbox.starpos.com.cn/emercapp',true,json_encode($arr),true);

        $bbntu=json_decode($par);
        if($bbntu['msg_cd']===000000 && $data[0]['mercId']==$bbntu['mercId']){
            $statu=Db::table('merchant_store')->where('merchant_id',$id)->update(['store_sn'=>$bbntu['stoe_id'],'log_no'=>$bbntu['log_no'],'status'=>1]);
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
        $par= curl_request('http://sandbox.starpos.com.cn/emercapp',true,$del,true);

        $par=json_decode($par);
        //返回数据的签名域
        $signreturn=sign_ature(1111,$par['data']);
        //&& $par['signValue']==$signreturn
        if($par['msg_cd']==000000){
//            $rebul=Db::table('think_user')->where('merchant_id',$del['merchant_id'])->update($del);
           return_msg(200,修改成功);
        }else{
            return_msg(400,'修改失败');
        }




    }

    /**
     * 商户状态查询
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
        $par= curl_request('http://sandbox.starpos.com.cn/emercapp',true,$data,true);
        $par=json_decode($par);
        $return_sign=sign_ature(1111,$par);
        if ($par['msg_cd']==000000 && $par['signValue']==$return_sign){
            return_msg('200','审核成功');
        }else{
            return_msg('400','非法报文');
        }

    }
}
