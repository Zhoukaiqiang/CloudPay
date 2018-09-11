<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Merchant extends Controller
{
    /**
     * 显示正常使用的商户列表
     *
     * @return \think\Response
     */
    public function normal_list()
    {
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.review_status=2 and a.status=0')
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 显示已停用商户
     *
     * @return \think\Response
     */
    public function stop_list()
    {
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.review_status=2 and a.status=1')
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 显示审核中的商户
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function review_list(Request $request)
    {
        //
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.review_status<2')
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 显示已驳回商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function reject($id)
    {
        //
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.review_status=3')
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 新增间联商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function add_middle()
    {
        //
        if(request()->isPost()){
            $data=request()->post();
            $data['channel']=3;//表示间联
            //验证
            //上传图片
            $data['attachment']=$this->upload_logo();
            $data['attachment']=json_encode($data['attachment']);
            $info=TotalMerchant::create($data,true);
            if($info){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出所有合伙人信息
            $data=AgentPartner::field(['id','partner_name'])->select();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 新增直联商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function add_straight(Request $request)
    {
        //
        if($request->isPost()){
            $data=request()->post();
            //验证
            //上传图片
            $data['attachment']=$this->upload_logo();
            $data['attachment']=json_encode($data['attachment']);
            $info=TotalMerchant::create($data,true);
            if($info){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出所有合伙人信息
            $data=AgentPartner::field(['id','partner_name'])->select();
            return_msg(200,'success',$data);
        }
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
        $files=request()->file('attachment');
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
