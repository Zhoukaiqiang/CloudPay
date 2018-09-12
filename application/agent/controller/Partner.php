<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Partner extends Controller
{
    /**
     * 显示员工列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //显示当前代理商下面所有员工
        $id=request()->param('parent_id');
        $data=AgentPartner::field(['id,partner_name,partner_phone,create_time,model'])
            ->where('agent_id',$id)
            ->select();
        //取出负责商户数
        foreach($data as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 新增合伙人
     *
     * @return \think\Response
     */
    public function add_partner()
    {
        if(request()->isPost()){
            //传入当前代理商id
            $data=request()->post();
            //提交时间
            $data['create_time']=time();
            //验证
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::create($data,true);
            if($result){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            return_msg(200,'success');
        }
    }

    /**
     * 删除合伙人
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function del()
    {
        //获取合伙人id
        $id=request()->param('id');
        $result=AgentPartner::destroy($id);
        if($result){
            return_msg(200,'删除成功');
        }else{
            return_msg(400,'删除失败');
        }
    }

    /**
     * 合伙人详情页
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function partner_detail()
    {
        if(request()->isPost){
            $data=request()->post();
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::update($data,true);
            if($result){
                return_msg(200,'修改成功');
            }else{
                return_msg(400,'修改失败');
            }
        }else{
            //获取合伙人id
            $id=request()->param('id');
            $data=AgentPartner::where('id',$id)->field(['id,partner_name,partner_phone,create_time,model'])->select();
            return_msg(200,'success',$data);
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
}
