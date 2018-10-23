<?php

namespace app\admin\controller;

use app\admin\model\TotalAdmin;
use app\admin\controller\Admin;
use think\Controller;
use think\Request;
use think\Loader;

class Personal extends Admin
{
    /**
     * 显示人员列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询不是超级管理员的人员

        $rows = TotalAdmin::count("id");

        $pages = page($rows);

        $data['list']=TotalAdmin::limit($pages['offset'], $pages['limit'])->select();

        $res = collection($data)->toArray();

        if (count($res) > 0) {
            $data['pages'] = $pages;
            $data['rows'] = $rows;
            return_msg(200,'success',$data);
        }else {
            return_msg(400, '没有数据');
    }


    }

    /**
     * 添加人员
     *
     * @return \think\Response
     */
    public function add()
    {
        if(request()->isPost()){
            $data=request()->post();
            $validate = Loader::validate('AdminValidate');
            if(!$validate->scene('user')->check($data)){
                $error=$validate->getError();
                return_msg(400,$error);
            }
            //查询用户名是否存在
            $user=TotalAdmin::where('name',$data['name'])->find();
            if($user){
                return_msg(400,'用户名已经存在');
            }
            //查询用户账号是否存在
            $result=TotalAdmin::where('phone',$data['phone'])->find();
            if($result){
                return_msg(400,'用户账号已经存在');
            }
            //获取创建时间
            $data['create_time']=time();
            $data['password']=encrypt_password($data['password'], $data['phone']);
            $info=TotalAdmin::insert($data,true);
            if($info){
                return_msg(200,'保存成功',$info);
            }else{
                return_msg(400,'保存失败');
            }
        }else{
            return_msg(400, "请用POST方法");
        }
    }

    /**
     * 人员详情页面
     *
     * @param  id int 人员id
     * @return \think\Response
     */
    public function detail()
    {
       if(request()->isPost()){
           //需要传一个主键id
           $data=request()->post();
           //验证
           $validate = Loader::validate('AdminValidate');
           if(!$validate->scene('user_edit')->check($data)){
               $error=$validate->getError();
               return_msg(400,$error);
           }
           if ($data['status'] == -1) {
               $data['status'] = 0;
           }
           //查询用户名是否存在
           $where=[
               'id'     =>['<>',$data['id']],
               'name'   =>['=',$data['name']]
           ];
           $user = TotalAdmin::where($where)->find();
           if($user){
               return_msg(400,'用户名已经存在');
           }
           //查询用户账号是否存在
           $where=[
               'id'     =>['<>',$data['id']],
               'phone'   =>['=',$data['phone']]
           ];
           $result=TotalAdmin::where($where)->find();
           if($result){
               return_msg(400,'用户账号已经存在');
           }

           if(isset($data['password'])){

               $data['password']=encrypt_password(addslashes($data['password']));
           }

           $info=TotalAdmin::update($data,true);
           if($info){
               return_msg(200,'修改成功',$info);
           }else{
               return_msg(400,'修改失败');
           }
       }else{
           $id=request()->param('id');
           $data=TotalAdmin::where('id',$id)->find();
           unset($data['password']);
           return_msg(200,'success',$data);
       }
    }


}
