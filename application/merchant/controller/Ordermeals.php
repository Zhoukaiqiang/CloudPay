<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/20
 * Time: 16:59
 */

namespace app\merchant\controller;


use app\merchant\model\MerchantDish;
use app\merchant\model\MerchantDishCart;
use app\merchant\model\MerchantDishNorm;
use app\merchant\model\MerchantShop;
use app\merchant\model\Order;
use think\Controller;
use think\Request;
use think\Session;

class Ordermeals extends Controller
{
    public function returntime()
    {
        $mid = \request()->param("merchant_id");
        $shop_id=\request()->param('shop_id');
        $name=\request()->param('name');
        Session::set("merchant_id", $mid, "_app");
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
            $number = MerchantShop::where('id', $data[ 'shop_id' ])->field('istableware,tableware_money')->find();
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

    /**
     *菜品分类
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_category(Request $request)
    {
        $shop_id=$request->param('shop_id');
//        $shop_id=1;
        if($request->isPost()){
            $norm_id=$request->param('norm_id');
            $data=MerchantDish::where(['shop_id'=>$shop_id,'norm_id'=>$norm_id])->select();
            //属性
            foreach($data as &$v){
                $arr=ltrim($v['dish_attr'],'[');
                $arr=rtrim($arr,']');
                $arr=explode(',',$arr);
                $v['dish_attr']=$arr;
            }
            check_data($data);
        }else{
            //取出所有分类
            $data=MerchantDish::alias('a')
                ->field('a.norm_id,b.dish_norm')
                ->join('cloud_merchant_dish_norm b','a.norm_id=b.id','left')
                ->where(['a.shop_id'=>$shop_id])
                ->group('a.norm_id')
                ->select();
            check_data($data);
        }
    }

    /**
     *菜品搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dish_search(Request $request)
    {
        $shop_id=$request->param('shop_id');
        $name=$request->param('name');
        $data=MerchantDish::where(['dish_name'=>['like',$name.'%'],'shop_id'=>$shop_id])->select();
        //属性
        foreach($data as &$v){
            $arr=ltrim($v['dish_attr'],'[');
            $arr=rtrim($arr,']');
            $arr=explode(',',$arr);
            $v['dish_attr']=$arr;
        }
        check_data($data);
    }

    /**
     *加入购物车
     * @param Request $request
     */
    public function dish_cart(Request $request)
    {
        $data=$request->post();

        $insert=MerchantDishCart::insert($data);
        if($insert){
            return_msg(200,'成功加入购物车');
        }else{
            return_msg(400,'加入购物车失败');
        }
    }

    /**
     *查看购物车
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function show_cart()
    {
        $info=request()->post();

        $data=MerchantDishCart::alias('a')
            ->field('a.id,a.dish_number,b.dish_name,b.money')
            ->join('cloud_merchant_dish b','a.dish_id=b.id','left')
            ->where(['a.table_number'=>$info['table_name'],'a.shop_id'=>$info['shop_id']])
            ->select();
        check_data($data);
    }

    /**
     *减
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del()
    {
        $id=request()->param('id');
        $data=MerchantDishCart::field('dish_number')->where('id',$id)->find();
        $number=$data['dish_number']-1;
        if($number<=0){
            $res=MerchantDishCart::where('id',$id)->delete();
            if($res){
                return_msg(200,'删除成功');
            }else{
                return_msg(400,'删除失败');
            }
        }else{
            $res=MerchantDishCart::where('id',$id)->update(['dish_number'=>$number]);
            if($res){
                return_msg(200,'success');
            }else{
                return_msg(400,'failure');
            }
        }
    }

    /**
     *加
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $id=request()->param('id');
        $data=MerchantDishCart::field('dish_number')->where('id',$id)->find();
        $number=$data['dish_number']+1;
       
            $res=MerchantDishCart::where('id',$id)->update(['dish_number'=>$number]);
            if($res){
                return_msg(200,'success');
            }else{
                return_msg(400,'failure');
            }
        }

    /**
     *订单列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order_list()
    {
        $info=request()->post();

        $data=MerchantDishCart::alias('a')
            ->field('a.id,a.dish_number,b.dish_name,b.money')
            ->join('cloud_merchant_dish b','a.dish_id=b.id','left')
            ->where(['a.table_number'=>$info['table_name'],'a.shop_id'=>$info['shop_id']])
            ->select();
        check_data($data);
     }

    /**
     *提交订单
     */
    public function submit_order()
    {
        $data=request()->post();
        $arr=[
            'status'=>0,
            'order_money'=>$data['total_money'],
            'shop_id'=>$data['shop_id'],
            'received_money'=>$data['total_money'],
            'create_time'=>time(),
            'order_remark'=>$data['order_remark'],
            'order_number'=>generate_order_no(),
            'table_name'=>$data['table_name'],
            'meal_number'=>$data['meal_number'],
        ];
        $data=Order::insert($arr);
        if($data){
            return_msg(200,'成功');
        }else{
            return_msg(400,'失败');
        }
     }

    /**
     *确认订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirm_orders()
    {
        $res=request()->post();

        $data['order']=Order::field('order_number,order_remark,order_money')->where(['table_name'=>$res['table_name'],'shop_id'=>$res['shop_id']])->find();

        $data['list']=MerchantDishCart::alias('a')
            ->field('a.id,a.dish_number,b.dish_name,b.money')
            ->join('cloud_merchant_dish b','a.dish_id=b.id','left')
            ->where(['a.table_number'=>$res['table_name'],'a.shop_id'=>$res['shop_id']])
            ->select();

        check_data($data);
     }

    /**
     *支付成功
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay_order()
    {
        $data=request()->post();

        Order::where(['shop_id'=>$data['shop_id'],'table_name'=>$data['table_name']])->update(['status'=>1,'pay_type'=>'wxpay']);

        $info['order']=Order::field('status,meal_number,order_number,received_money')->where(['shop_id'=>$data['shop_id'],'table_name'=>$data['table_name']])->find();


    }
}