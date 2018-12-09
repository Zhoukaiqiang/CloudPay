<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/26
 * Time: 14:22
 */

namespace app\merchant\controller;


use app\agent\model\Order;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use think\Controller;
use think\Db;
use think\Request;

class Reconciliation extends Commonality
{

    /**
     * Notes:对账首页
     * User: guoyang
     * DATE: 2018/10/26
     * @param Request $request
     * 商户端 不传值   店长端 传门店ID    服务员 不传值
     */
    public function index(Request $request)
    {
        /** role 1服务员 2店长 3收银员 -1商户 */
        $id=$this->id;
        $role=$this->role;
        $name=$this->name;

        /**如果是店长和商户就取门店以及员工数据*/
        if($role!=1 && $role!=3){
            /**如果是店长取出门店ID*/
            if($role==2){
                $id=$request->param('shop_id');
                $name='id';
            }
            /**取出商户所有门店以及员工*/
            /*$shop = Db::table('cloud_merchant_shop')->alias('a')
                ->field(['a.shop_name', 'a.id as shopid', 'b.id as userid', 'b.name'])
                ->join('cloud_merchant_user b', 'a.id=b.shop_id','left')
                ->where('a.'.$name,$id)
                ->order('a.id')
                ->select();*/
            $shop['shop'] = Db::table('cloud_merchant_shop')->field(['shop_name','id as shopid'])
                ->where($name,$id)
                ->select();
            $shop['user'] = Db::table('cloud_merchant_user')->field('id as userid,name')->where($name,$id)->select();

            //halt($shop);
            if(!$shop){
                return_msg(400,'error',$shop);
            }else{
                $id=$shop['shop'][0]['shopid'];
            }


            /** 门店ID */
            $name='shop_id';
        }
//        return_msg($id,$name);
        /** 取出门店对账数据 */
        $create_time=$request->param('start_time') ? strtotime($request->param('start_time')) : mktime(0,0,0,date('m'),date('d'),date('Y'));

        $end_time=$request->param('end_time') ? $request->param('end_time') : mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $data=Order::field('pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money')
            ->whereTime('pay_time','between',[$create_time,$end_time])
            ->group('pay_type')
            ->where(['merchant_id'=>$this->id,'status'=>1])
            ->order('pay_time','desc')
            ->select();
//            halt($data);
        $merber=Db::table('cloud_member_recharge');

//            $data = Db::query("select  from cloud_order where $name=$id and FROM_UNIXTIME('pay_time','%Y-%m-%d') = curdate() group By pay_type");
        if (!$data){
            return_msg(400,'error','没有记录');
        }
        if($role==1 || $role==3){
            $shop=[];
        }
        $success = $this->tuensfis($shop, $data);

        return_msg(200,'success',$success) ;
    }

    /**
     * 对账首页查询
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index_query(Request $request)
    {

        /** shop_id 不传值默认0 全部门店传值-1*/
        $shop_id=$request->param('shop_id') ? $request->param('shop_id') : 0;
//        echo $shop_id;die;
        /** 全部员工不传值默认0 */
        $userid=$request->param('user_id') ? $request->param('user_id') : 0;
        $create_time=$request->param('start_time') ? strtotime($request->param('start_time')) : mktime(0,0,0,date('m'),date('d'),date('Y'));

        $end_time=$request->param('end_time') ? $request->param('end_time') : mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

        /** 筛选字段   门店商户筛选*/
        $name= $shop_id==-1 ? 'merchant_id' : 'shop_id';
        $shopsymo= $shop_id ? '=' : '<>';
        $id= $shop_id==-1 ? $this->id :$shop_id ;

        /** 员工筛选   /role 1服务员 2店长 3收银员 -1商户 */
        $username='user_id';
        if($userid!=0){
            $role=MerchantUser::where('id',$userid)->field('role')->find();
            $arr=[1=>'user',2=>'shop_id',3=>'person_info_id'];
            $username= $arr[$role->role];
        }
        /** 员工筛选符号 */
        $usersymo= $userid ? '=' :"<>";

        /*$data = Db::query("select pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money from cloud_order where $name{$shopsymo}$id  and pay_time between $create_time and $end_time group By pay_type");*/
//        echo $name;echo $id;die;
        /*echo $create_time."<br/>";
        echo $end_time;die;*/
        $data=Order::field('pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money')
            ->whereTime('pay_time','between',[$create_time,$end_time])
            ->group('pay_type')
            ->where([$name=>$id,'status'=>1])
            ->order('pay_time','desc')
            ->select();
//        halt($data);
        $success= $this->tuensfis($shop=[],$data);
        return_msg(200,'success',$success);


    }

    /**
     * 对账数据
     * @param $shop
     * @param $shop_id
     */
    public function tuensfis($shop,$data)
    {
        $pay_type=[];
        //取出支付类型
        foreach ($data as $k=>$v){
            $pay_type[]=$v['pay_type'];
        }
        $array=['alipay','etc','wxpay','cash'];
        //取出数组的差异集
        $arr=array_keys(array_diff($array,$pay_type));
//        return json_encode($arr);
        if($arr){
            for ($i=0;$i<count($arr);$i++){
                $data[]=['pay_type'=>$array[$arr[$i]],'count'=>0,'received_money'=>0,'discount'=>0,'order_money'=>0,'refund_money'=>0];
            }
        }

        /** proceeds 实收总金额  discounts 优惠总金额  refund 退款总金额  ordercount 订单笔数  order_money 订单金额*/
        $money=['proceeds'=>0,'discounts'=>0,'refund'=>0,'ordercount'=>0,'order_money'=>0];
        foreach ($data as $k=>$v){
            $money['proceeds']+=$v['received_money'];
            $money['discounts']+=$v['discount'];
            $money['refund']+=$v['refund_money'];
            $money['ordercount']+=$v['count'];
            $money['order_money']+=$v['order_money'];
        }
        $money['proceeds'] = round($money['proceeds'],2);
        $money['order_money'] = round($money['order_money'],2);
        return ['shop'=>$shop,'data'=>$data,'money'=>$money];
    }

    public function aaa(){

    }

    /**
     * 日周月账单首页
     * @param Request $request
     * @return string
     */
    public function oneday(Request $request)
    {
        /** 获取员工ID 或者 门店ID 或 商户*/
        $id=$request->param('shop_id') ? $request->param('shop_id') : $request->param('user_id');

        if($request->param('shop_id')){
            $id=$request->param('shop_id');
            $name= $id==-1 ? 'merchant_id' : 'shop_id';
            $id= $id==-1 ? $this->id : $id;
        }elseif($request->param('user_id')) {
            /** 员工筛选   /role 1服务员 2店长 3收银员 -1商户 */
            $role = MerchantUser::where('id', $id)->field('role')->find();
            $arr = [1 => 'user_id', 2 => 'shop_id', 3 => 'person_info_id'];
            $name = $arr[$role->role];
        }
//        return_msg($name);
        /** 时间天数 */
        $pid=$request->param('pid') ? $request->param('pid') : 0;

        //获取今天结束时间

//        $time=mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $time = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
//        echo $time;die;
//        $endtime=mktime(0, 0, 0, date('m'), date('d')-$pid, date('Y'));
        if($pid == 0){
            $endtime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        }else{

            $endtime = strtotime("-$pid day");
        }
//        echo $endtime;die;
        /*echo $time."<br/>";
        echo $endtime;die;*/
//        echo $endtime;die;
//        echo $name;die;
        return  $this->oneday_bill($endtime,$time,$id,$name);

    }

    /**
     * 数据查询
     * @param $createtime
     * @param $endtime
     * @param $shop_id
     * @return string
     */
    public function oneday_bill($createtime,$endtime,$id,$name)
    {
//        echo $name;echo $id;die;
        $data=Db::query("select FROM_UNIXTIME(pay_time,'%Y%m%d') days,FROM_UNIXTIME(pay_time,'%H%i%s') minutes,pay_time,received_money,status,id from cloud_order where $name=$id  and pay_time between $createtime and $endtime  order by pay_time desc");

        /*$data=Order::field('pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money')
            ->whereTime('pay_time','between',[$createtime,$endtime])
//            ->group('pay_type')
            ->where([$name=>$id])
            ->select();*/
//        halt($data);
        /*$success= $this->tuensfis($shop=[],$data);
        return_msg(200,'success',$success);*/
//        check_data($data);
        $arr=[];
//        var_dump($shop_id);die;
        foreach ($data as $k=>&$v){

            $v['minutes']=substr_replace($v['minutes'],':',2,0);
            $v['minutes']=substr_replace($v['minutes'],':',5,0);
            for ($i=0;$i<count($data);$i++){
                if ($v['days']==$data[$i]['days']){
                    if($v['id']==$data[$i]['id']){
                        $arr['d'.$v['days']][]=$v;
                    }
                }
            }
        }
//            halt($data);
        //计算实收金额和订单笔数
        $res=[];
        $money['count'] = count($data);
        $money['countmoney'] = 0;
        foreach($data as $v){
            if($v['status'] == 1){
                $money['countmoney'] += $v['received_money'];
            }
        }
        $money['countmoney'] = round($money['countmoney'],2);
        $res[] = $money;
        /*$money=Db::query("select count(id) as count,sum(received_money) as countmoney from cloud_order where $name=$id and status=1 and pay_time between $createtime and $endtime order by pay_time desc");*/

        $array=['arr'=>$arr,'money'=>$res];
        return json_encode($array);
    }

    /**
     * 日周月账单筛选查询 上一天下一天查询  周月
     * @param Request $request
     * @return string
     */
    public function turuend_query(Request $request)
    {
        /** 获取员工ID 或者 门店ID 或 商户*/
        $id=$request->param('shop_id') ? $request->param('shop_id') : $request->param('user_id') ;

        if($request->param('shop_id')){
            $id=$request->param('shop_id');
            $name = $id == -1 ? 'merchant_id' : 'shop_id';
            $id = $id == -1 ? $this->id : $id;
        }elseif($request->param('user_id')) {
            /** 员工筛选   /role 1服务员 2店长 3收银员 -1商户 */
            $role = MerchantUser::where('id', $id)->field('role')->find();
            $arr = [1 => 'user_id', 2 => 'shop_id', 3 => 'person_info_id'];
            $name = $arr[$role->role];
        }
        //起始时间
        $create_time=$request->param('create_time');
        //days 天数
        $days=$request->param('days');
        //up上下  1上 2下
        $up=$request->param('up');

        if($up==2){
            $create_time=strtotime($create_time)+24*3600;
            $endtime=strtotime($create_time)+$days*24*3600;
        }else if($up==1){

            $endtime=strtotime($create_time)-$days*24*3600;
            $create_time=strtotime($create_time)-1;
        }


        return  $this->oneday_bill($endtime,$create_time,$id,$name);

    }

    /**
     * 账单搜索
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tuensfisddds(Request $request)
    {
        $shops=$request->post();
        //判断是否是商户
        if($shops['shop_id']==0){
            $merchant_id=session('merchant_id');
            $merchant_id=1;
            $rows=Db::table('cloud_merchant_shop')->where('merchant_id',$merchant_id)->field(['id'])->select();

            $row=[];
            foreach ($rows as $k=>$v){
                $row[]=$v['id'];

            }

            $shops['shop_id']=$row;
        }else{
            $shops['shop_id']=[$shops['shop_id']];
        }
        $und='=';
        if($shops['user_id']==0){
            $und='<>';

        }
        //取出所有商户所有门店以及员工
        $shop=Db::table('cloud_merchant_shop')->alias('a')
            ->field(['a.shop_name','a.id as shopid','b.id as userid','b.name'])
            ->join('cloud_merchant_user b','a.id=b.shop_id')
            ->where('a.id','in', $shops['shop_id'])
            ->where('b.id',$und,$shops['user_id'])

            ->select();

        //取出第一家店的id
        $shop_id=implode(',',$shops['shop_id']);
//        dump($shop_id);
        $userid=$shops['user_id'];
        //搜索时间
        $createtime=strtotime($shops['create_time']);
        $endtime=strtotime($shops['end_time']);
        //取门店的收款金额的数据
        $data = Db::query("select pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money from cloud_order where shop_id in($shop_id) and person_info_id $und $userid and pay_time between $createtime and $endtime  group By pay_type");

        $del=$this->tuensfis($shop,$data);
        return $del;

    }
}