<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\ShopActiveDiscount;
use app\merchant\model\ShopActiveRecharge;
use think\Controller;
use think\Request;

class Active extends Controller
{
    /**
     * 充值送
     *
     * @return \think\Response
     */
    public function recharge(Request $request)
    {
        if($request->isPost()){
            $data=$request->post();
            if(is_array($data['recharge_money'])){
                foreach($data['recharge_money'] as $k=>$v){
                    foreach($data['give_money'] as $k1=>$v1){
                        if($k==$k1){
                            //判断是永久还是设置时间
                            if($data['recharge_time']==0){
                                $arr=[
                                    'recharge_money'=>$v,
                                    'give_money'=>$v1,
                                    'name'=>$data['name'],
                                    'recharge_time'=>0,
                                    'shop_id'=>$data['shop_id']
                                ];
                            }elseif($data['recharge_time']==1){
                                $arr=[
                                    'recharge_money'=>$v,
                                    'give_money'=>$v1,
                                    'name'=>$data['name'],
                                    'recharge_time'=>1,
                                    'shop_id'=>$data['shop_id'],
                                    'start_time'=>$data['start_time'],
                                    'end_time'=>$data['end_time']
                                ];
                            }
                            $result=ShopActiveRecharge::insert($arr,true);
                            if($result){
                                $res[]=200;
                            }else{
                                $res[]=400;
                            }
                        }
                    }
                }
                if(in_array(400,$res)){
                    return_msg(400,'操作失败');
                }else{
                    return_msg(200,'操作成功');
                }
            }else{
                //验证
                $result=ShopActiveRecharge::insert($data,true);
                if($result){
                    return_msg(200,'操作成功');
                }else{
                    return_msg(400,'操作失败');
                }
            }
        }else{
            //取出所有门店
            $data=MerchantShop::field('id,shop_name')->select();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 折扣活动
     *
     * @return \think\Response
     */
    public function discount(Request $request)
    {
        if($request->isPost()){
            $data=$request->post();
            //验证
            $result=ShopActiveDiscount::insert($data,true);
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }else{
            //取出所有门店
            $data=MerchantShop::field('id,shop_name')->select();
            return_msg(200,'success',$data);
        }
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
