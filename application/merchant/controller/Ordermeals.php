<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/20
 * Time: 16:59
 */

namespace app\merchant\controller;


use app\merchant\model\MerchantShop;
use think\Controller;
use think\Request;

class Ordermeals extends Controller
{
    public function returntime()
    {

        $shop_id=\request()->param('shop_id');
        $name=\request()->param('name');
        return_msg(200,'success',['shop'=>$shop_id,'name'=>$name]);
    }

    /**
     * 扫码首页面
     * shop_id    门店id
     * name   桌位名称
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $shop_id=\request()->param('shop_id');
        $name=\request()->param('name');
        $data=MerchantShop::where('id',$shop_id)->field('id,shop_name')->find();
        $data['name']=$name;
        if($data){
            return_msg(200,'success',$data);
        }

    }
    /**
     *   点菜
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cuisine_list(Request $request)
    {
        $shop_id = $request->param('shop_id');
        if(Request::instance()->isGET()) {

            $data = MerchantDish::where(['shop_id'=>$shop_id,'status'=>1])->field(['dish_label,shop_id','dish_name','norm_id', 'dish_introduce', 'money','dish_img', 'id'])->select();
//            var_dump($data);
            $type=MerchantDishNorm::where(['shop_id'=>$shop_id])->select();

            //菜品分类

            $array=$this->array;
            //菜品推荐 菜品标签
            foreach ($data as $a=>&$v){
                $arr=ltrim($v['dish_label'],'[');
                $arr=rtrim($arr,']');
                $arr=explode(',',$arr);




                $v['dish_label']='';

                for ($i=0;$i<count($arr);$i++){

                    $v['dish_label'].=$array[$arr[$i]].',';
                }

                $v['dish_label']=rtrim($v['dish_label'],',');
                $v['dish_introduce']=$array[$v['dish_introduce']];

            }
            //菜品分类名称
            $rew=[];
            foreach ($data as $k=>&$v){
                foreach ($type as $it=>$val){

                    if($val['id']==$v['norm_id']){
                        $v['norm_id']=$val['dish_norm'];
                    }
                }
                $rew[]=$v['norm_id'];

            }
            //菜品分类去重
            $data['rew']=array_unique($rew);
//            $rew=array_filter(array_unique(explode(',',$rew)));

            if($data){
                return_msg(200,'success',$data);
            }else{
                return_msg(400,'error','此店未来得及上传菜品');
            }
        }else if($request->param('dish_name')){
            //搜索菜品
            $dish_name=$request->param('dish_name');
            $data=MerchantDish::where(['dish_name'=>['like','%'.$dish_name.'%'],'shop_id'=>$shop_id,'status'=>1])->select();
            if($data){
                return_msg(200,'success',$data);
            }else{
                return_msg(400,'error','查无此菜品');
            }
        }
    }

    public function indent_list(Request $request)
    {
        $post=$request->post();

    }
    /**
     * 去下单
     * @param Request $request
     * @return string
     */
    public function place_order(Request $request)
    {
        $data=$request->post();
        //判断是否继续点菜
        if(!$data['order_id']) {
            //istableware餐具是否收费 1收取餐具费 0不收取   paymentorder 付款顺序1先上菜后付款   2先付款后上菜
            $number = MerchantShop::where('id', $data[ 'shop_id' ])->field('istableware,tableware_money,paymentorder')->find();
            if ($number[ 'istableware' ] == 1) {
                $data[] = ['name' => '餐具', 'money' => $number[ 'tableware_money' ] * $data[ 'meal_number' ], 'deal' => $data[ 'meal_number' ]];
            }
        }
        //订单总金额
        $count_money=0;
        foreach ($data as $k=>$v){
            $count_money+=$v['money'];

        }
        $data['count_money']=$count_money;

        return_msg(200,'success',$data);
    }

    /**
     * 提交订单
     * @param Request $request
     */
    public function confirm_order(Request $request)
    {
        $data=$request->post();
        //判断是否继续点菜
        if(!$data['order_id']) {
            //订单号
            list($usec, $sec) = explode(" ", microtime());
            $times = str_replace('.', '', $usec + $sec);
            $timese = date('YmdHis', time());
            $code = $timese . $times;
            //订单入库
            $order = ['order_money' => $data[ 'order_money' ], 'received_money' => $data[ 'received_money' ], 'table_name' => $data[ 'table_name' ], 'order_remark' => $data[ 'order_remark' ], 'shop_id' => $data[ 'shop_id' ], 'merchant_id' => $data[ 'merchant_id' ], 'order_number' => $code];
            $order_id = Order::insertGetId($order, true);
            //判断付款顺序   1先上菜后付款   2先付款后上菜
        }else{
            //原有的订单号上加菜就可以了

        }
        if($order){
            return_msg(200,'success',['order_id'=>$order_id]);
        }else{
            return_msg(400,'error','提交订单失败');
        }

    }


}