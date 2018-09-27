<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use think\Controller;
use app\admin\model\TotalMerchant;
use think\Request;

class Merchant extends Controller
{
    /**
     * 商户首页(默认显示通过审核的商户)
     * review_status 0待审核 1开通中 2通过 3未通过
     *
     * @return \think\Response
     */
    public function index()
    {
        //获取总行数
        $rows=TotalMerchant::where('review_status',2)->count();
        $pages = page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.review_status = 2')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        $data['pages']['rows'] = $rows;
        return_msg('200','success',$data);
    }

    /**
     * 显示商户正常营业的详情页
     *
     * @return \think\Response
     */
    public function normal_detail()
    {
        $id=request()->param('id');
        $data=TotalMerchant::where('id',$id)->find();
        //获取所有代理商
        $info=$this->get_agent();
        $data['agent']=$info;
        return_msg('200','success',$data);
    }

    /**
     * 启用商户 0关闭 1开启
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function enable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>1]);
        if($result){
            return_msg(200,"启用成功");
        }else{
            return_msg(400,"启用失败");
        }
    }

    /**
     * 停用商户 0关闭 1开启
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function disable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,"已停用");
        }else{
            return_msg(400,"停用失败");
        }
    }

    /**
     * 显示直联待审列表
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @param  $channel 当前通道 0支付宝 1微信 2支付宝微信直联 3间联
     * @return \think\Response
     */
//    public function review_list()
//    {
//        //显示审核中的直联商户
//        $where=[
//            'review_status'=>['<>',2],
//            'channel'       =>['<','3']
//        ];
//        //获取总行数
//        $row=TotalMerchant::where($where)->count();
//        $pages=page($row);
//        //显示审核中的直联商户
//        $data=TotalMerchant::alias('a')
//            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.review_status,b.contact_person,b.agent_phone')
//            ->join('cloud_total_agent b','a.agent_id=b.id','left')
//            ->where($where)
//            ->limit($pages['offset'],$pages['limit'])
//            ->select();
//        $data['pages']=$pages;
//        return_msg('200','success',$data);
//    }

    /**
     * 显示直联待审详情页
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
//    public function review_detail()
//    {
//        $id=request()->param('id');
//        //显示当前商户数据
//        $data=TotalMerchant::where('id',$id)->find();
//        //获取所有代理商
//        $info=$this->get_agent();
//        $data['agent']=$info;
//        return_msg('200','success',$data);
//    }

    /**
     * 点击提交提交给第三方审核
     *
     * @param  审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     */
    public function review_submit()
    {
        $id=request()->param('id');
        //修改审核状态
        $result=TotalMerchant::where('id',$id)->update(['review_status'=>1]);
        if($result){
            //提交给第三方审核
            return_msg(200,"提交审核成功");
        }else{
            return_msg(400,"提交审核失败");
        }
    }

    /**
     * 驳回
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @param  $rejected    驳回原因
     * @return
     */
    public function review_rejected()
    {
        $data=request()->param();
        $result=TotalMerchant::where('id',$data['id'])->update(['review_status'=>3,'rejected'=>$data['rejected']]);
        if($result){
            return_msg(200,"已驳回");
        }else{
            return_msg(400,"驳回失败");
        }
    }

    /**
     * 显示间联未通过列表
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @param  $channel //当前通道 0支付宝 1微信 2支付宝微信直联 3间联
     * @return
     */
    public function review_middle()
    {
        //显示未通过并属于间联的商户
        $where=[
            'review_status'=>['=',3],
//            'channel'       =>['=','3']
        ];
        //获取总行数
        $row=TotalMerchant::where($where)->count();
        $pages=page($row);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.rejected,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg('200','success',$data);
    }

    /**
     * 显示间联详情页
     *
     * @param
     * @return
     */
    public function middle_detail()
    {
        $id=request()->param('id');
        //显示当前商户数据
        $data=TotalMerchant::where('id',$id)->find();
        //取出所有代理商
        $info=$this->get_agent();
        $data['agent']=$info;
        return_msg('200','success',$data);
    }

    /**
     * 间联详情页提交
     *
     * @param
     * @return
     */
    public function middle_submit()
    {
        $data=request()->post();
        $validate=Loader::validate('Adminvalidate');
        if(!$validate->scene('middle_submit')->check($data)){
            $error=$validate->getError();
            return_msg(400,$error);
        }
        //修改审核状态
        $result=TotalMerchant::where('id',$id)->update(['review_status'=>1]);
        if($result){
            //提交给银行审核
            return_msg(200,"提交审核成功");
        }else{
            return_msg(400,"提交审核失败");
        }
    }

    //取出所有代理商
    private function get_agent(){
        $info=TotalAgent::field(['id','agent_name'])->select();
        return $info;
    }
}
