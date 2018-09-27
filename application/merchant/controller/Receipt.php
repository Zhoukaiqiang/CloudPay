<?php

namespace app\merchant\controller;

use app\merchant\model\Order;
use think\Controller;
use think\Request;

class Receipt extends Controller
{
    /**
     * 显示账单详情
     *
     * @return \think\Response
     */
    public function receipt_detail(Request $request)
    {
        $id=$request->param('id');
        $data=Order::field('id,status,received_money,order_money,discount,received_store,cashier,pay_time,pay_type,order_remark,order_number,status,authorize_number,prove_number,give_money')->where('id',$id)->find();
        return_msg(200,'success',$data);
    }

    /**
     * 显示账单
     *
     * @return \think\Response
     */
    public function receipt_bill()
    {
        //获取商户id
        $merchant_id=session('merchant_id');
        $merchant_id=1;//测试
        $data=Order::field('id,status,order_money,pay_type,create_time')
            ->where('merchant_id',$merchant_id)
            ->whereTime('create_time','>','yesterday')
            ->select();
        return_msg(200,'success',$data);
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
