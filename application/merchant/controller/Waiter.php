<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 14:06
 */

namespace app\merchant\controller;


use app\agent\model\Order;
use app\merchant\model\MerchantDishCart;
use app\merchant\model\MerchantDishNorm;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\ShopActiveDiscount;
use app\merchant\model\ShopComestible;
use app\merchant\model\ShopTable;
use think\Controller;
use think\Db;
use think\Request;
use app\merchant\model\MerchantDish;
use app\merchant\controller\Commonality;


class Waiter extends Commonality
{
    public $array=[4=>'十分推荐',3=>'推荐',5=>'强烈推荐',6=>'新品上市',7=>'人气热销',8=>'本店招牌',9=>'爆款'];

    /**
     * 店小二首页面
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($nerid=0)
    {
        $id=$this->id;
        $role=$this->role;
        $name=$this->name;

        //判断是否是商户
        if($role==-1) {
            //门店名称及id
            $shop = MerchantShop::where($name, $id)->field(['id', 'shop_name'])->select();
            //订单数量
            //赋值门店id
            $id=$shop[0]['id'];
        }
        if($nerid!=0){
            $id=$nerid;
        }

            $data['count']=Order::where('shop_id',$id)
                ->whereTime('create_time', 'today')
                ->count('id');
            $data['shop_id']=$id;

//        var_dump($data);die;
//            return_msg(200,'success',$shop);
            //order_receiving接单状况 1已接单  status 0未支付
            //下单消息 服务消息

            $result=Order::alias('a')
                ->field(['b.name','a.status','a.order_receiving','a.table_name','a.create_time','a.receivi_time'])
                ->join('cloud_merchant_user b','b.id=a.user_id','left')
                ->where(['a.shop_id'=>$id,'a.status'=>0])
                ->whereTime('a.create_time', 'today')
                ->select();
            if($role==-1){
                $data=['shop'=>$shop,'count'=>$data,'result'=>$result];
            }else{
                $data=['count'=>$data,'result'=>$result];
            }

            return_msg(200,'success',$data);

    }

    /**
     * Notes:
     * User: guoyang
     * DATE: 2018/10/25
     * @param Request $request
     * @return string
     */
    public function index_query(Request $request)
    {
        $shop_id=$request->param('shop_id');
        return $this->index($shop_id);
    }

    /**
     * 服务设置
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function waiter_set()
    {

        if($this->role==-1){
            $shop=MerchantShop::where('merchant_id',$this->id)->field(['id','shop_name'])->select();
            return json_encode($shop);
        }
    }

    /**
     *菜品设置
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_category(Request $request)
    {
        $shop_id=$request->param('shop_id');
        //取出所有分类
        $data['list']=MerchantDish::alias('a')
            ->field('a.norm_id,b.dish_norm')
            ->join('cloud_merchant_dish_norm b','a.norm_id=b.id','left')
            ->where(['a.shop_id'=>$shop_id,'a.status'=>1])
            ->group('a.norm_id')
            ->select();
//            halt($data);
        $res=[];
        foreach($data['list'] as $v){
            $info=MerchantDish::where(['shop_id'=>$shop_id,'norm_id'=>$v['norm_id']])->select();
//                halt($data['info']);
//                $arr=[];
            /*foreach($info as &$s){
                $arr=ltrim($s['dish_attr'],'[');
                $arr=rtrim($arr,']');
                $arr=explode(',',$arr);
                $s['dish_attr']=$arr;
            }*/
            $res[]=$info;
        }
        $data['info']=$res;

        check_data($data);
//        }
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
        $data=MerchantDish::where(['dish_name'=>['like',$name.'%'],'shop_id'=>$shop_id,'status'=>1])->select();
        //属性
        /*foreach($data as &$v){
            $arr=ltrim($v['dish_attr'],'[');
            $arr=rtrim($arr,']');
            $arr=explode(',',$arr);
            $v['dish_attr']=$arr;
        }*/
        check_data($data);
    }

    /**
     * 新增菜品
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_cuisine(Request $request)
    {
        //页面展示
        if(Request::instance()->isGet()){
            //取出所有属性
            $shop_id=$request->param('shop_id');
            $data=[];
            //菜品分类
            $data['data']=Db::name('merchant_dish_norm')->where('shop_id',$shop_id)->select();
//            $data['type']=Db::name('merchant_dish_norm')->where('shop_id',$shop_id)->field('id,dish_norm')->select();
            $data['shop_id']=$shop_id;
            check_data($data);
        }elseif(Request::instance()->isPost()){
            //新增菜品
            $data=$request->post();
            //标签 []
            //验证
            check_params('add_cuisine',$data,'MerchantValidate');
//            $data['dish_label']=explode(',',$data['dish_label']);
//            $data['dish_attr']=explode(',',$data['dish_attr']);
//            dump($data['dish_attr']);die;
            $file=$request->file('dish_img');
            //裁剪200*200图
            $tailor=$this->tailor_img($file);
            if(!$tailor){
                return_msg(400,'success','图片格式不正确');
            }
//            var_dump($file);die;
            //原图

           $file=$tailor;

            $data['dish_img']=$file;
            $dish=Db::name('merchant_dish')->insert($data);

            if($dish){
                return_msg(200,'success','增加菜品成功');
            }else{
                return_msg(400,'success','增加菜品失败');
            }
        }
    }

    /**
     * 编辑菜品
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function edit_cuisine(Request $request)
    {

        //菜品id
        $id=$request->param('id');
        $shop_id=$request->param('shop_id');
        if($request->isPost()){

            $post=$request->post();
            //验证
            check_params('edit_cuisine',$post,'MerchantValidate');
            //判断是否修改图片
            if($request->file('dish_img')){

                $file=$request->file('dish_img');
                //裁剪200*200图
                $tailor=$this->tailor_img($file);
                if(!$tailor){
                    return_msg(400,'success','图片格式不正确');
                }

                $post['dish_img']=$tailor;
            }

            $data=MerchantDish::where('id',$id)->update($post);
            if($data){
                return_msg(200,'success','修改菜品成功');
            }else{
                return_msg(400,'success','修改菜品失败');
            }
        }else{
            //取出菜品信息
            $data['list']=MerchantDish::where('id',$id)->select();
            //属性
            foreach($data['list'] as &$v){
                $arr=ltrim($v['dish_attr'],'[');
                $arr=rtrim($arr,']');
                $arr=explode(',',$arr);
                $v['dish_attr']=$arr;
            }

            //菜品分类
            $data['attr']=MerchantDishNorm::field('id,dish_norm')->where('shop_id',$shop_id)->select();
            check_data($data);
        }

    }
    /**
     * 生成二维码
     */
    public function qrcode2(){
        $shop_id=\request()->param('shop_id');
        $name=\request()->param('name');
        if(empty($shop_id) || empty($name)){
            return_msg(400,'error','请输入桌位名称或传入门店');
        }
      return $this->qrcode(0,$shop_id,$name);
    }

    /**
     * 保存桌码  二维码
     * image  二维码地址
     * name   桌码名称
     * shop_id  门店id
     * @param Request $request
     */
    public function save_code(Request $request)
    {
       $data= $request->post();
        list($usec, $sec) = explode(" ", microtime());
        $times=str_replace('.','',$usec + $sec);


        //入库
        $resu=ShopTable::insert(['create_time'=>time(),'code'=>$times,'image'=>$data['image'],'name'=>$data['name'],'shop_id'=>$data['shop_id']]);
        if($resu){
            return_msg(200,'success','保存桌码成功');
        }else{
            return_msg(400,'error','保存桌码失败');
        }
    }
    /**
     * 生成隨機數
     * @param $min
     * @param $max
     * @param $num
     * @return array|null
     */
    public function  unique_rand($min,$max,$num){
        $count = 0;
        $return_arr = array();
        while($count < $num){
            $return_arr[] = mt_rand($min,$max);
            $return_arr = array_flip(array_flip($return_arr));
            $count = count($return_arr);
        }
        shuffle($return_arr);
        return $return_arr;
    }

    /**
     * 桌位设置
     * @param Request $request
     */
    public function table_set(Request $request)
    {
        if(Request::instance()->isGet()){
            $data=Db::name('shop_table')->where('shop_id',$request->param('shop_id'))->select();
            return json_encode($data);
        }else if(Request::instance()->isPost()) {
            $shop_id=$request->param('shop_id');
            $img = $request->file('img');
            $file_path = upload_pics($img);
            $code = $this->unique_rand(10000000, 99999999, 1);
            //當前時間
            $date = time();
            $resu = ShopTable::create(['shop_id'=>$shop_id,'create_time' => $date, 'code' => $code[ 0 ], 'img' => $file_path]);
            if ($resu) {
                return_msg(200, 'success', '添加成功');
            } else {
                return_msg(400, 'error', '添加失败');
            }
        }

    }

    /**
     * 删除桌位号 删除桌码
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function table_delete(Request $request)
    {
        $result=\db('shop_table')->delete($request->param('id'));
        if($result){
            return_msg(200,'success','删除成功');
        }else{
            return_msg(400,'error','删除失败');
        }
    }

    /**
     * 餐具费设置
     * istableware 餐具是否收费 1收费 0不收费
     * tableware_money 餐具收费金额
     * id 門店id
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function tableware_set(Request $request)
    {
        Db::name('merchant_shop')->update($request->post());

        return_msg(200,'success','设置成功');

    }

    /**
     * 付款顺序
     * paymentorder    1先上菜后付款   2先付款后上菜
     * id 門店id
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function paySequence(Request $request)
    {

        //paymentorder  付款顺序   1先上菜后付款   2先付款后上菜
       Db::name('merchant_shop')->update($request->post());

            return_msg(200,'success','设置成功');

    }



    /**
     * 菜品下架
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cuisine_soldout(Request $request)
    {
        $result=Db::name('merchant_dish')->where('id',$request->param('id'))->update(['status'=>0]);
        if($result){
            return_msg(200,'success','下架成功');
        }else{
            return_msg(400,'error','下架失败');
        }
    }

    /**
     *已下架菜品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dish_soldout()
    {
        $shop_id=request()->param('shop_id');
        $data=MerchantDish::where(['shop_id'=>$shop_id,'status'=>0])->select();
        check_data($data);
    }

    /**
     *菜品上架
     */
    public function dish_shelf()
    {
        $id=request()->param('id');
        $data=MerchantDish::where('id',$id)->update(['status'=>1]);
        if($data){
            return_msg(200,'操作成功');
        }else{
            return_msg(200,'操作失败');

        }
    }

    /**
     *删除菜品
     */
    public function dish_del()
    {
        $id=request()->param('id');
        $data=MerchantDish::where('id',$id)->delete();
        if($data){
            return_msg(200,'操作成功');
        }else{
            return_msg(200,'操作失败');

        }
    }

    /**
     * 管理分类
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function manage_type(Request $request)
    {
        $shop_id=$request->param('shop_id');
        $data=MerchantDishNorm::where('shop_id',$shop_id)->select();

        check_data($data);
    }

    /**
     * 添加菜品分类  新增管理分类
     * dish_norm  分类名称
     * shop_id   门店id
     * @param Request $request
     */
    public function add_type(Request $request)
    {
        $data=$request->post();
        //dish_norm 菜品分类名称
        $result=Db::name('merchant_dish_norm')->insert($data);
        if($result){
            return_msg(200,'success','菜品分类新增成功');
        }else{
            return_msg(400,'error','菜品分类新增失败');
        }
    }

    /**
     * 编辑菜品分类  修改菜品分类
     * @param Request $request
     */
    public function set_type(Request $request)
    {
        //菜品分类id
        $id=$request->param('id');
        $name=$request->param('dish_norm');

        //dish_norm 菜品分类名称
        $result=Db::name('merchant_dish_norm')->where('id',$id)->update(['dish_norm'=>$name]);
        if($result){
            return_msg(200,'success','菜品分类修改成功');
        }else{
            return_msg(400,'error','菜品分类修改失败');
        }
    }

    /**
     * 删除菜品分类
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete_type(Request $request)
    {
        $id=$request->param('id');

        $data=MerchantDish::where('norm_id',$id)->select();
        if(!$data){
            $resu=MerchantDishNorm::where('id',$id)->delete();
            if($resu){
                return_msg(200,'success','菜品分类删除成功');
            }else{
                return_msg(400,'error','菜品分类删除失败');
            }
        }else{
            return_msg(500,'error','此分类中还有菜品，不能删除');

        }
    }
    /**
     * 今日订单数据
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Today_order(Request $request)
    {

        $shop_id=$request->param('shop_id');
        $data=Order::alias('a')
            ->field(['sum(b.money) money','a.received_money','a.table_name','a.create_time','a.status','a.id'])
            ->join('shop_comestible b','a.id=b.order_id')
            ->where('a.shop_id',$shop_id)->whereTime('a.create_time','d')
            ->group('b.order_id')
            ->select();

        if($data){
            return json_encode($data);
        }else{
            return_msg(400,'error','今日没有订单');
        }
    }

    /**订单详情
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function today_orderquery(Request $request)
    {
        //订单ID
        $order_id=$request->param('id');
        //status 支付状态 0未支付 1已支付 2已关闭 3会员充值
        //已支付订单详情
//            $data=Order::alias('a')
//                ->field(['c.istableware','a.discount','c.tableware_money','a.order_number','a.table_name','a.id','a.meal_number','a.order_money','a.discount','a.received_money','a.create_time','b.name','a.order_person','sum(b.money*b.deal) money','b.deal','a.status'])
//                ->join('shop_comestible b','a.id=b.order_id','left')
//                ->join('merchant_shop c','a.shop_id=c.id')
//                ->where(['a.id'=>['=',$order_id]])
//                ->group('b.id')
//                ->select();
//            if(!$data){
//                return_msg(400,'success','此订单出现意外');
//            }
//            //餐具是否收费
//            if($data[0]['istableware']!=0){
//                //meal_number 用餐人数
//                $data[]=['name'=>'餐具','deal'=>$data[0]['meal_number'],'money'=>$data[0]['tableware_money']*$data[0]['meal_number']];
//            }
//            $res=0;
//            foreach ($data as $k=>$v){
//               $res+=$v['money'];
//            }
//            $data['summoney']=$res;
//            return_msg(200,'successs',$data);

            //非支付状态
            $data=Order::alias('a')
//                ->field(['sum(b.money*b.deal) money','a.discount','c.istableware','c.tableware_money','a.order_number','a.table_name','a.id','b.create_time','a.order_person','a.meal_number','a.create_time','b.name', 'b.deal','a.status'])
                ->field(['c.id as shop_id,c.istableware','a.discount','c.tableware_money','a.order_number','a.table_name','a.id','a.meal_number','a.order_money','a.discount','a.received_money','a.create_time','b.name','a.order_person','sum(b.money*b.deal) money','b.deal','a.status'])
                ->join('shop_comestible b','a.id=b.order_id','left')
                ->join('merchant_shop c','a.shop_id=c.id')
                ->where(['a.id'=>['=',$order_id]])
                ->group('b.id')
                ->select();
            if(!$data){
                return_msg(400,'success','此订单出现意外');
            }
            //餐具是否收费
            if($data[0]['istableware']!=0){
                //meal_number 用餐人数
                $data[]=['name'=>'餐具','deal'=>$data[0]['meal_number'],'money'=>$data[0]['tableware_money']*$data[0]['meal_number']];
            }
            $sum=0;
            foreach ($data as $k=>$v)
            {
                $sum+=$v['money'];
            }
            //总金额
            $data[0]['sum_money']=$sum;

            return_msg(200,'successs',$data);


    }

    /**
     * 订单数据查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indent_query(Request $request)
    {
        //门店id
        $id=$request->param('shop_id');
        //开始时间和结束时间
        $create_time=$request->param('create_time') ? strtotime($request->param('create_time')) : mktime(0,0,0,date('m'),date('d'),date('Y'));
        $end_time=$request->param('end_time') ? strtotime($request->param('end_time')) : mktime(23,59,59,date('m'),date('d'),date('Y'));
        //$status 5全部状态 支付状态 0未支付 1已支付 2已关闭 3会员充值
        $status=$request->param('status');
       if($status!==0 && $status<4){
           $symbol='=';
       }else{
           $status=5;
            $symbol='<>';
        }
        $data=Order::where(['shop_id'=>['=',$id],'create_time'=>['between',[$create_time,$end_time]],'status'=>[$symbol,$status]])->field(['received_money','table_name','create_time','status','id'])->select();
        if($data){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'error','没有订单');
        }
    }

    /**
     * 服务数据首页
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serve_list(Request $request)
    {
        $shop_id=$request->param('shop_id') ? $request->param('shop_id') : 0;;
        $user_id=$request->param('user_id') ? $request->param('user_id') : 0;
        //获取数据
        $data=$this->serve_public($shop_id,$user_id);
        if($data){
            return json_encode($data);
        }else{
            return_msg(400,'error','没有订单');
        }
    }

    /**
     * 获取服务员的服务次数和姓名
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serve_public($shop_id,$user_id)
    {
        $syemo='=';
        if($user_id==0){
            $syemo='<>';
        }
        $shopsyemo='=';
        if($shop_id==0){
            $shopsyemo='<>';
        }
        $data=Order::alias('a')
            ->field(['b.name','count(a.id) as countid'])
            ->join('cloud_merchant_user b','a.user_id=b.id')
            ->where(['a.shop_id'=>[$shopsyemo,$shop_id],'a.user_id'=>[$syemo,$user_id]])
            ->whereTime('a.create_time', 'today')
            ->group('a.user_id')
            ->select();
        return $data;
    }

    /**
     * 服务员服务次数详情  服务员数据详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serve_query(Request $request)
    {
        $shop_id=$request->param('shop_id');
        $user_id=$request->param('user_id');
        //获取服务总次数
        $count=$this->serve_public($shop_id,$user_id);
        $data=Db::name('order')->where(['user_id'=>['=',$user_id]])
            ->whereTime('create_time', 'today')
            ->field(['table_name','count(id) as counttable'])
            ->group('table_name')
            ->select();
        $data['count']=$count;
        if($count){
            return_msg(200,'success',json_encode($data));
        }else{
            return_msg(400,'error','没有服务数据');
        }
    }

    /**
     * 订单关闭
     * @param Request $request
     */
    public function order_close(Request $request)
    {
        //获取order表中的id
        $id=$request->param('id');
        $result=Order::where('id',$id)->update(['status'=>2]);
        if($result){
            return_msg(200,'success','关闭成功');
        }else{
            return_msg(400,'error','关闭成功');
        }
    }

    /**
     * 菜品属性
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function add_attribute(Request $request)
    {

        //id 菜品id
        $id=$request->param('id');
        if($id){
            $data=MerchantDish::where('id',$id)->field('dish_attr')->find();
            $data=explode(',',$data['dish_attr']);
            
            return_msg(200,'success',json_encode($data));
        }

    }

    /**
     *  点菜 --服务端桌位展示   桌位搜索
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function choose_table(Request $request)
    {
        $shop_id=$request->param('shop_id');
        //服务员端
        if($this->role==1){
            $shop_id=MerchantUser::where('id',$this->id)->field(['shop_id'])->find();
            $shop_id=$shop_id['shop_id'];
        }
        //展示桌位
        if($request->isGet()){


            $table=Db::name('shop_table')->where('shop_id',$shop_id)->field(['id','name','shop_id'])->select();
            if(empty($table)){
                return_msg(400,'error','无桌位');
            }
            return_msg(200,'success',$table);

        }elseif ($request->isPost()){
            //桌位搜索
            $name=$request->param('name');

            $table=Db::name('shop_table')->where(['shop_id'=>$shop_id,'name'=>['like',"%$name%"]])->field(['id','name','shop_id'])->select();
            if($table){
                return_msg(200,'success',$table) ;
            }else{
                return_msg(400,'error','没有此桌位') ;
            }

        }


    }




    /**
     * 服务员端首页
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
        public function call_service()
    {
        $shop_id=$this->id;
        //服务员端
        if($this->role==1){
            //获取门店id
            $shop_id=MerchantUser::where('id',$this->id)->field(['shop_id'])->find();
            $shop_id=$shop_id['shop_id'];
        }

        //order_receiving 是否接单 0未接单 1已接单 2已完结
        $receiving=Order::where(['shop_id'=>$shop_id,'order_receiving'=>['in','0,1']])->field(['order_receiving','table_name','create_time','id'])->select();
        $data=[];
        foreach ($receiving as $k=>$v){
            if($v['order_receiving']==0){
                $data['call'][]=$v;
            }else{
                $data['accepted '][]=$v;
            }
        }

        check_data($data);

    }

    /**
     * 点击接单
     * @param Request $request
     * @order_id    订单id
     */
    public function receiving(Request $request)
    {
        $id=$request->param('order_id');
        $data=['id'=>$id,'order_receiving'=>1];
        $result=Order::update($data);
        if($result){
            return_msg(200,'success','接单成功');
        }else{
            return_msg(400,'error','接单失败');
        }

    }


    /**
     * 服务员端首页   服务记录
     * @param Request $request
     * @return string
     */
    public function waiter_list(Request $request)
    {
        $user_id=session('user_id');
        $user_id=1;
//        $shop_id=$this->id;
//        //服务员端
//        if($this->role==1){
//            $shop_id=MerchantUser::where('id',$this->id)->field(['shop_id'])->find();
//            $shop_id=$shop_id['shop_id'];
//        }
        if(Request::instance()->isGet()) {
            $data = Db::query("select FROM_UNIXTIME(create_time,'%Y%m%d') create_time,count(id) ids,id,table_name from cloud_order where FROM_UNIXTIME(create_time,'%Y%m%d')=curdate() and order_receiving=1 and user_id=$user_id group by table_name");
            return $this->service_query($data);
        }else if(Request::instance()->isPost()){
            $create_time=strtotime($request->param('create_time'));
            $end_time=strtotime($request->param('end_time'))+60*60*24-1;
            if(!$end_time){
                $data = Db::query("select FROM_UNIXTIME(create_time,'%Y%m%d') create_time,count(id) ids,id,table_name from cloud_order where FROM_UNIXTIME(create_time,'%Y%m%d')=curdate() and order_receiving=1 and user_id=$user_id group by table_name");
            }else{
                $data = Db::query("select FROM_UNIXTIME(create_time,'%Y%m%d') create_time,count(id) ids,id,table_name from cloud_order where order_receiving=1 and user_id=$user_id and create_time between $create_time and $end_time group by table_name");
                }
                $result=$this->service_query($data);
            if($result){
                return_msg(200,'success',$result);
            }else{
                return_msg(400,'error','没有此时间段的服务记录');
            }

            }



    }

    /**
     * 查询服务记录
     * @param $data
     * @return string
     */
    public function service_query($data)
    {

        $arr = [];
        foreach ($data as $k => &$v) {
            $v[ 'create_time' ] = substr_replace($v[ 'create_time' ], '-', 4, 0);
            $v[ 'create_time' ] = substr_replace($v[ 'create_time' ], '-', 7, 0);
            for ($i = 0; $i < count($data); $i++) {
                if ($v[ 'create_time' ] == $data[ $i ][ 'create_time' ]) {
                    if ($v[ 'id' ] == $data[ $i ][ 'id' ])
                        $arr[ $v[ 'create_time' ] ][] = $v;
                }
            }
        }
        foreach ($arr as $k=>&$v){
            $ids=0;
            foreach ($v as $i=>$t){
                $ids+=$t['ids'];
            }
            $v['count']=$ids;
        }
        return json_encode($arr);
    }

    /**
     * 下载二维码
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  proceeds(Request $request)
    {
        $id=$request->param('id');
        $url=ShopTable::where('id',$id)->field('name,image')->find();
//        return $url->image;
        return_msg(200,'success',$url);
//        return $this->download($url->image);
    }

    /**
     * 下载二维码
     * @param $file_url
     * @param string $new_name
     */
   public function download($file_url,$new_name=''){

        if(!file_exists($file_url)){ //检查文件是否存在
            echo '404';
        }

        $file_name=basename($file_url);
        $file_type=explode('.',$file_url);
        $file_type=$file_type[count($file_type)-1];
        $file_name=trim($new_name=='')?$file_name:urlencode($new_name);
//       var_dump($file_name);die;
        $file_type=fopen($file_url,'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".filesize($file_url));
        header("Content-Disposition: attachment; filename=".$file_name);

        //输出文件内容
        echo fread($file_type,filesize($file_url));
        fclose($file_type);
    }

    /**
     * 修改用餐人数
     */
    public function modidyNumberPeople()
    {
//        $id=\request()->param('id');
//        $data=Order::where("id",$id)->update()
    }

    public function shiyan()
    {
     /*   $arr=[6=>"/www/wwwroot/CloudPay/public/uploads/20181018/86f2b35d816282eba85a1ba86a73f429.png",
            7=>"/www/wwwroot/CloudPay/public/uploads/20181018/14da24389a4d54f31ff8002b3190edcc.png",
            8=>"/www/wwwroot/CloudPay/public/uploads/20181018/51608e6faebf8e0ca85e76198b0a5a55.png"
            ];
//        MerchantShop::where('merchant_id',2)->update(['imgFile'=>json_encode($arr)]);
        $data=MerchantShop::where('id',106)->field('imgFile')->find();
        $data=json_decode($data->imgFile,true);
        unset($data[7]);
$data[7]="/www/wwwroot/CloudPay/public/uploads/20181018/14da24389a4d54f31ff8002b3190edcc.png";

        sort($data);var_dump($data);*/




    }

}