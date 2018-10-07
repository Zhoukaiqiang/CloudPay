<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\ShopActiveDiscount;
use app\merchant\model\ShopActiveExclusive;
use app\merchant\model\ShopActiveRecharge;
use app\merchant\model\ShopActiveShare;
use think\Controller;
use think\Request;

class Active extends Controller
{
    public $merchant_id;
    public $user_id;
    public function __construct()
    {
        $this->merchant_id=session('merchant_id') ? session('merchant_id') : null;
        $this->user_id=session('user_id') ? session('user_id') : 1;
    }
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
                            if($data['active_time']==0){
                                $arr=[
                                    'recharge_money'=>$v,
                                    'give_money'=>$v1,
                                    'name'=>$data['name'],
                                    'active_time'=>0,
                                    'status'=>1,
                                    'merchant_id'=>$this->merchant_id
                                ];
                            }elseif($data['active_time']==1){
                                $arr=[
                                    'recharge_money'=>$v,
                                    'give_money'=>$v1,
                                    'name'=>$data['name'],
                                    'active_time'=>1,
                                    'start_time'=>$data['start_time'],
                                    'end_time'=>$data['end_time'],
                                    'status'=>1,
                                    'merchant_id'=>$this->merchant_id
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
                $data['status']=1;
                $result=ShopActiveRecharge::insert($data,true);
                if($result){
                    return_msg(200,'操作成功');
                }else{
                    return_msg(400,'操作失败');
                }
            }
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
            $data['status']=1;
            //获取商户id
            $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
            $data['merchant_id']=$info['merchant_id'];
            //验证
            $result=ShopActiveDiscount::insert($data,true);
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }else{
            if(!empty($this->merchant_id)){
                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->select();
                return_msg(200,'success',$data);
            }

        }
    }

    /**
     * 会员专享
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function exclusive (Request $request)
    {
        $data=$request->post();
        $data['status']=1;
        $data['consump_number']=$request->post('consump_number') ? $request->post('consump_number') : -1;
        $data['last_consump']=$request->post('last_consump') ? $request->post('last_consump') : -1;
        $data['recharge_total']=$request->post('recharge_total') ? $request->post('recharge_total') : -1;
        $data['consump_total']=$request->post('consump_total') ? $request->post('consump_total') : -1;
        $data['merchant_id']=$this->merchant_id;
        $info=ShopActiveExclusive::insert($data,true);
        if($info){
            return_msg(200,'派卷成功');
        }else{
            return_msg(400,'派卷失败');

        }
    }

    /**
     * 分享红包
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function share(Request $request)
    {
        if($request->isPost()){
            $data=$request->post();
            $data['status']=1;
            //获取商户id
            $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
            $data['merchant_id']=$info['merchant_id'];
            $result=ShopActiveShare::insert($data,true);
            if($result){
                return_msg(200,'分享成功');
            }else{
                return_msg(400,'分享失败');
            }
        }else{
            if(!empty($this->merchant_id)){
                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->select();
                return_msg(200,'success',$data);
            }
        }
    }

    /**
     * 活动列表
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function active_list()
    {
        if(!empty($this->merchant_id)){
            //充值送
            //取出永久有效活动
            $where=[
                'active_time'=>0,
                'merchant_id'=>$this->merchant_id,
            ];
            $data['recharge'][]=ShopActiveRecharge::where($where)->select();
            //选择时间
            $data['recharge'][]=ShopActiveRecharge::where('merchant_id',$this->merchant_id)->whereTime('end_time','>',time())->select();
            //折扣活动 取出商户下所有门店活动
            //取出永久有效活动
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.active_time'=>0,'a.merchant_id'=>$this->merchant_id])
                ->select();
            //选择时间
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.end_time','>',time())
                ->select();
            //会员专享
            $data['exclusive'][]=ShopActiveDiscount::where('merchant_id',$this->merchant_id)->whereTime('end_time','>',time())->select();
            //分享红包
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.end_time','>',time())
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //取出门店下所有活动
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            $where=[
                'active_time'=>0,
                'shop_id'=>$info['shop_id']
            ];
            //折扣
            //取出永久有效活动
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where($where)
                ->select();
            //选择时间
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.shop_id',$info['shop_id'])
                ->whereTime('a.end_time','>',time())
                ->select();
            //分享
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.shop_id',$info['shop_id'])
                ->whereTime('a.end_time','>',time())
                ->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 关闭活动
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function stop(Request $request)
    {
        $merchant_id=$request->post('merchant_id');
        $result=ShopActiveRecharge::where('merchant_id',$merchant_id)->update(['status'=>0]);
        if($result){
            return_msg(200,'操作成功');
        }else{
            return_msg(400,'操作失败');

        }
    }

    /**
     * 活动门店
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function active_shop(Request $request)
    {

    }
}
