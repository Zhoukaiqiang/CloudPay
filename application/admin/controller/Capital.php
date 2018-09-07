<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use app\admin\model\TotalCapital;
use think\Controller;
use think\Request;

class Capital extends Controller
{
    /**
     * 显示未结算的首页列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $data=TotalCapital::alias('a')
            ->field('a.id,a.date,a.settlement_start,a.settlement_end,a.description,a.account,a.invoice,b.agent_name,b.contact_person, b.agent_area,b.agent_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status=0')
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 结款
     * @param   id int 资金结算id
     * @return \think\Response
     */
    public function settlement()
    {
        if(request()->isPost()){
            //确认结算
            //改变结算状态为已结款
            $id=request()->param('id');
            //获取结款时间
            $time=time();
            $data=[
                'id'=>$id,
                'status'=>1,
                'settlement_time'=>$time
            ];
            $result=TotalCapital::update($data);
            if($result){
                $this->success('已结款','index');
            }else{
                $this->error('结款失败','index');
            }
        }else{
            $id=request()->param('id');
            $data=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg('200','success',$data);
        }
    }

    /**
     * 暂缓处理
     *
     * @param  id int 资金结算id
     * @param  description string 暂缓处理描述
     * @return \think\Response
     */
    public function deal()
    {
        if(request()->isPost()){
            $data=request()->post();
            //跟新数据
            $result=TotalCapital::update($data,true);
            if($result){
                $this->success('处理成功','index');
            }else{
                $this->error('处理失败','index');
            }
        }else{
            $id=request()->param('id');
            $data=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg('200','success',$data);
        }
    }

    /**
     * 显示已结算列表
     *
     * @param
     * @return \think\Response
     */
    public function settled()
    {
        $data=TotalCapital::alias('a')
            ->field('a.date,a.settlement_start,a.settlement_end,a.account,b.agent_name,a.settlement_time,a.invoice,b.contact_person, b.agent_area,b.agent_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status=1')
            ->select();
        return_msg('200','success',$data);
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
