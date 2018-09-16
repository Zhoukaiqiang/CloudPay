<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Merchant extends Controller
{
    /**
     * 显示当前代理商下所有商户列表
     */
    public function index()
    {
        $agent_id=session('agent_id');
        $agent_id=1;
        //获取总行数
        $rows=TotalMerchant::where('agent_id',$agent_id)->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,a.review_status,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }
    /**
     * 显示当前代理商下正常使用的商户列表
     *  review_status 审核状态 0待审核 1开通中 2通过 3未通过
     *  status  账号状态 0开启 1关闭
     * @return \think\Response
     */
    public function normal_list()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $where=[
            'a.review_status' =>2,
            'a.status'=>0,
            'a.agent_id'=>$agent_id
        ];
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg('200','success',$data);
    }

    /**
     * 显示已停用商户
     *
     * @return \think\Response
     */
    public function stop_list()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $where=[
            'a.review_status' =>2,
            'a.status'=>1,
            'a.agent_id'=>$agent_id
        ];
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        return_msg('200','success',$data);
    }

    /**
     * 显示审核中的商户
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function review_list()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $where=[
            'a.review_status'=>['<',2],
            'a.agent_id'=>['=',$agent_id]
        ];
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg('200','success',$data);
    }

    /**
     * 显示已驳回商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function reject()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $where=[
            'review_status'=>3,
            'agent_id'=>$agent_id
        ];
        $rows=TotalMerchant::where($where)->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.rejected,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
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
        $agent_id=session('agent_id');
        $agent_id=1;
        if(request()->isPost()){
            $data=request()->post();
            $data['channel']=3;//表示间联
            //验证
            //上传图片
            $data['attachment']=$this->upload_logo();
            $data['agent_id']=$agent_id;
            $data['attachment']=json_encode($data['attachment']);
            $info=TotalMerchant::insert($data,true);
            if($info){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出当前代理商下所有合伙人
            $data=AgentPartner::field(['id','partner_name'])->where('agent_id',$agent_id)->select();
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
        $agent_id=session('agent_id');
        if($request->isPost()){
            $data=request()->post();
            //验证

            //上传图片
            $data['agent_id']=$agent_id;
            $data['attachment']=$this->upload_logo();
            $data['attachment']=json_encode($data['attachment']);
            $info=TotalMerchant::insert($data);
            if($info){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出所有合伙人信息
            $data=AgentPartner::field(['id','partner_name'])->where('agent_id',$agent_id)->select();
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
