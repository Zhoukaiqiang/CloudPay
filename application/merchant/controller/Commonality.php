<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/16
 * Time: 17:36
 */

namespace app\merchant\controller;

use think\Session;

class Commonality extends Common
{

    protected $id;
    protected $role;
    protected $name;
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        /** 检验并赋值给变量 */
        //role 1服务员 2店长 3收银员 -1商户

        if (Session::has("username_","app")) {

                $this->id = Session::get("username_","app")["id"];
                $this->role=Session::get("username_","app")["role"];
            if($this->role==-1){
                $this->name='merchant_id';
            }elseif($this->role==2) {
                $this->name = 'shop_id';
            }elseif ($this->role==3){
                $this->naem='person_info_id';
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

    /**
     * 裁剪图片为正方形 200*200
     */

    public function tailor_img($file, $width = 200, $height = 200)
    {

        $image = \think\Image::open($file);
//                var_dump($image);die;

        $type = $image->type();


        //判断是否是图片格式
        if (in_array($type, ['jpg', 'jpeg', 'png'])) {
            $date_path = 'uploads/thumb/' . date('Ymd');

            if (!file_exists($date_path)) {
                mkdir($date_path, 0777, true);
            }
            list($usec, $sec) = explode(" ", microtime());
            $times = str_replace('.', '', $usec + $sec);

            $thumb_path = $date_path . '/' . $times . '.' . $type;

            $image->thumb($width, $height, \think\Image::THUMB_CENTER)->save($thumb_path);
            return $thumb_path;
        } else {
            return 0;
        }

    }




    /**
     * 流水号
     * @return string
     */
    public function runningWater()
    {
        list($usec, $sec) = explode(" ", microtime());
        $times=str_replace('.','',$usec + $sec);
        //當前時間
        return time().$times;
    }

    /**
     * Notes:生成二维码地址
     * User: guoyang
     * DATE: 2018/10/25
     * @param null $url 二维码地址
     * @param null $shop_id
     * @param null $name
     */
    public function qrcode($url=null, $shop_id=null, $name = null)
    {
        $merchant_id = $this->merchant_id;
        $url=$url ? $url : "http://api.hzyspay.com/merchant/Ordermeals/returntime?shop_id=$shop_id&name=$name&merchant_id=$merchant_id";
        header("content-type:text/html;charset=utf-8");
        Vendor('phpqrcode.phpqrcode');  //引入的phpqrcode类
        import('phpqrcode.phpqrcode', EXTEND_PATH,'.php');
        $path = "/uploads/QRcode/".date("Ymd").DS;//创建路径

        $time = time().'.png'; //创建文件名

        $file_name = iconv("utf-8","gb2312",$time);

        $file_path = $_SERVER['DOCUMENT_ROOT'].$path;

        if(!file_exists($file_path)){
            mkdir($file_path, 0700,true);//创建目录
        }
        $file_path = "/uploads/".$this->runningWater().'.png';//1.命名生成的二维码文件
        $level = 'L';  //3.纠错级别：L、M、Q、H
        $size = 4;//4.点的大小：1到10,用于手机端4就可以了
        ob_end_clean();//清空缓冲区
        //生成二维码-保存：
        \QRcode::png($url, $file_path, $level, $size);

        return_msg(200,'success',$file_path);

    }
}
