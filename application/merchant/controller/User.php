<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Request;

class User extends Controller
{

    /**
     * 员工首页
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function index()
    {
        //获取商户id
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        //取出所有门店
        $data['shop']=MerchantShop::field('id,shop_name')->where('merchant_id',$merchant_id)->select();
        //前端传入员工id
    }

    /**
     * 员工搜索
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function search(Request $request)
    {
        //获取门店id和角色
        $shop_id=$request->post('shop_id') ? $request->post('shop_id') : '';
        $role=$request->post('role') ? $request->post('role') : '';
        if(!empty($shop_id) && empty($role)){
            //显示当前门店下所有员工
            $data=MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where('a.shop_id',$shop_id)
                ->select();
            return_msg(200,'success',$data);
        }elseif(!empty($shop_id) && !empty($role)){
            //取出当前门店下所有角色信息
            $data=MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$shop_id,'a.role'=>$role])
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($shop_id) && !empty($role)){
            $merchant_id=session('merchant_id');
            $merchant_id=1;
            //当前商户下所有角色信息
            $data=MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.merchant_id'=>$merchant_id,'a.role'=>$role])
                ->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 添加人员
     * @return \think\Response
     */
    public function add(Request $request)
    {
        $merchant_id=session('merchant_id');
        if($request->isPost()){
            //传入门店id
            $data=$request->post();
            //验证
            //查询手机号是否存在
            $info=MerchantUser::where('people_phone',$data['people_phone'])->find();
            if($info){
                return_msg(400,'failure','手机号已经存在');
            }
//            $data['password']=addslashes(encrypt_password($data['password'],$data['people_phone']));
            $result=MerchantUser::insert($data,true);
            if($result){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            $merchant_id=1;
            //取出所有门店信息
            $data=MerchantShop::field(['id','shop_name'])->where('merchant_id',$merchant_id)->select();
            return_msg(200,'success',$data);
        }

    }
    /**
     * 编辑人员
     *
     * @return \think\Response
     */
    public function edit(Request $request)
    {
        if($request->isPost()){
            $data=$request->post();
//            $data['password']=addslashes(encrypt_password($data['password'],$data['phone']));
            $result=MerchantUser::update($data);
            if($result){
                return_msg(200,'success','修改成功');
            }else{
                return_msg(400,'failure','修改失败');
            }
        }else{
            $id=$request->param('id');
            $merchant_id=session('merchant_id');
            $merchant_id=1;//测试
            //获取员工信息
            $data['list']=MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.password,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            //获取所有门店信息
            $data['shop']=MerchantShop::field('id,shop_name')
                ->where('merchant_id',$merchant_id)
                ->select();
            return_msg(200,'success',$data);
        }


    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */


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
}
