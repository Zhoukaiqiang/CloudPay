<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Request;

class User extends Controller
{
    /**
     * 添加人员
     * @return \think\Response
     */

    public function add(Request $request)
    {
        if($request->isPost()){
            //传入门店id
            $data=$request->post();
            //验证
            //查询手机号是否存在
            $info=MerchantUser::where('people_phone',$data['people_phone'])->find();
            if($info){
                return_msg(400,'failure','手机号已经存在');
            }
            $data['password']=addslashes(encrypt_password($data['password'],$data['people_phone']));
            $result=MerchantUser::insert($data,true);
            if($result){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出所有门店信息
            $data=MerchantShop::field(['id','shop_name'])->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 编辑人员
     *
     * @return \think\Response
     */
    public function create()
    {
        //
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
}
