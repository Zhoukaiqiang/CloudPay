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
     * 菜品设置  点菜
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
}