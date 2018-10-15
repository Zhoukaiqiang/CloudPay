<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Exception;
use think\Loader;
use think\Request;
use think\Session;

class User extends Common
{

    /**
     * 员工首页
     *  role 角色 1服务员 2店长 3收银员
     * @param  \think\Request  $request
     * @return \think\Response
     * @throws Exception
     */
    public function index()
    {
        //获取商户id和店长id

        if(!empty($this->merchant_id)){
            //取出所有门店
            $data['shop'] = MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
            //取出当前商户下所有店长
            $data['user'] = MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.merchant_id'=>$this->merchant_id,'a.role'=>2])
                ->select();
            //前端传入员工id
            if (count($data["user"])) {
                return_msg(200,'success',$data);
            }else {
                return_msg(400,'no data');
            }

        }elseif(!empty($this->user_id)){
            //取出当前店长所属门店
            $info = MerchantUser::field('shop_id')->where('id',$this->user_id)->find();

            $data['shop_name'] = MerchantShop::field('shop_name')->where('id',$info['shop_id'])->find();

            //取出当前门店下所有服务员和收银员
            $data['user'] = MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$info['shop_id'],'a.role'=>['<>',2]])
                ->select();

            if (count($data["user"])) {
                return_msg(200,'success',$data);
            }else {
                return_msg(400,'no data');
            }
        }

    }

    /**
     * 员工搜索
     *
     * @param  \think\Request  $request
     * @return \think\Response
     * @throws Exception
     */
    public function search(Request $request)
    {
        //获取门店id和角色
        $shop_id = $request->param('shop_id') ? $request->param('shop_id') : null;
        $role = $request->param('role') ? $request->param('role') : null;

        if (empty($shop_id)) {
            $shop_id_flag = '<>';
            $shop_id = -2;
        }else {
            $shop_id_flag = "eq";
        }
        if (empty($role)) {
            $role_flag = '<>';
            $role = -2;
        }else {
            $role_flag = "eq";
        }

        $query = [
            "a.shop_id" => [$shop_id_flag, $shop_id],
            "a.role" => [$role_flag, $role],
        ];
        if($this->merchant_id){
            //根据条件显示员工
            $data=MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where($query)
                ->select();

            return_msg(200,'success',$data);
        }else {
            /** 店长身份登录 */
            $shop_id = MerchantUser::get($this->user_id)["shop_id"];
            $data = MerchantUser::alias('a')
                ->field('a.id,a.name,a.phone,a.role')
                ->where([
                    "shop_id" => $shop_id,
                    "role"    => ["<>", 2]
                ])
                ->select();

            return_msg(200,'success',$data);
        }

    }

    /**
     * 添加员工
     * @return \think\Response
     */
    public function add(Request $request)
    {

        if($request->isPost()){
            if(!empty($this->merchant_id)){
                /** 当前为商户登录 */

                $data=$request->post();
                $data['shop_id'] = $this->merchant_id;
                //验证参数
                check_params("add_user", $data, "MerchantValidate");

                //查询手机号是否存在

                $info=MerchantUser::where('phone',$data['phone'])->find();
                if($info){
                    return_msg(400,'failure','手机号已经存在');
                }
                $data['password'] = addslashes(encrypt_password($data['password'],$data['phone']));
                $result = MerchantUser::insert($data,true);
                if($result){
                    return_msg(200,'添加成功');
                }else{
                    return_msg(400,'添加失败');
                }
            }elseif(!empty($this->user_id)){
                //传入门店id
                $data=$request->post();
                //查询店长所属商户id
                $merchant=MerchantUser::field('merchant_id')->where('id',$this->user_id)->find();
                $data['merchant_id']=$merchant['merchant_id'];
                //验证
                $validate=Loader::validate('MerchantValidate');
                if(!$validate->scene('add_user')->check($data)){
                    $error=$validate->getError();
                    return_msg(400,$error);
                }
                //查询手机号是否存在
                $info = MerchantUser::where('phone',$data['phone'])->find();
                if($info){
                    return_msg(400,'failure','手机号已经存在');
                }
                $data['password']=addslashes(encrypt_password($data['password'],$data['phone']));
                $result = MerchantUser::insert($data,true);
                if($result){
                    return_msg(200,'添加成功');
                }else{
                    return_msg(400,'添加失败');
                }
            }

        }else{

            if(!empty($this->merchant_id)){
                //取出所有门店信息
                $data=MerchantShop::field(['id','shop_name'])->where('merchant_id',$this->merchant_id)->select();

                return_msg(200,'success',$data);

            }elseif(!empty($this->user_id)){
                //取出店长所属门店
                $info = MerchantUser::field('shop_id')->where('id',$this->user_id)->find();

                $data = MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();

                return_msg(200,'success',$data);
            }

        }

    }
    /**
     * 编辑人员
     *
     * @return \think\Response
     * @throws Exception
     */
    public function edit(Request $request)
    {

        if($request->isPost()){

            $data = $request->post();

            $data['password'] = addslashes(encrypt_password($data['password'],$data['phone']));

            $result=MerchantUser::update($data);

            if($result){
                return_msg(200,'修改成功');
            }else{
                return_msg(400,'修改失败');
            }
        }else{
            $id=$request->param('id');

            if(!empty($this->merchant_id)){
                //获取员工信息
                $data['list']=MerchantUser::alias('a')
                    ->field('a.id,a.name,a.phone,a.password,a.role,b.shop_name')
                    ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                    ->where('a.id',$id)
                    ->find();
                //获取所有门店信息
                $data['shop']=MerchantShop::field('id,shop_name')
                    ->where('merchant_id',$this->merchant_id)
                    ->select();
                return_msg(200,'success',$data);
            }elseif(!empty($this->user_id)){
                //获取员工信息
                $data['list'] = MerchantUser::alias('a')
                    ->field('a.id,a.name,a.phone,a.password,a.role,b.shop_name')
                    ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                    ->where('a.id',$id)
                    ->find();

                //取出店长所属门店
                $info = MerchantUser::field('shop_id')->where('id',$this->user_id)->find();

                $data['shop'] = MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();

                return_msg(200,'success',$data);
            }

        }


    }
}
