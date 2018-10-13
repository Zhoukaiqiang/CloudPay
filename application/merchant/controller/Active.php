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
use think\Session;

class Active extends Common
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
            $recharge_money=implode(',',$data['recharge_money']);
            $give_money=implode(',',$data['give_money']);
            if(is_array($data['shop_id'])){
                foreach($data['shop_id'] as $v){
                    //获取商户id
                    $merchant=MerchantShop::field('merchant_id')->where('id',$v)->find();
                    if($data['active_time']==0){
                        //验证
                        check_params('recharge',$data,'MerchantValidate');
                        $arr=[
                            'shop_id'=>$v,
                            'recharge_money'=>$recharge_money,
                            'give_money'=>$give_money,
                            'name'=>$data['name'],
                            'merchant_id'=>$merchant['merchant_id'],
                            'status'=>1,
                            'create_time'=>time()
                        ];
                    }elseif($data['active_time']==1){
                        check_params('new_recharge',$data,'MerchantValidate');
                        $arr=[
                            'shop_id'=>$v,
                            'recharge_money'=>$recharge_money,
                            'give_money'=>$give_money,
                            'name'=>$data['name'],
                            'merchant_id'=>$merchant['merchant_id'],
                            'status'=>1,
                            'create_time'=>time(),
                            'start_time'=>$data['start_time'],
                            'end_time'=>$data['end_time'],
                        ];
                    }
                    $result=ShopActiveRecharge::insert($arr,true);
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
            }
        }else{
            if(!empty($this->merchant_id)){
                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                //查询门店是否有活动
                foreach($data as $k=>$v){
                    $res=ShopActiveRecharge::where(['shop_id'=>$v['id'],'status'=>1])->find();
                    if(!empty($res)){
                        unset($data[$k]);
                    }
                }
                if(empty($data)){
                    return_msg(400,'请先关闭活动');
                }
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();
                $res=ShopActiveRecharge::where(['shop_id'=>$data['id'],'status'=>1])->find();
                if($res){
                    return_msg(400,'请先关闭活动');
                }else{
                    return_msg(200,'success',$data);
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
                if($data['active_time']==0){
                    //验证
                    check_params('discount',$data,'MerchantValidate');
                }elseif($data['active_time']==1){
                    check_params('new_discount',$data,'MerchantValidate');
                }
                foreach($data['shop_id'] as $k=>$v){
                    $data['shop_id']=$v;
                    $data['status']=1;
                    $data['create_time']=time();
                    //获取商户id
                    $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
                    $data['merchant_id']=$info['merchant_id'];
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
            }
        }else{
            if(!empty($this->merchant_id)){
                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                //查询门店是否有活动
                foreach($data as $k=>$v){
                    $res=ShopActiveDiscount::where(['shop_id'=>$v['id'],'status'=>1])->find();
                    if(!empty($res)){
                        unset($data[$k]);
                    }
                }
                if(empty($data)){
                    return_msg(400,'请先关闭活动');
                }
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();
                $res=ShopActiveDiscount::where(['shop_id'=>$data['id'],'status'=>1])->find();
                if($res){
                    return_msg(400,'请先关闭活动');
                }else{
                    return_msg(200,'success',$data);
                }
            }

        }
    }

    /**
     * 会员专享
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function exclusive(Request $request)
    {
        $info=ShopActiveExclusive::field('status')->where(['merchant_id'=>$this->merchant_id,'status'=>1])->find();
        if(empty($info)){
            return_msg(400,'请先关闭活动');
        }
        $data=$request->post();
        $data['status']=1;//测试
        $data['create_time']=time();
        $data['consump_number']=$request->post('consump_number') ? $request->post('consump_number') : -1;
        $data['last_consump']=$request->post('last_consump') ? $request->post('last_consump') : -1;
        $data['recharge_total']=$request->post('recharge_total') ? $request->post('recharge_total') : -1;
        $data['consump_total']=$request->post('consump_total') ? $request->post('consump_total') : -1;
        $data['merchant_id']=$this->merchant_id;
        //验证
        check_params('exclusive',$data,'MerchantValidate');
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
            return_msg(200,'派券成功');
        }else{
            return_msg(400,'派券失败');

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
            //验证
            check_params('share',$data,'MerchantValidate');
            if(is_array($data['shop_id'])){
                foreach($data['shop_id'] as $k=>$v){
                    $data['shop_id'] = $v;
                    $data['status'] = 1;
                    $data['create_time'] = time();
                    //获取商户id
                    $info=MerchantShop::field('merchant_id')->where('id',$data['shop_id'])->find();
                    $data['merchant_id']=$info['merchant_id'];

                    $result = ShopActiveShare::insert($data,true);
                    if($result){
                        $res[] = 200;
                    }else{
                        $res[] = 400;
                    }
                }
                if(in_array(400,$res)){
                    return_msg(400,'操作失败');
                }else{
                    return_msg(200,'操作成功');
                }
            }

        }else{
            if(!empty($this->merchant_id)){
                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                //查询门店是否有活动
                foreach($data as $k=>$v){
                    $res=ShopActiveShare::where(['shop_id'=>$v['id'],'status'=>1])->find();
                    if(!empty($res)){
                        unset($data[$k]);
                    }
                }
                if(empty($data)){
                    return_msg(400,'请先关闭活动');
                }
                return_msg(200,'success',$data);
            }elseif(empty($this->merchant_id) && !empty($this->user_id)){
                $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->find();
                $res=ShopActiveShare::where(['shop_id'=>$data['id'],'status'=>1])->find();
                if($res){
                    return_msg(400,'请先关闭活动');
                }else{
                    return_msg(200,'success',$data);
                }
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
            $data['recharge'][] = ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.active_time'=>0,'a.merchant_id'=>$this->merchant_id,'a.status'=>1])
                ->order('a.create_time desc')
                ->select();

            //选择时间

            $data['recharge'][] = ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.active_time'=>1,'a.merchant_id'=>$this->merchant_id])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //折扣活动 取出商户下所有门店活动
            //取出永久有效活动
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.active_time'=>0,'a.merchant_id'=>$this->merchant_id,'a.status'=>1])
                ->order('a.create_time desc')
                ->select();

            //选择时间
            $data['discount'][]=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.merchant_id'=>$this->merchant_id,'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();

            //会员专享
            $data['exclusive'][]=ShopActiveExclusive::where(['merchant_id'=>$this->merchant_id,'status'=>1])->whereTime('end_time','>',time())->order('create_time desc')->select();

            //分享红包
            $data['share'][] = ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.merchant_id'=>$this->merchant_id,'status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            return_msg(200,'success',$data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //取出门店下所有活动
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            $where=[
                'active_time'=>0,
                'shop_id'=>$info['shop_id'],
                'status'=>1
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
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //分享
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //充值
            //取出永久有效活动
            $data['recharge'][] = ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1,'a.active_time'=>0])
                ->order('a.create_time desc')
                ->select();
            //选择时间
            $data['recharge'][]=ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1,'a.active_time'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
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
    /*public function stop_active(Request $request)
    {
        $data=$request->post();
        if($data['active']=='recharge'){
            $this->check('ShopActiveRecharge',$data['id']);
        }elseif($data['active']=='discount'){
            $this->check('ShopActiveDiscount',$data['id']);
        }elseif($data['active']=='exclusive'){
            $this->check('ShopActiveExclusive',$data['id']);
        }elseif($data['active']=='share'){
            $this->check('ShopActiveShare',$data['id']);
        }
    }

    public function check($active,$id)
    {
        $result=$active::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,'操作成功');
        }else{
            return_msg(400,'操作失败');

        }
    }*/
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
     * 关闭分享
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function share_stop(Request $request)
    {
        $id = $request->post('id');
        $result=ShopActiveShare::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,'操作成功');
        }else{
            return_msg(400,'操作失败');

        }
    }

    /**
     * 关闭会员专享
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function exclusive_stop(Request $request)
    {
        $id = $request->post('id');
        $result=ShopActiveExclusive::where('id',$id)->update(['status'=>0]);
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
