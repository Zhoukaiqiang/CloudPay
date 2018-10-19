<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/16
 * Time: 17:36
 */

namespace app\merchant\controller;


use think\Controller;
use think\Session;

class Commonality extends Controller
{

    protected $id;
    protected $role;
    protected $name;
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        /** 检验并赋值给变量 */

        if (Session::has("username_","app")) {

                $this->id = Session::get("username_","app")["id"];
                $this->role=Session::get("username_","app")["role"];
            if($this->role==-1){
                $this->name='merchant_id';
            }elseif($this->role==2){
                $this->name='shop_id';
            }else{
                $this->name='user_id';
            }

        }else {
            return_msg(400, "请登录");
        }
    }


    /**
     * 图片上传
     * @param $file
     * @return mixed|string
     */

    function upload_picspay($file)
    {
        //移动图片
        $info = $file->validate(['size' => 500 * 1024 * 1024, 'ext' => 'jpg,jpeg,gif,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        if ($info) {
            //文件上传成功,生成缩略图
            //获取文件路径
            $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
            $goods_logo = str_replace('\\', '/', $goods_logo);
            return $goods_logo;
        } else {
            $error = $file->getError();
            $this->error($error);
        }
    }
}
