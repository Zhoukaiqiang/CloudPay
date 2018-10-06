<?php

namespace app\merchant\controller;


use app\merchant\model\MerchantDish;
use app\merchant\model\MerchantDishNorm;
use app\merchant\model\ShopComestible;
use app\merchant\model\ShopTable;
use think\Controller;
use think\Request;

class Service extends Controller
{
    /**
     * 选择桌位
     *
     * @return \think\Response
     */
    public function select_table()
    {
        //选择桌位
        $data=ShopTable::field('id,name,img')->select();
        return_msg(200,'success',$data);
    }

    /**
     * 桌位名称搜索
     *
     * @return \think\Response
     */
    public function search(Request $request)
    {
        $table=$request->param('table');
        $info=ShopTable::field('id,name,img')->where('name','like',$table."%")->select();
        return_msg(200,'success',$info);
    }

    /**
     * 菜品列表
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function food_list(Request $request)
    {
        //获取菜品分类名和桌号id
        $data=$request->param();
        //根据菜品名字取出菜品id
        $norm_id=MerchantDishNorm::field('id')->where('dish_norm',$data['dish_norm'])->find();
        //取出所有菜品属性信息
        $info=MerchantDish::field('id,dish_name,dish_describe,dish_img,money')->where('norm_id',$norm_id)->select();
    }

    /**
     * 菜品搜索
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function dish_search(Request $request)
    {
        $dish_name=$request->param('dish_name');
        $data=MerchantDish::field('id,dish_name,dish_describe,dish_img,money')->where('dish_name','like',$dish_name."%")->select();
        return_msg(200,'success',$data);
    }

    /**
     * 点菜
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function point_food(Request $request)
    {
        //获取菜品信息
        $dish=$request->param();
        $dish['create_time']=time();
        //加入临时表
        $insert=ShopComestible::insert($dish,true);
        if($insert){
            return_msg(200,'success');
        }else{
            return_msg(400,'failure');
        }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
