<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/26
 * Time: 10:06
 */

namespace app\merchant\controller;



use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Commonality
{
    /**
     * APP首页展示
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        //$role 1服务员 2店长 3收银员 -1商户
//            $id=Session::get('username_', 'app')['user_id'];
//            $role=Sessin::get('username_', 'app')['role_id'];
            $role=$this->role;
            $name=$this->name;
            $id=$this->id;
            if($role==1 || $role==3){
                $name='person_info_id';
            }
            if($role==2){
                $id=MerchantUser::where('id',$id)->field('shop_id')->find();
                $id=$id->shop_id;
                }


        //查询今天的收银金额和订单笔数
        $data=Order::where([$name=>$id])
            ->whereTime('pay_time','d')
            ->field('count(id) id,sum(received_money) received_money')
            ->select();


                return_msg(200,'success',$data);



    }


}

