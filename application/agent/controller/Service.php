<?php

namespace app\agent\controller;

use app\agent\model\TotalAgent;
use think\Controller;
use think\Request;

class Service extends Controller
{
    /**
     * 显示服务商列表
     *
     * @return \think\Response
     */
    public function service_list()
    {
        //获取代理商id
        $id=request()->param('id');
        //获取当前代理商下的所有子代理
        $data=TotalAgent::field(['id,agent_name,contact_person,agent_phone,agent_mode,agent_area,create_time,status'])->where('parent_id',$id)->select();
        return_msg(200,'success',$data);
    }

    /**
     * 启用子代
     *
     * @return \think\Response
     */
    public function open_agent()
    {
        //获取当前代理商id
        $id=request()->param('id');
        $result=TotalAgent::where('id',$id)->update(['status'=>1]);
        if($result){
            return_msg(200,'启用成功');
        }else{
            return_msg(400,'启用失败');
        }
    }

    /**
     * 停用子代
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function stop_agent(Request $request)
    {
        //
        $data=$request->post();
        $data['status']=0;
        $result=TotalAgent::update($data);
        if($result){
            return_msg(200,'停用成功');
        }else{
            return_msg(400,'停用失败');
        }
    }

    /**
     * 子代详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function agent_detail()
    {
        //
        if(request()->isPost()){

        }else{
            //获取子代id
            $id=request()->param('id');
            //通过子代id查询子代信息
            $data=TotalAgent::where('id',$id)->find();
            //解析图片
            $data['contract_picture']=json_decode($data['contract_picture']);
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
