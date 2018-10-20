<?php

namespace app\merchant\controller;

use app\merchant\model\MemberRecharge;
use app\merchant\model\MerchantMember;
use app\merchant\model\MerchantMemberCard;
use app\merchant\model\MerchantMemberCart;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\Order;
use app\merchant\model\ShopActiveDiscount;
use app\merchant\model\ShopActiveExclusive;
use app\merchant\model\ShopActiveRecharge;
use app\merchant\model\ShopActiveShare;
use think\Controller;
use think\Request;

class Member extends Common
{
    public $url = 'http://sandbox.starpos.com.cn/emercapp';
    /**
     * 显示会员列表
     *
     * @return \think\Response
     */
    public function member_list(Request $request)
    {
        if(!empty($this->merchant_id)){
            //显示当前商户下所有会员
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
                ->where('merchant_id',$this->merchant_id)
                ->whereTime('register_time','>','yesterday')
                ->select();
            check_data($data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            $merchant=MerchantUser::field('merchant_id')->where('id',$this->user_id)->find();
            //显示当前门店下所有会员
            $data=MerchantMember::field('id,member_head,member_phone,member_name,money')
                ->where('merchant_id',$merchant['merchant_id'])
//                ->whereTime('register_time','>','yesterday')
                ->select();
            check_data($data);
        }

    }

    /**
     * 会员搜索
     *
     * @return \think\Response
     */
    public function search()
    {
        $search=request()->param('search');
        if(!empty($this->merchant_id)){
            if(is_numeric($search)){
                $where=[
                    'member_phone'=>['like',$search.'%'],
                    'merchant_id'=>$this->merchant_id
                ];
                //电话搜索
                $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where($where)->select();
            }else{
                $where=[
                    'member_name'=>['like',$search.'%'],
                    'merchant_id'=>$this->merchant_id
                ];
                //姓名搜索
                $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where($where)->select();
            }
            check_data($data);
        }elseif(!empty($this->user_id)){
            $merchant=MerchantUser::field('merchant_id')->where('id',$this->user_id)->find();
            if(is_numeric($search)){
                $where=[
                    'member_phone'=>['like',$search.'%'],
                    'merchant_id'=>$merchant['merchant_id']
                ];
                //电话搜索
                $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where($where)->select();
            }else{
                $where=[
                    'member_name'=>['like',$search.'%'],
                    'merchant_id'=>$merchant['merchant_id']
                ];
                //姓名搜索
                $data=MerchantMember::field('id,member_head,member_phone,member_name,money')->where($where)->select();
            }
            check_data($data);
        }

    }
    /**
     * 会员详情
     *
     * @return \think\Response
     */
    public function member_detail(Request $request)
    {
        //获取会员id
        $id=$request->param('id');
        $data=MerchantMember::field('id,member_head,recharge_money,consumption_money,member_name,member_phone,money,consump_number,register_time')
            ->where('id',$id)
            ->find();
        check_data($data);
    }

    /**
     * 查看当前会员所有订单
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function member_order(Request $request)
    {
        //获取会员id
        $member_id=$request->param('id');
        $data=MemberRecharge::field('id,order_money,pay_type,status,create_time')
            ->where('member_id',$member_id)
            ->whereTime('recharge_time','>',time()-7776000)
            ->select();
//        $data['member_id']=$member_id;
        check_data($data);
    }

    /**
     * 查看会员订单详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function order_detail(Request $request)
    {
        //获取会员订单id
        $id=$request->param('id');
        $data=MemberRecharge::alias('a')
            ->field('a.*,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id')
            ->where('a.id',$id)
            ->find();
        check_data($data);
//        return_msg(200,'success',$data);
    }

    /**
     * 会员充值
     *
     * @param  int  $id 会员id
     * @return \think\Response
     */
    public function recharge(Request $request)
    {
        if($request->isPost()){
            //获取会员id
            $data=$request->post();
            check_params('member_recharge',$data,'MerchantValidate');
            //获取支付金额
           //取出所有活动
            $check=$this->get_recharge();
            $check=collection($check)->toArray();
            //按键值升序排序
            array_multisort($check);
//            halt($check);
            for($i=0;$i<count($check);$i++){
                if($i==(count($check)-1)){
                    if($check[$i]['recharge_money']<=$data['amount']){
                        //充值金额
                        $data['money']=$data['amount']+$check[$i]['give_money'];
                        //优惠金额
                        $data['discount_amount']=$check[$i]['give_money'];
                    }elseif($check[0]['recharge_money']>$data['amount']){
                        //没有优惠
                        $data['money']=$data['amount'];
                        $data['discount_amount']=0;
                    }
                }else{
                    if($check[$i]['recharge_money']<=$data['amount'] && $data['amount']<=$check[$i+1]['recharge_money']){
                        //充值金额
                        $data['money']=$data['amount']+$check[$i]['give_money'];
                        //优惠金额
                        $data['discount_amount']=$check[$i]['give_money'];
                    }elseif($check[0]['recharge_money']>$data['amount']){
                        //没有优惠
                        $data['money']=$data['amount'];
                        $data['discount_amount']=0;
                    }
                }
            }
            //修改余额
            $info=MerchantMember::field('money')->where('id',$data['id'])->find();
            $total=$data['money']+$info['money'];
            $result=MerchantMember::where('id',$data['id'])->update(['money'=>$total]);
            if($result){
                //修改充值总额
                $recharge_money=MerchantMember::field('recharge_money')->where('id',$data['id'])->find();
                $recharge_total=$recharge_money['recharge_money']+$data['money'];
                MerchantMember::where('id',$data['id'])->update(['recharge_money'=>$recharge_total]);
                //查询商户id
                $merchant=MerchantMember::field('merchant_id')->where('id',$data['id'])->find();
                //查询门店id
                $shop=MerchantMember::field('shop_id')->where('id',$data['id'])->find();
                //加入充值表
                $arr=[
                    'status'=>1,
                    'member_id'=>$data['id'],
                    'order_money'=>$data['money'],
                    'amount'=>$data['amount'],
                    'recharge_time'=>time(),
                    'pay_type'=>'PAY',
                    'order_no'=>generate_order_no($data['id']),
                    'merchant_id'=>$merchant['merchant_id'],
                    'discount_amount'=>$data['discount_amount'],
                    'shop_id'=>$shop['shop_id'],
                    'create_time'=>time()
                ];
                MemberRecharge::create($arr,true);
                return_msg(200,'充值成功');
            }else{
                return_msg(400,'充值失败');
            }
        }else{
            $data=$this->get_recharge();
            check_data($data);
        }
    }

    public function get_recharge()
    {
        //取出所有会员充值送活动
        //取出永久充值送活动
        if(!empty($this->merchant_id)){
            //取出未过期充值送活动
            $data=ShopActiveRecharge::field('recharge_money,give_money')->where(['merchant_id'=>$this->merchant_id])->whereTime('end_time','>',time())->select();
//                halt($data);
            return $data;
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            $info=MerchantUser::field('merchant_id')->where('id',$this->user_id)->find();
            //取出未过期充值送活动
            $data=ShopActiveRecharge::field('recharge_money,give_money')->where(['merchant_id'=>$info['merchant_id']])->whereTime('end_time','>',time())->select();
            return $data;
        }
    }
    /**
     * 扫码收款
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
//    public function scan_code(Request $request)
//    {
//        if(!empty($this->merchant_id)){
//            //获取会员id
//            $data=$request->post();
//            //获取门店id
//            $shop=MerchantShop::field('id')->where('merchant_id',$this->merchant_id)->find();
//            //发送给新大陆
//            $result = curl_request($this->url, true, $data, true);
//            $result=json_decode($result,true);
//
//            if($result['Result']=="S"){
//                $arr=[
//                    'LogNo'=>$result['LogNo'],
//                    'order_no'=>$result['orderNo'],
//                    'amount'=>$result['Amount']/100,
//                    'order_money'=>$result['total_amount']/100,
//                    'member_id'=>$data['id'],
//                    'prove_number'=>$data['authCode'],
//                    'pay_type'=>$data['payChannel'],
//                    'status'=>1,
//                    'create_time'=>time(),
//                    'recharge_time'=>time(),
//                    'merchant_id'=>$this->merchant_id,
//                    'shop_id'=>$shop['id']
//                ];
//                $res=MemberRecharge::insert($arr);
//                if($res){
//                    //修改充值总额和余额
//                    $data['money']=floatval($result['Amount']/100);
//                    $info=MerchantMember::field('money')->where('id',$data['id'])->find();
//                    $total=$data['money']+$info['money'];
//                    MerchantMember::where('id',$data['id'])->update(['money'=>$total]);
//                        //修改充值总额
//                        $recharge_money=MerchantMember::field('recharge_money')->where('id',$data['id'])->find();
//                        $recharge_total=$recharge_money['recharge_money']+$data['money'];
//                        MerchantMember::where('id',$data['id'])->update(['recharge_money'=>$recharge_total]);
//                    return_msg(200,'交易成功');
//                }else{
//                    return_msg(200,'交易失败');
//                }
//            }else{
//                return_msg(400,'交易失败');
//            }
//        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
//            //获取会员id
//            $data=$request->post();
//            //获取商户id和门店id
//            $merchant=MerchantMember::field('merchant_id,shop_id')->where('id',$data['id'])->find();
//
//            //发送给新大陆
//            $result = curl_request($this->url, true, $data, true);
//            if($result['Result']=="S"){
//                $arr=[
//                    'LogNo'=>$result['LogNo'],
//                    'order_no'=>$result['orderNo'],
//                    'amount'=>$result['Amount']/100,
//                    'order_money'=>$result['total_amount']/100,
//                    'member_id'=>$data['id'],
//                    'prove_number'=>$data['authCode'],
//                    'pay_type'=>$data['payChannel'],
//                    'status'=>1,
//                    'create_time'=>time(),
//                    'recharge_time'=>time(),
//                    'merchant_id'=>$merchant['merchant_id'],
//                    'shop_id'=>$merchant['shop_id']
//                ];
//                $res=MemberRecharge::insert($arr);
//                if($res){
//                    //修改充值总额和余额
//                    $data['money']=floatval($result['Amount']/100);
//                    $info=MerchantMember::field('money')->where('id',$data['id'])->find();
//                    $total=$data['money']+$info['money'];
//                    MerchantMember::where('id',$data['id'])->update(['money'=>$total]);
//                    //修改充值总额
//                    $recharge_money=MerchantMember::field('recharge_money')->where('id',$data['id'])->find();
//                    $recharge_total=$recharge_money['recharge_money']+$data['money'];
//                    MerchantMember::where('id',$data['id'])->update(['recharge_money'=>$recharge_total]);
//                    return_msg(200,'交易成功');
//                }else{
//                    return_msg(200,'交易失败');
//                }
//            }else{
//                return_msg(400,'交易失败');
//            }
//        }
//    }

    /**
     * 银联收款
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
    public function union_pay()
    {
        
    }
    /**
     * 会员数据
     *
     * @param  status 支付状态 0未支付 1已支付 2已关闭 3会员充值
     * @param  int  $id
     * @return \think\Response
     */
    public function member_data(Request $request)
    {
        //获取商户id
        //今日充值并为会员
        if(!empty($this->merchant_id)) {
            $data[]['recharge'] = MemberRecharge::whereTime('recharge_time', 'today')
                ->where( 'merchant_id' , $this->merchant_id)
                ->sum('amount');
            //今日赠送
            $data[]['give'] = MemberRecharge::whereTime('recharge_time', 'today')
                ->where(['merchant_id' => $this->merchant_id])
                ->sum('discount_amount');
            //今日消费
            $data[]['consume'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 1, 'merchant_id' => $this->merchant_id, 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日新增
            $data[]['new_count'] = MerchantMember::whereTime('register_time', 'today')
                ->where('merchant_id', $this->merchant_id)
                ->count();
            check_data($data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //获取门店id
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();
            $data[]['recharge'] = MemberRecharge::whereTime('recharge_time', 'today')
                ->where( 'shop_id' , $info['shop_id'])
                ->sum('amount');
            //今日赠送
            $data[]['give'] = MemberRecharge::whereTime('recharge_time', 'today')
                ->where('shop_id' , $info['shop_id'])
                ->sum('discount_amount');
            //今日消费
            $data[]['consume'] = Order::whereTime('pay_time', 'today')
                ->where(['status' => 1, 'shop_id' => $info['shop_id'], 'member_id' => ['>', 0]])
                ->sum('received_money');
            //今日新增
            $data[]['new_count'] = MerchantMember::whereTime('register_time', 'today')
                ->where('shop_id', $info['shop_id'])
                ->count();
            check_data($data);
        }
    }

    /**
     * 退款
     *
     * @param  int  $id
     * @return \think\Response
     */

    /**
     * 设置会员卡
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function set_member_cart(Request $request)
    {
        $data=$request->post();
        $data['merchant_id']=$this->merchant_id;
        check_params('card',$data,'MerchantValidate');
        MerchantMemberCard::where('merchant_id',$this->merchant_id)->delete();
        $result=MerchantMemberCard::insertGetId($data,true);
        if($result){
            return_msg(200,'设置成功');
        }else{
            return_msg(400,'设置失败');
        }
    }

    /**
     *我的会员
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_list()
    {
        //取出商户下所有会员
        $row=MerchantMember::where('merchant_id',$this->merchant_id)->count();
        $pages=page($row);
        $data['list']=MerchantMember::field("id,member_name,money,member_phone, register_time")
            ->where('merchant_id',$this->merchant_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        //取出今日数据
        $data['new_member']=MerchantMember::where('merchant_id',$this->merchant_id)
            ->whereTime('register_time','today')
            ->count();
        $data['member_recharge']=MemberRecharge::where('merchant_id',$this->merchant_id)
            ->whereTime('recharge_time','today')
            ->count();
        $data['recharge_total']=MemberRecharge::where('merchant_id',$this->merchant_id)
            ->whereTime('recharge_time','today')
            ->sum('order_money');
        $data['member_total']=$row;
        $data['page']=$pages;
        check_data($data);
    }

    /**
     *会员搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_search(Request $request)
    {
        $search=$request->param('search');
        if(is_numeric($search)){
            $where=[
                'member_phone'=>['like',$search.'%'],
                'merchant_id'=>$this->merchant_id
            ];
            $row=MerchantMember::where($where)->count();
            $pages=page($row);
            //电话搜索
            $data['list'] = MerchantMember::field('id,member_name,money,member_phone,register_time')
                ->where($where)
                ->limit($pages['offset'],$pages['limit'])
                ->select();
            $data['page']=$pages;
        }else{
            $where = [
                'member_name'=>['like',$search.'%'],
                'merchant_id'=>$this->merchant_id
            ];
            $row = MerchantMember::where($where)->count();
            $pages = page($row);
            //姓名搜索
            $data['list'] = MerchantMember::field('id,member_name,money,member_phone,register_time')->where($where)->limit($pages['offset'],$pages['limit'])->select();

            $data['page'] = $pages;
        }
        check_data($data);
    }

    /**
     *会员详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_detail(Request $request)
    {
        //获取会员id
        $member_id = $request->param('id');
        $row=MemberRecharge::where('member_id',$member_id)
            ->count();
        $pages=page($row);
        $data['list'] = MemberRecharge::field('order_no,recharge_time,pay_type,order_money,discount_amount,amount')
            ->where('member_id',$member_id)
            ->limit($pages['offset'],$pages['limit'])
            ->order('recharge_time desc')
            ->select();
        $data['page']=$pages;
        check_data($data);
    }

    /**
     *会员详情搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_detail_search(Request $request)
    {
        //获取会员id
        $member_id=$request->param('id');
        $query['start_time']=$request->param('start_time') ? $request->param('start_time') : '';
        if(!empty($query['start_time'])) {
            $query['end_time'] = $request->param('end_time') ? $request->param('end_time') : '';
            if (empty($query['end_time'])) {
                return_msg(400, '请选择结束时间');
            }
            if (time() - $query['start_time'] > 5604000) {
                return_msg(400, '您选择的时间大于两个月，请重新选择！');
            }
            $time=[$query['start_time'],$query['end_time']];
            $row = MemberRecharge::where('member_id',$member_id)
                ->whereTime('recharge_time','between',$time)
                ->count();
            $pages=page($row);

            $data['list'] = MemberRecharge::field('order_no,recharge_time,pay_type,order_money,discount_amount,amount')
                ->where('member_id',$member_id)
                ->whereTime('recharge_time','between',$time)
                ->limit($pages['offset'],$pages['limit'])
                ->order('recharge_time desc')
                ->select();
            $data['page'] = $pages;
            check_data($data);
        }
    }

    /**
     *会员充值记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_recharge()
    {
        //统计数据
        $member=new MemberRecharge();
        //实际充值
        $data['amount']=$member->where('merchant_id',$this->merchant_id)->whereTime('recharge_time','today')->sum('amount');
        //赠送金额
        $data['give_money']=$member->where('merchant_id',$this->merchant_id)->whereTime('recharge_time','today')->sum('discount_amount');
        //现金收款
        $data['cash']=$member->where(['merchant_id'=>$this->merchant_id,'pay_type'=>'PAY'])->whereTime('recharge_time','today')->sum('order_money');
        //银联
        $data['union']=$member->where(['merchant_id'=>$this->merchant_id,'pay_type'=>'YLPAY'])->whereTime('recharge_time','today')->sum('order_money');
        //线上
        $data['online']=$member->where(['merchant_id'=>$this->merchant_id,'pay_type'=>'WXPAY'])->whereOr('pay_type','ALIPAY')->whereTime('recharge_time','today')->sum('order_money');

        $row=$member->alias('a')
            ->join('cloud_merchant_member b','a.member_id=b.id','left')
            ->where('a.merchant_id',$this->merchant_id)
            ->count();
        $pages=page($row);
        $data['data'] = $member->alias('a')
            ->field('a.recharge_time,a.LogNo,a.order_no,a.status,a.order_money,a.discount_amount,a.pay_type,b.member_phone')
            ->join('cloud_merchant_member b','a.member_id=b.id','left')
            ->where('a.merchant_id',$this->merchant_id)
            ->limit($pages['offset'],$pages['limit'])
            ->order('a.recharge_time desc')
            ->select();
        $data['page']=$pages;
        check_data($data);
    }

    /**
     *会员充值记录搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_recharge_search(Request $request){
        $query['start_time']=$request->param('start_time') ? $request->param('start_time') : '';
        if(!empty($query['start_time'])) {
            $query['end_time'] = $request->param('end_time') ? $request->param('end_time') : '';
            if (empty($query['end_time'])) {
                return_msg(400, '请选择结束时间');
            }
            if (time() - $query['start_time'] > 5604000) {
                return_msg(400, '您选择的时间大于两个月，请重新选择！');
            }
            $time=[$query['start_time'],$query['end_time']];

           $member=new MemberRecharge();
            $row=$member->alias('a')
                ->join('cloud_merchant_member b','a.member_id=b.id','left')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.recharge_time','between',$time)
                ->count();
            $pages=page($row);
            $data['data'] = $member->alias('a')
                ->field('a.recharge_time,a.LogNo,a.order_no,a.status,a.order_money,a.discount_amount,a.pay_type,b.member_phone')
                ->join('cloud_merchant_member b','a.member_id=b.id','left')
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.recharge_time','between',$time)
                ->limit($pages['offset'],$pages['limit'])
                ->order('a.recharge_time desc')
                ->select();
            $data['page']=$pages;
            check_data($data);
        }
    }

    /**
     *会员活动列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_member_active_list()
    {
        $data['discount']=ShopActiveDiscount::alias('a')
            ->field('a.id,a.discount,a.active_time,a.start_time,a.end_time,a.apply_name,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.merchant_id',$this->merchant_id)
            ->select();
        //会员专享??
        $data['exclusive']=ShopActiveExclusive::field('id,name,start_time,end_time,consump_number,last_consump,recharge_total,consump_total,register_status')
            ->where('merchant_id',$this->merchant_id)
            ->select();
        //充值送
//        halt($data);
        $data['recharge']=ShopActiveRecharge::alias('a')
            ->field('a.id,a.name,a.recharge_money,a.give_money,a.active_time,a.start_time,a.end_time,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.merchant_id',$this->merchant_id)
            ->select();
        //分享
        $data['share']=ShopActiveShare::alias('a')
            ->field('a.id,a.start_time,a.end_time,b.shop_name')
            ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
            ->where('a.merchant_id',$this->merchant_id)
            ->select();
        check_data($data);
    }


    public function wx_member_card()
    {
        //获取用户appid
        $appid=1;
        //取出会员信息
        $data['list']=MerchantMember::field('merchant_id,money')->where('appid',$appid)->select();
        //取出会员活动
        foreach($data['list'] as &$v){
            $v['recharge']=ShopActiveRecharge::field('recharge_money,give_money')->where('merchant_id',$v['merchant_id'])->select();
            $v['discount']=ShopActiveDiscount::field('discount')->where('merchant_id',$v['merchant_id'])->select();
        }
    }
}
