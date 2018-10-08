<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 14:06
 */

namespace app\merchant\controller;


use app\agent\model\Order;
use app\merchant\model\MerchantShop;
use app\merchant\model\MerchantUser;
use app\merchant\model\ShopTable;
use think\Controller;
use think\Db;
use think\Request;
use app\merchant\model\MerchantDish;


class Waiter extends Controller
{
    /**
     * 店小二首页面
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        $shop_id=session('shop_id');
        if($merchant_id){
            //门店名称及id
            $shop=MerchantShop::where('merchant_id',$merchant_id)->field(['id','shop_name'])->select();
                //订单数量
            $data=Order::where('merchant_id',$merchant_id)
                ->whereTime('create_time', 'today')
                ->count('id');
            //order_receiving接单状况 1已接单  status 0未支付
            //下单消息 服务消息
            $result=Order::alias('a')
                ->field(['b.name','a.status','a.order_receiving','a.table_name','a.create_time','a.receivi_time'])
                ->join('cloud_merchant_user b','b.id=a.user_id','left')
                ->where(['a.merchant_id'=>$merchant_id,'a.status'=>0,'a.order_receiving'=>1])
                ->whereTime('a.create_time', 'today')
                ->select();
            $data=['shop'=>$shop,'count'=>$data,'result'=>$result];
            return json_encode($data);
        }else if($shop_id){
            //订单数量
            $data=Order::where('shop_id',$shop_id)
                ->whereTime('create_time', 'today')
                ->count('id');
            //order_receiving接单状况 1已接单  status 0未支付
            //下单消息 服务消息
            $result=Order::alias('a')
                ->field(['b.name','a.status','a.order_receiving','a.table_name','a.create_time','a.receivi_time'])
                ->join('cloud_merchant_user b','b.id=a.user_id','left')
                ->where(['a.merchant_id'=>$merchant_id,'a.status'=>0,'a.order_receiving'=>1])
                ->whereTime('a.create_time', 'today')
                ->select();
            $data=['count'=>$data,'result'=>$result];
            return json_encode($data);
        }
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
        if(session('merchant_id')){
            $merchant_id=session('merchant_id');
            $shop=MerchantShop::where('merchant_id',$merchant_id)->field(['id','shop_name'])->select();
            return json_encode($shop);
        }
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

        if(Request::instance()->isGet()){
            //取出所有属性
            $shop_id=$request->param(['shop_id']);
            $data=Db::name('merchant_dish_norm')->select();
            $data['shop_id']=$shop_id;
            return json_encode($data);
        }else if(Request::instance()->isPost()){
            $data=$request->post();
            $file=$request->file('img');
            $file=upload_pics($file);
            $data['img']=$file;
            $dish=new MerchantDish();
            $dish->data;
            $resu=$dish->save();
            if($resu){
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
        $id=$request->param('id');
        $post=$request->post();
        //判断是否修改图片
        if($request->file('dish_img')){
            $file=upload_pics($request->file('dish_img'));
            $post['dish_img']=$file;
        }

        $data=MerchantDish::where('id',$id)->update($post);
        if($data){
            return_msg(200,'success','修改菜品成功');
        }else{
            return_msg(400,'success','修改菜品失败');
        }

    }

   public function qrcode()
    {
        Vendor('phpqrcode.phpqrcode');
        import('phpqrcode.phpqrcode', EXTEND_PATH,'.php');
        $value = 'http://www.cnblogs.com/txw1958/'; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6;//生成图片大小
//生成二维码图片
//        Loader::import('qrcode', EXTEND_PATH);
        \QRcode::png($value, 'qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
//生成二维码图片
        $logo = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1538050591782&di=045c3edb26144c5207c7d61c23a02817&imgtype=0&src=http%3A%2F%2Fpic.58pic.com%2F58pic%2F15%2F23%2F09%2F74T58PICZjg_1024.jpg';//准备好的logo图片
        $QR = 'qrcode.png';//已经生成的原始二维码图

        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
//输出图片
        imagepng($QR, 'helloweixin.png');
        echo '<img src="helloweixin.png">';
    }

    public function qrcode2(){
        header("content-type:text/html;charset=utf-8");
        Vendor('phpqrcode.phpqrcode');
        import('phpqrcode.phpqrcode', EXTEND_PATH,'.php');
        $path = "/Uploads/QRcode/";//创建路径
        if(!file_exists($path)){
            mkdir($path, 0700,true);//创建目录
        }
//
        $time = time().'.png'; //创建文件名
        $fileName = $path.$time;//1.命名生成的二维码文件
//        dump($fileName);die;
        $file_name = iconv("utf-8","gb2312",$time);
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/'.$fileName;

        $data = "https://www.baidu.com";//2.生成二维码的数据(扫码显示该数据)
        $level = 'L';  //3.纠错级别：L、M、Q、H
        $size = 4;//4.点的大小：1到10,用于手机端4就可以了
        ob_end_clean();//清空缓冲区
        //生成二维码-不保存：在当前浏览器显示
        \QRcode::png($data, $file_path, $level, $size);
        //文件名转码
        //生成桌位編號
        return $file_path;

        $code=$this->unique_rand(10000000,99999999,1);


        //當前時間
        $date=time();

        $resu=ShopTable::create(['create_time'=>$date,'code'=>$code[0],'img'=>$file_path]);
        //获取下载文件的大小
        $file_size = filesize($file_path);

        $file_temp = fopen ( $file_path, "r" );
        //返回的文件
        header("Content-type:application/octet-stream");
        //按照字节大小返回
        header("Accept-Ranges:bytes");

        //返回文件大小
        header("Accept-Length:".$file_size);
        //这里客户端的弹出对话框
        header("Content-Disposition:attachment;filename=".$time);

        echo fread ( $file_temp, filesize ( $file_path ) );
        fclose ( $file_temp );
        exit ();
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
     * 删除桌位号
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
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function tableware_set(Request $request)
    {
        $shop_id=$request->param('shop_id');

        $result=Db::name('merchant_shop')->where('id',$shop_id)->update($request->post());
        if($result){
            return_msg(200,'success','设置成功');
        }else{
            return_msg(400,'error','设置失败');
        }
    }

    /**
     * 菜品设置
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

            $data = MerchantDish::where('shop_id', $shop_id)->field(['shop_id','dish_name', 'dish_introduce', 'money', 'dish_categery', 'dish_img', 'id'])->select();
            //菜品分类
            $type=Db::name('merchant_dish_norm')->where('parent_id',8)->select();
            return json_encode([$data,$type]);
        }else if($request->param('dish_name')){
            $dish_name=$request->param('dish_name');
            $data=MerchantDish::where(['dish_name'=>['like','%'.$dish_name.'%'],'shop_id'=>$shop_id])->select();
            if($data){
                return_msg(200,'success',json_encode($data));
            }else{
                return_msg(400,'error','查无此菜品');
            }
        }
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
        $data=MerchantDish::alias('a')
            ->field('b.dish_norm,b.id')
            ->join('cloud_merchant_dish_norm b','b.id=a.norm_id')
            ->where('shop_id',$shop_id)
            ->group('a.norm_id')
            ->select();
        return json_encode($data);
    }

    /**
     * 添加菜品分类  新增管理分类
     * @param Request $request
     */
    public function add_type(Request $request)
    {
        $data=$request->post();
        $data['parent_id']=8;
        //dish_norm 菜品分类名称
        $result=Db::name('merchant_dish_norm')->insert($data);
        if($result){
            return_msg(200,'success','菜品分类新增成功');
        }else{
            return_msg(400,'error','菜品分类新增失败');
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
        $data=Order::where('shop_id',$shop_id)->field(['received_money','table_name','create_time','status','id'])->select();
        if($data){
            return json_encode($data);
        }else{
            return_msg(400,'error','没有订单');
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
        if($request->param('status')==1){
            $data=Order::alias('a')
                ->field(['a.table_name','a.order_number','a.meal_number','a.order_money','a.discount','a.received_money','a.create_time','b.name','b.order_person','b.money','b.deal','a.status'])
                ->join('cloud_shop_comestible b','a.order_number=b.order_number','left')
                ->where(['a.id'=>['=',$order_id]])
                ->select();
            return json_encode($data);
        }else{
            $data=Order::alias('a')
                ->field(['a.table_name','a.order_number','b.create_time','b.order_person','a.meal_number','a.create_time','b.name','b.order_person','b.money','b.deal','a.status'])
                ->join('cloud_shop_comestible b','a.order_number=b.order_number','left')
                ->where(['a.id'=>['=',$order_id]])
                ->select();
            $sum=0;
            foreach ($data as $k=>$v)
            {
                $sum+=$v['money'];
            }
            $data['sum_money']=$sum;
            return json_encode($data);
        }

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
        $status=$request->param('status') ? $request->param('status') :5;
        $symbol='=';
        if($status==5){
            $symbol='<>';
        }
        $data=Order::where(['shop_id'=>['=',$id],'create_time'=>['between',[$create_time,$end_time]],'status'=>[$symbol,$status]])->field(['received_money','table_name','create_time','status','id'])->select();
        if($data){
            return_msg(200,'success',json_encode($data));
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
        $id=$request->param('shop_id');
        //获取数据
        $data=$this->serve_public($id);
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
    public function serve_public($shop_id,$user_id=0)
    {
        $syemo='=';
        if($user_id==0){
            $syemo='<>';
        }
        $data=Order::alias('a')
            ->field(['b.name','count(a.id) as countid','a.user_id'])
            ->join('cloud_merchant_user b','a.user_id=b.id')
            ->where(['a.shop_id'=>['=',$shop_id],'a.user_id'=>[$syemo,$user_id]])
            ->whereTime('create_time', 'today')
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
     * 属性的删除与新增  首页面展示 新增属性
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function add_attribute(Request $request)
    {
        if($request->post()){
            if($request->param('norm_id')){
                $resu=Db::table('merchant_dish_norm')->delete($request->param('norm_id'));
                if($resu){
                    return_msg(200,'success','删除属性成功');
                }else{
                    return_msg(400,'error','删除属性失败');
                }
            }else{
                $result=Db::name('merchant_dish_norm')->insert($request->post());
                if($result){
                    return_msg(200,'success','添加属性成功');
                }else{
                    return_msg(400,'error','添加属性失败');
                }
            }
        }else{
            $data=Db::name('merchant_dish_norm')->where('parent_id',0)->select();
            return json_encode($data);
        }

    }

    /**
     *  点菜 --服务端桌位展示
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function choose_table()
    {
        $user_id=session('user_id');
        $user_id=1;
        $shop_id=MerchantUser::where('id',$user_id)->field(['shop_id'])->find();
        $shop_id=$shop_id['shop_id'];
        $table=Db::name('shop_table')->where('shop_id',$shop_id)->field(['id','name','shop_id'])->select();

        return json_encode($table);

    }

    /**
     * 去下单
     * @param Request $request
     * @return string
     */
    public function place_order(Request $request)
    {
        $data=$request->post();
        //订单总金额
        $count_money=0;
        foreach ($data as $k=>$v){
            $count_money+=$v['money'];
        }
        $data['count_money']=$count_money;
        return json_encode($data);
    }

    /**
     * 确定订单
     * @param Request $request
     */
    public function confirm_order(Request $request)
    {
        $data=$request->post();
        //订单号
//        $code = $this->unique_rand(100000000000000000, 999999999999999999, 1);
        list($usec, $sec) = explode(" ", microtime());
        $times=str_replace('.','',$usec + $sec);
        $timese=date('YmdHis',time());
        $code=$timese.$times;
        $order=['order_money'=>$data['order_money'],'received_money'=>$data['received_money'],'table_name'=>$data['table_name'],'order_remark'=>$data['order_remark'],'shop_id'=>$data['shop_id'],'merchant_id'=>$data['merchant_id'],'order_number'=>$code];
        $order_id=Order::insertGetId($order,true);


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
        $user_id=session('user_id');
        $user_id=1;
        //获取门店id
        $shop=MerchantUser::where('id',$user_id)->field(['shop_id'])->find();
        //order_receiving 是否接单 0未接单 1已接单 2已完结
        $receiving=Order::where(['shop_id'=>$shop['shop_id'],'order_receiving'=>['in','0,1']])->field(['order_receiving','table_name','create_time','id'])->select();
        $data=[];
        foreach ($receiving as $k=>$v){
            if($v['order_receiving']==0){
                $data['call'][]=$v;
            }else{
                $data['accepted '][]=$v;
            }
        }

        return json_encode($data);

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
}