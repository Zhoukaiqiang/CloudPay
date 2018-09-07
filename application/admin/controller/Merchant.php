<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Merchant extends Controller
{
    /**
     * 商户首页(默认显示通过审核的商户) 0待审核 1开通中 2通过 3未通过
     *
     * @return \think\Response
     */
    public function index()
    {
        $data=\app\admin\model\TotalMerchants::where('review_status',2)->select();
        return view('data',$data);
    }

    /**
     * 显示商户正常营业的详情页
     *
     * @return \think\Response
     */
    public function normal_detail()
    {
        $id=request()->param('id');
        $data=\app\admin\model\TotalMerchants::where('id',$id)->find();
        return view('normal_detail',['data'=>$data]);
    }

    /**
     * 启用商户 0开启 1关闭
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function enable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=\app\admin\model\TotalMerchants::where('id',$id)->update(['status'=>0]);
        if($result){
            $this->success('已启用','index');
        }else{
            $this->error('启用失败','index');
        }
    }

    /**
     * 停用商户 0开启 1关闭
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function disable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=\app\admin\model\TotalMerchants::where('id',$id)->update(['status'=>1]);
        if($result){
            $this->success('已停用','index');
        }else{
            $this->error('停用失败','index');
        }
    }

    /**
     * 显示直联待审列表
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @param  $channel alipay支付宝直联 wechat微信直联 middle间联
     * @return \think\Response
     */
    public function review_list()
    {
        //显示审核中以及不属于间联的商户
        $where=[
            'review_status'=>['<>',2],
            'channel'       =>['<>','middle']
        ];
        $data=\app\admin\model\TotalMerchants::where($where)->select();
        return view('review_list',['data'=>$data]);
    }

    /**
     * 显示直联待审详情页
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function review_detail()
    {
        $id=request()->param('id');
        //显示当前商户数据
        $data=\app\admin\model\TotalMerchants::where('id',$id)->find();
        return view('review_detail',['data'=>$data]);
    }

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
        $result=\app\admin\model\TotalMerchants::where('id',$id)->update(['review_status'=>1]);
        if($result){
            //提交给第三方审核

            $this->success('已提交审核','index');
        }else{
            $this->error('提交审核失败','index');
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
        $result=\app\admin\model\TotalMerchants::where('id',$data['id'])->update(['review_status'=>3,'rejected'=>$data['rejected']]);
        if($result){
            $this->success('已驳回','index');
        }else{
            $this->error('驳回失败','index');
        }
    }

    /**
     * 显示间联未通过列表
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @param  $channel alipay支付宝直联 wechat微信直联 middle间联
     * @return
     */
    public function review_middle()
    {
        //显示未通过并属于间联的商户
        $where=[
            'review_status'=>['=',3],
            'channel'       =>['=','middle']
        ];
        $data=\app\admin\model\TotalMerchants::where($where)->select();
        return view('review_middle',['data'=>$data]);
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
        $data=\app\admin\model\TotalMerchants::where('id',$id)->find();
        return view('middle_detail',['data'=>$data]);
    }

}
