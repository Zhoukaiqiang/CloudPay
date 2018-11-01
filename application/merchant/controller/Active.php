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
use Endroid\QrCode\QrCode;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Active extends Common
{
    public $appid;
    public $appsecret;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->appid="wx1aeeaac161a210df";
        $this->appsecret="0b1ddf2e988b7d05e4e775cc8bdfc831";
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
            $data['recharge_money']=explode(',',$data['recharge_money']);
            $data['give_money']=explode(',',$data['give_money']);

            foreach($data['recharge_money'] as $k=>$v){
                foreach($data['give_money'] as $k1=>$s){
                    if($k==$k1){
                        if($data['active_time'] == 0){
                            //永久
                            check_params('recharge',$data,'MerchantValidate');
                            $arr=[
                                'active_time'=>0,
                                'recharge_money'=>$v,
                                'give_money'=>$s,
                                'merchant_id'=>$this->merchant_id,
                                'status'=>1,
                                'create_time'=>time(),
                                'start_time'=>time(),
                                'end_time'=>strtotime('2038-01-19 03:14:07')
                            ];
                        }elseif($data['active_time'] == 1){
                            check_params('new_recharge',$data,'MerchantValidate');
                            $arr=[
                                'active_time'=>1,
                                'recharge_money'=>$v,
                                'give_money'=>$s,
                                'merchant_id'=>$this->merchant_id,
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
                }
            }
            if(in_array(400,$res)){
                return_msg(400,'操作失败');
            }else{
                return_msg(200,'操作成功');
            }
            /*$recharge_money=implode(',',$data['recharge_money']);
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
            }*/
        }else{
            //查询商户是否有活动
            $info = ShopActiveRecharge::where(['merchant_id'=>$this->merchant_id,'status'=>1,'end_time'=>['>',time()]])->find();
            if($info){
                return_msg(400,'请先关闭活动');
            }
        }
        /*else{
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
        }*/
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
            $data['shop_id']=explode(',',$data['shop_id']);
            if(is_array($data['shop_id'])){
                if($data['active_time']==0){
                    $data['start_time']=time();
                    $data['end_time']=strtotime('2038-01-19 03:14:07');
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
                    $info=MerchantShop::field('merchant_id')->where('id',$v)->find();
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
            if(!empty($this->merchant_id )){

                //取出所有门店
                $data=MerchantShop::field('id,shop_name')->where('merchant_id',$this->merchant_id)->select();
                //查询门店是否有活动
                foreach($data as $k=>$v){
                    $res=ShopActiveDiscount::where(['shop_id'=>$v['id'],'status'=>1,'end_time'=>['>',time()]])->find();
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
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->select();
                $res=ShopActiveDiscount::where(['shop_id'=>$data[0]['id'],'status'=>1,'end_time'=>['>',time()]])->find();
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
        if($request->isGet()){

            $info=ShopActiveExclusive::field('status')->where(['merchant_id'=>$this->merchant_id,'status'=>1,'end_time'=>['>',time()]])->find();
            if(!empty($info)){
                return_msg(400,'请先关闭活动');
            }
        }else{
            $data=$request->post();
            $data['status']=1;
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
                                'status'=>1,
                                'order_number'=>generate_order_no(),
                                'merchant_id'=>$this->merchant_id
                            ];
                            MemberExclusive::insert($arr);
                        }
                    }
                }elseif($data['last_consump'] !=-1){
                    $info=MerchantMember::field('id,consumption_time')->select();
                    foreach($info as $v){
                        //比较时间戳大小
                        if($v['consumption_time'] >= time()-$data['last_consump']){
                            //派卷
                            $arr=[
                                'SN'=>getSN(),
                                'member_id'=>$v['id'],
                                'exclusive_id'=>$insertid,
                                'status'=>1,
                                'order_number'=>generate_order_no(),
                                'merchant_id'=>$this->merchant_id
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
                                'status'=>1,
                                'order_number'=>generate_order_no(),
                                'merchant_id'=>$this->merchant_id
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
                                'status'=>1,
                                'order_number'=>generate_order_no(),
                                'merchant_id'=>$this->merchant_id
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
            $data['shop_id']=explode(',',$data['shop_id']);
            //验证
            check_params('share',$data,'MerchantValidate');
            if(is_array($data['shop_id'])){
                foreach($data['shop_id'] as $k=>$v){
                    $data['shop_id'] = $v;
                    $data['status'] = 1;
                    $data['create_time'] = time();
                    //获取商户id
                    $info=MerchantShop::field('merchant_id')->where('id',$v)->find();
                    $data['merchant_id']=$info['merchant_id'];

                    $result = ShopActiveShare::insert($data,true);
                    if($result){
                        $res[] = 200;
                    }else{
                        $res[] = 400;
                    }
                    //发送到公众号中
                    $this->send_msg($v);
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
                    $res=ShopActiveShare::where(['shop_id'=>$v['id'],'status'=>1,'end_time'=>['>',time()]])->find();
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
                $data=MerchantShop::field('id,shop_name')->where('id',$info['shop_id'])->select();

                $res=ShopActiveShare::where(['shop_id'=>$data[0]['id'],'status'=>1,'end_time'=>['>',time()]])->find();
                if($res){
                    return_msg(400,'请先关闭活动');
                }else{
                    return_msg(200,'success',$data);
                }
            }
        }
    }

    /**
     * 发起请求
     */
    public function https_request($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     *获取access_token
     * @return mixed
     */
    public function get_access()
    {
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
        $res=$this->https_request($url);
        $res=json_decode($res,true);
        return $res['access_token'];
    }

    /**
     *发送模板消息
     */
    public function send_msg($shop_id)
    {
        //获取所有会员openid
        $openid=MerchantMember::field('openid')->where('shop_id',$shop_id)->select();

        $access_token=$this->get_access();

        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
      foreach($openid as $v){
          $arr=array(
              "touser"=>$v['openid'],
              "template_id"=>"Jo5aZi-6uWfA7Vl_gTGqsaABqeIfuJIzrXBRe3cDOIg",
              "url"=>"www.baidu.com",
              "data"=>array(
                  'first'=>array('value'=>'点击获取更多优惠'),
                  'keyword1'=>array('value'=>date("Y-m-d H:i:s"),'color'=>'#173177'),
              ),
          );
//        $postjson=json_encode($arr);
          $res=curl_request($url,true,$arr,true);
          halt($res);
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
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.active_time'=>0,'a.merchant_id'=>$this->merchant_id,'a.status'=>1])
//                ->order('a.create_time desc')
                ->select();
            //选择时间

            $data['recharge'][] = ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.active_time'=>1,'a.merchant_id'=>$this->merchant_id])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //折扣活动 取出商户下所有门店活动
            //取出永久有效活动

            //选择时间
            $data['discount']=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.merchant_id'=>$this->merchant_id,'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();

            //会员专享
            $data['exclusive'][]=ShopActiveExclusive::where(['merchant_id'=>$this->merchant_id,'status'=>1])->whereTime('end_time','>',time())->order('create_time desc')->select();

            //分享红包
            $data['share'][] = ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.merchant_id'=>$this->merchant_id,'status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();

            check_data($data);
        }elseif(empty($this->merchant_id) && !empty($this->user_id)){
            //取出门店下所有活动
            $info=MerchantUser::field('shop_id')->where('id',$this->user_id)->find();

            //选择时间
            $data['discount']=ShopActiveDiscount::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //分享
            $data['share'][]=ShopActiveShare::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            //充值
            //取出永久有效活动
            $data['recharge'][] = ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1,'a.active_time'=>0])
                ->order('a.create_time desc')
                ->select();
            //选择时间
            $data['recharge'][]=ShopActiveRecharge::alias('a')
                ->field('a.*,b.shop_name')
                ->join('cloud_merchant_shop b','a.shop_id=b.id','left')
                ->where(['a.shop_id'=>$info['shop_id'],'a.status'=>1,'a.active_time'=>1])
                ->whereTime('a.end_time','>',time())
                ->order('a.create_time desc')
                ->select();
            check_data($data);
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
        $result=ShopActiveRecharge::where('merchant_id',$this->merchant_id)->delete();
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
        if(!empty($this->merchant_id)){
            $result=ShopActiveDiscount::where('merchant_id',$this->merchant_id)->delete();
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }elseif(!empty($this->user_id)){
            //获取门店id
            $shop_id=$request->param('shop_id');
            $result=ShopActiveDiscount::where('shop_id',$shop_id)->delete();
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
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
        if(!empty($this->merchant_id)){
            $result=ShopActiveShare::where('merchant_id',$this->merchant_id)->delete();
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }elseif(!empty($this->user_id)){
            //获取门店id
            $shop_id = $request->post('shop_id');
            $result=ShopActiveShare::where('shop_id',$shop_id)->delete();
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');

            }
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
        $result=ShopActiveExclusive::where('merchant_id',$this->merchant_id)->delete();
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

    /**
     *pc端分享活动详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_active_detail()
    {
        $data=ShopActiveShare::field('money,lowest_consump,use_number')->where('merchant_id',$this->merchant_id)->find();
        check_data($data);
    }

    /**
     *核销记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cancel_record()
    {
        $where=[
            ['cloud_merchant_member b','a.member_id=b.id','left'],
            ['cloud_merchant_user c','a.user_id=c.id','left'],
            ['cloud_shop_active_exclusive d','a.exclusive_id=d.id','left']
        ];
        $row=MemberExclusive::alias('a')
            ->join($where)
            ->where(['a.merchant_id'=>$this->merchant_id,'a.status'=>0])
            ->count();

        $pages=page($row);

        $data['list']=MemberExclusive::alias('a')
            ->field('a.id,a.cancel_time,a.order_number,a.status,b.member_phone,c.name user_name,d.name active_name,d.coupons_money,d.order_money,d.consump_number,d.last_consump,d.recharge_total,d.consump_total,d.register_status')
            ->join($where)
            ->where(['a.merchant_id'=>$this->merchant_id,'a.status'=>0])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['page']=$pages;
        check_data($data);
    }

    /**
     *发放记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_issue_record()
    {
        $row=ShopActiveExclusive::
            where('merchant_id',$this->merchant_id)
            ->count();

        $pages=page($row);

        $data['list']=ShopActiveExclusive::field('create_time,coupons_title,coupons_money,order_money,consump_number,last_consump,recharge_total,consump_total,register_status')
            ->where('merchant_id',$this->merchant_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['page']=$pages;
        check_data($data);
    }

    /**
     *核销记录搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_cancel_record_search(Request $request)
    {
        $query['start_time'] = $request->param('start_time') ? $request->param('start_time') : '';
        if(!empty($query['start_time'])) {
            $query['end_time'] = $request->param('end_time') ? $request->param('end_time') : '';
            if (empty($query['end_time'])) {
                return_msg(400, '请选择结束时间');
            }
            if (time() - $query['start_time'] > 5604000) {
                return_msg(400, '您选择的时间大于两个月，请重新选择！');
            }
            $time = [$query['start_time'], $query['end_time']];

            $where=[
                ['cloud_merchant_member b','a.member_id=b.id'],
                ['cloud_merchant_user c','a.user_id=c.id'],
                ['cloud_shop_active_exclusive d','a.exclusive_id=d.id']
            ];
            $row=MemberExclusive::alias('a')
                ->join($where)
                ->where('a.merchant_id',$this->merchant_id)
                ->whereTime('a.cancel_time','between',$time)
                ->count();
            $pages=page($row);
            $data['list']=MemberExclusive::alias('a')
                ->field('a.id,a.cancel_time,a.order_number,a.status,b.member_phone,c.name user_name,d.name active_name,d.coupons_money,d.order_money')
                ->join($where)
                ->where(['a.merchant_id'=>$this->merchant_id,'a.status'=>0])
                ->whereTime('a.cancel_time','between',$time)
                ->limit($pages['offset'],$pages['limit'])
                ->select();
            $data['page']=$pages;

            check_data($data);
        }
    }

    /**
     *发放记录搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pc_issue_record_search(Request $request)
    {
        $query['start_time']=$request->param('start_time') ? $request->param('start_time') : '';
        if(!empty($query['start_time'])) {
            $query['end_time'] = $request->param('end_time') ? $request->param('end_time') : '';
            if (empty($query['end_time'])) {
                return_msg(400, '请选择结束时间');
            }
            if (time() - $query['start_time'] > 5604000) {
                return_msg(400, '您选择的时间大于两个月，请重新选择！');
            }
            $time = [$query['start_time'], $query['end_time']];

            $row=ShopActiveExclusive::where('merchant_id',$this->merchant_id)
                ->whereTime('create_time','between',$time)
                ->count();
            $pages=page($row);
            $data['list']=ShopActiveExclusive::field('create_time,coupons_title,coupons_money,order_money')
                ->where('merchant_id',$this->merchant_id)
                ->whereTime('create_time','between',$time)
                ->limit($pages['offset'],$pages['limit'])
                ->select();
            $data['page'] = $pages;
            check_data($data);
        }
    }


}
