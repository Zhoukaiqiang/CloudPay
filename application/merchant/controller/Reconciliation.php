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
use think\Controller;
use think\Db;
use think\Request;

class Reconciliation extends Controller
{

    /**对账首页
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //判断是否是商户还是门店
        if (session('merchant_id')) {
            $merchant_id = session('merchant_id');
            $merchant_id=1;
            //取出所有商户所有门店以及员工
            $shop=Db::table('cloud_merchant_shop')->alias('a')
                ->field(['a.shop_name','a.id as shopid','b.id as userid','b.people_name'])
                ->join('cloud_merchant_user b','a.id=b.shop_id')
                ->where('a.merchant_id',$merchant_id)
                ->select();
            //取出第一家店的id
            $shop_id=$shop[0]['shopid'];
            //取第一家的数据
            $data = Db::query("select pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money from cloud_order where shop_id=$shop_id group By pay_type");

            $success=$this->tuensfis($shop,$data);

            return $success;

        } else{
            $shop_id=session('shop_id');
            $shop_id=2;
            //取出门店以及员工
            $shop=Db::table('cloud_merchant_shop')->alias('a')
                ->field(['a.shop_name','a.id as shopid','b.id as userid','b.people_name'])
                ->join('cloud_merchant_user b','a.id=b.shop_id')
                ->where('a.id',$shop_id)
                ->select();
            //取第一家的数据
            $data = Db::query("select pay_type,count(id) as count,sum(received_money) as received_money,sum(discount) as discount,sum(order_money) as order_money,sum(refund_money) as refund_money from cloud_order where shop_id=$shop_id group By pay_type");

            $success=$this->tuensfis($shop,$data);
            return $success;
        }
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
        $array=['alipay','etc','wxpay'];
        //取出数组的差异集
        $arr=array_keys(array_diff($array,$pay_type));
        if($arr){
            $data[]=['pay_type'=>$array[$arr[0]],'count'=>0,'received_money'=>0,'discount'=>0,'order_money'=>0,'refund_money'=>0];
        }
        //实收总金额
        $proceeds=0;
        //优惠总金额
        $discounts=0;
        //退款总金额
        $refund=0;
        //订单笔数
        $ordercount=0;
        foreach ($data as $k=>$v){
            $proceeds+=$v['received_money'];
            $discounts+=$v['discount'];
            $refund+=$v['refund_money'];
            $ordercount+=$v['count'];
        }
        $money['proceeds']=$proceeds;
        $money['discounts']=$discounts;
        $money['refund']=$refund;
        $money['ordercount']=$ordercount;
       return json_encode(['shop'=>$shop,'data'=>$data,'money'=>$money]);
    }

    /**
     * 日周月账单首页
     * @param Request $request
     * @return string
     */
    public function oneday(Request $request)
    {
        //门店id
        $shop_id=$request->param('shop_id');
        //获取今天结束时间
        $time=mktime(23, 59, 59, date('m'), date('d'), date('Y'));

        if($request->param('pid')==1){
            //获取今天开始时间
            $endtime=mktime(0,0,0,date('m'),date('d'),date('Y'));

        }else if($request->param('pid')==2) {
            //获取一周前的时间戳
            $endtime=mktime(23, 59, 59, date('m'), date('d')-7, date('Y'));

        }else if($request->param('pid')==3){
            //获取一月前的时间戳
            $endtime=mktime(23, 59, 59, date('m')-1, date('d'), date('Y'));


        }
        return  $this->oneday_bill($endtime,$time,$shop_id);

    }

    /**
     * 日周月账单查询
     * @param $createtime
     * @param $endtime
     * @param $shop_id
     * @return string
     */
    public function oneday_bill($createtime,$endtime,$shop_id)
    {
        $data=Db::query("select FROM_UNIXTIME(pay_time,'%Y%m%d') days,FROM_UNIXTIME(pay_time,'%H%i%s') minutes,pay_time,received_money,status,id from cloud_order where shop_id=$shop_id and status=1 and pay_time between $createtime and $endtime order by pay_time desc");
        $arr=[];

        foreach ($data as $k=>&$v){
                $v['status']='已付款';
                $v['minutes']=substr_replace($v['minutes'],':',2,0);
                $v['minutes']=substr_replace($v['minutes'],':',5,0);
            for ($i=0;$i<count($data);$i++){
                if ($v['days']==$data[$i]['days']){
                    if($v['id']==$data[$i]['id']){
                        $arr[$v['days']][]=$v;
                    }
                    }
            }
            }

            //计算实收金额和订单笔数
        $money=Db::query("select count(id),sum(received_money) from cloud_order where shop_id=$shop_id and status=1 and pay_time between $createtime and $endtime order by pay_time desc");
        $array=['arr'=>$arr,'money'=>$money];
        return json_encode($array);
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
        //判断是否是全部门店
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