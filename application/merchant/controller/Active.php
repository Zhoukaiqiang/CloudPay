<?php

namespace app\merchant\controller;

use app\merchant\model\MemberExclusive;
use app\merchant\model\MerchantMember;
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
                                    'merchant_id'=>$this->merchant_id,
                                    'create_time'=>time()
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
                                    'merchant_id'=>$this->merchant_id,
                                    'create_time'=>time()
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
                $data['create_time']=time();
                $data['merchant_id']=$this->merchant_id;
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
            if(is_array($data['shop_id'])){
                foreach($data['shop_id'] as $k=>$v){
                    $data['shop_id']=$v;
                    $data['status']=1;
                    $data['create_time']=time();
                    //获取商户id
                    $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
                    $data['merchant_id']=$info['merchant_id'];
                    //验证
                    $result=ShopActiveDiscount::insert($data,true);
                    if($result){
                        $res[]=200;
                    }else{
                        $res[]=400;
                    }
                }
                if(in_array(400,$res)){
                    return_msg(400,'操作失败');
                }else{
                    return_msg(200,'操作成功');
                }
            }else{
                $data['status']=1;
                $data['create_time']=time();
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
        $data['create_time']=time();
        $data['consump_number']=$request->post('consump_number') ? $request->post('consump_number') : -1;
        $data['last_consump']=$request->post('last_consump') ? $request->post('last_consump') : -1;
        $data['recharge_total']=$request->post('recharge_total') ? $request->post('recharge_total') : -1;
        $data['consump_total']=$request->post('consump_total') ? $request->post('consump_total') : -1;
        $data['merchant_id']=$this->merchant_id;
        $insertid=ShopActiveExclusive::insertGetId($data,true);
        if($insertid){
            //判断会员是否符合条件
            if($data['consump_number'] != -1){
                $info=MerchantMember::field('id,consump_number')->select();
                foreach($info as $v){
                    if($v['consump_number'] >= $data['consump_number']){
                        //派卷
                        $arr=[
                            'SN'=>getSN(),
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$insertid,
                            'status'=>1
                        ];
                    MemberExclusive::insert($arr);
                    }
                }
            }elseif($data['last_consump'] !=-1){
                $info=MerchantMember::field('id,consumption_time')->select();
                foreach($info as $v){
                    //比较时间戳大小
                    if($v['consumption_time'] >= $data['last_consump']){
                        //派卷
                        $arr=[
                            'SN'=>getSN(),
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$insertid,
                            'status'=>1
                        ];
                        MemberExclusive::insert($arr);
                    }
                }
            }elseif($data['recharge_total'] !=-1){
                $info=MerchantMember::field('id,recharge_money')->select();
                foreach($info as $v){
                    if($v['recharge_money'] >= $data['recharge_total']){
                        //派卷
                        $arr=[
                            'SN'=>getSN(),
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$insertid,
                            'status'=>1
                        ];
                        MemberExclusive::insert($arr);
                    }
                }
            }elseif($data['consump_total'] !=-1){
                $info=MerchantMember::field('id,consumption_money')->select();
                foreach($info as $v){
                    if($v['consumption_money'] >= $data['consump_total']){
                        //派卷
                        $arr=[
                            'SN'=>getSN(),
                            'member_id'=>$v['id'],
                            'exclusive_id'=>$insertid,
                            'status'=>1
                        ];
                        MemberExclusive::insert($arr);
                    }
                }
            }
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
            if(is_array($data['shop_id'])){
                foreach($data['shop_id'] as $k=>$v){
                    $data['shop_id']=$v;
                    $data['status']=1;
                    $data['create_time']=time();
                    //获取商户id
                    $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
                    $data['merchant_id']=$info['merchant_id'];
                    //验证
                    $result=ShopActiveShare::insert($data,true);
                    if($result){
                        $res[]=200;
                    }else{
                        $res[]=400;
                    }
                }
                if(in_array(400,$res)){
                    return_msg(400,'操作失败');
                }else{
                    return_msg(200,'操作成功');
                }
            }else{
                $data['status']=1;
                $data['create_time']=time();
                //获取商户id
                $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
                $data['merchant_id']=$info['merchant_id'];
                $result=ShopActiveShare::insert($data,true);
                if($result){
                    return_msg(200,'操作成功');
                }else{
                    return_msg(400,'操作失败');
                }
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
            $data['recharge'][]=ShopActiveRecharge::where($where)->order('create_time desc')->select();
            //选择时间
            $data['recharge'][]=ShopActiveRecharge::where('merchant_id',$this->merchant_id)->whereTime('end_time','>',time())->order('create_time desc')->select();
            //折扣活动 取出商户下所有门店活动
            //取出永久有效活动
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.active_time'=>0,'a.merchant_id'=>$this->merchant_id])
                ->order('a.create_time desc')
                ->select();
            //选择时间
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //会员专享
            $data['exclusive'][]=ShopActiveDiscount::where('merchant_id',$this->merchant_id)->whereTime('end_time','>',time())->order('create_time desc')->select();
            //分享红包
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
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
                ->order('a.create_time desc')
                ->select();
            //选择时间
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.shop_id',$info['shop_id'])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //分享
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.shop_id',$info['shop_id'])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            return_msg(200,'success',$data);
        }

    }

    /**
     * 关闭充值送活动
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function recharge_stop(Request $request)
    {
        $id=$request->post('id');
        $result=ShopActiveRecharge::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,'操作成功');
        }else{
            return_msg(400,'操作失败');

        }
    }

    /**
     * 关闭折扣
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function discount_stop(Request $request)
    {
        $id=$request->post('id');
        $result=ShopActiveDiscount::where('id',$id)->update(['status'=>0]);
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

        $data=$request->param();
        if($data['name']=="discount"){
            //折扣
            $info=ShopActiveDiscount::alias('a')
                ->field('b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$data['merchant_id'])->select();
            return_msg(200,'success',$info);
        }elseif($data['name']=="share"){
            //分享
            $info=ShopActiveShare::alias('a')
                ->field('b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where('a.merchant_id',$data['merchant_id'])->select();
            return_msg(200,'success',$info);
        }


    }
}
