<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;

// 应用公共文件
if (!function_exists('encrypt_password')) {
    /**
     * 密码加密
     * @param [sting] $password [加密前的密码]
     * @param [string] $val 用户手机号
     * @return [string] [加密后的密码]
     *
     */
    function encrypt_password($password, $phone = '')
    {

        return md5('$ysf' . md5($password) . $phone);
    }
}
/**
 * 发送短信
 * $phone 电话
 * $msg 验证内容
 */
if (!function_exists('sendmsg')) {
    //发送短信
    function sendmsg($phone, $msg)
    {
        //获取接口地址
        $gateway = config('msg.gateway');
        $appkey = config('msg.appkey');
        //拼接url  发送get请求
        $url = $gateway . '?appkey=' . $appkey . '&mobile=' . $phone . '&content=' . $msg;
        //发送请求
        $res = curl_request($url, false, [], true);
        //解析返回结果
        if (!$res) {
            return '服务器异常，请求发送失败';
        }
        $arr = json_decode($res, true);
        if (isset($arr['code']) && $arr['code'] == 10000) {
            //短信发送成功
            return true;
        } else {
            return $arr['msg'];
//            return '短信发送失败';
        }
    }
}
/**
 * 邮箱注册
 */
if (!function_exists('send_mail')) {
    //使用PHPMailer发送邮件
    function send_mail($email, $subject, $body)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();        //传参数true，表示使用异常处理机制
        $mail->isSMTP();                                      // 设置使用SMTP协议（服务）
        $mail->Host = config('email.host');          // 邮件服务器地址
        $mail->SMTPAuth = true;                               // 开启SMTP认证
        $mail->Username = config('email.username');                 // 邮箱用户名
        $mail->Password = config('email.password');                // 授权码
        $mail->SMTPSecure = 'tls';                            // 加密方式  tls  ssl
        $mail->Port = 25;                                    // 发送邮件端口
        $mail->CharSet = 'UTF-8';
        //Recipients
        $mail->setFrom(config('email.username'));       //发件人，第二个参数可选，表示昵称
        $mail->addAddress($email);                      // 收件人，第二个参数可选，表示昵称
        $mail->isHTML(true);                                  // 邮件内容是html格式
        $mail->Subject = $subject;                             //邮件主题
        $mail->Body = $body;                             //邮件内容
//        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';//纯文本内容

        if (!$mail->send()) {
            //发送失败
            return $mail->ErrorInfo;
        }
        //发送成功
        return true;
    }
}

/**
 * 生成验证码
 * @param [int] [验证码的位数]
 * @return [int]   [生成的验证码]
 *
 *
 */
if (!function_exists('make_code')) {
    function make_code($num)
    {
        $max = pow(10, $num) - 1;
        $min = pow(10, $num - 1);
        return rand($min, $max);
    }
}

/**
 * api 数据返回
 * @param [int] $code [结果码 200：正常/4**数据问题/5**服务器问题]]
 * @param [string] $msg [接口码要返回的提示信息]
 * @param [array] $data [接口要返回的数据]
 *
 */
if (!function_exists('return_msg')) {
    function return_msg($code, $msg = '', $data = [])
    {
        /* 组合数据 */
        $return_data['code'] = $code;
        $return_data['msg'] = $msg;
        $return_data['data'] = $data;
        /* ---------返回信息并终止脚本---------- */

        echo json_encode($return_data);
        die;
    }
}

/**
 * 验证请求是否超时
 * @param [int] $code [结果码 200：正常/4**数据问题/5**服务器问题]]
 * @param [string] $msg [接口码要返回的提示信息]
 * @param [array] $data [接口要返回的数据]
 *
 */
if (!function_exists('check_time')) {
    function check_time()
    {
        $time = time();
        session('check_time', $time);
        if ((time() - session('check_time')) < 0.0001) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 *  app支付宝请求头
 */
if (!function_exists('jsonReturn')) {
    function jsonReturn($status = 0, $code = 0, $data = '', $msg = '', $type = 1)
    {

        $json_arr = array('status' => $status, 'code' => $code);
        if (!empty($msg)) {
            $json_arr['msg'] = $msg;
        }
        if (!empty($data)) {
            $json_arr['data'] = $data;
        }
        header('Content-Type:application/json; charset=utf-8');
        if ($type == 1) {
            exit(json_encode($json_arr));
        } else if ($type == 2) {
            return json_encode($json_arr);
        }

    }
}

/**
 * 分页
 * $page 当前页
 * $rows 总行数
 * $limit 每页显示的记录数
 */
if (!function_exists('page')) {
    function page($rows, $limit = 5)
    {
        $page = request()->param('page') ? request()->param('page') : 1;
        //获取总页数
        $pageCount = ceil($rows / $limit);
        //偏移量
        $offset = ($page - 1) * $limit;
        //上一页
        $pagePrev = $page - 1;
        if ($pagePrev <= 1) {
            $pagePrev = 1;
        }
        //下一页
        $pageNext = $page + 1;
        if ($pageNext >= $pageCount) {
            $pageNext = $pageCount;
        }
        $data['pageCount'] = $pageCount;
        $data['offset'] = $offset;
        $data['pagePrev'] = $pagePrev;
        $data['pageNext'] = $pageNext;
        $data['limit'] = $limit;
        $data['rows'] = $rows;

        return $data;
    }
}

/**
 * 参数验证
 * 验证规则写在Common模块 1--AdminValidate 2--MerchantValidate 3--AgentValidate
 */
if (!function_exists("check_params")) {
    function check_params($scene, $param, $validate = "AdminValidate")
    {
        $validate = \think\Loader::validate($validate);
        $check = $validate->scene($scene)->check($param);
        if (!$check) {
            return_msg(400, $validate->getError());
        }
    }
}

if (!function_exists("generate_order_no")) {
    /**
     * 生成订单号 规则:  /[日期+用户ID+3位随机码]/
     * @param $uid [int] 用户ID
     * @return [string] 订单号码
     */
    function generate_order_no($uid = null)
    {

        if(!$uid) {$uid = rand(100,999);}
        $order_num = (string)date("YmdHms") + (string)$uid + rand(100, 999);
        return $order_num;

    }
}

if (!function_exists("getSN")) {
    /**
     * 生成SN码 规则:  /[时间戳+4位随机码]/
     * @param $uid [int]
     * @return [string]
     */
    function getSN()
    {
        $order_num = time() . rand(1000, 9999);
        return $order_num;
    }
}
/**
 * 设置curl
 */
if (!function_exists('curl_request')) {
    //使用curl函数库发送请求
    function curl_request($url, $post = false, $params = [], $https = false)
    {
        $params = json_encode($params);
        /*$params=json_encode($params);
        return $params;*/
        //①使用curl_init初始化请求会话
        $ch = curl_init();
        //②使用curl_setopt设置请求一些选项

        //测试地址 http://sandbox.starpos.com.cn/emercapp
        //正式地址 https://gateway.starpos.com.cn/emercapp
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post) {
            //设置请求方式、请求参数
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array("application/json;charset=GBK", "Content-length:" . strlen($params)));
        }
        if ($https) {
            //https协议，禁止curl从服务器端验证本地证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //③使用curl_exec执行，发送请求
        //设置 让curl_exec 直接返回接口的结果数据
        $res = curl_exec($ch);
        //④使用curl_close关闭请求会话
        $res = iconv('GBK', 'UTF-8', $res);
        curl_close($ch);
        return $res;
    }
}

/**
 * 检查用户是否有权限查看
 * @param id [int] 用户id
 * @rule  is_super_vip [int] 1:超级管理员 2：运营专员 ...
 * @return    [boolean]  返回true / 结束
 */
function is_user_can($id)
{
    /* 检查用户是否存在数据库 */
    $result = Db::name("total_admin")->where("id", "eq", 1)->value('is_super_vip');

    switch ($result) {
        case 1:
            return true;
            break;
        case 0:
            return false;
            break;
        default:
            return_msg(400, '当前用户不存在！');
            break;
    }
}

if (!function_exists('sign_ature')) {
    function sign_ature($ids, Array $arr)
    {
//        dump($arr);die;
        ksort($arr);
        if ($ids == 0000) {
            $data = ['serviceId', 'version', 'incom_type', 'stl_typ', 'stl_sign', 'stl_oac', 'bnk_acnm', 'wc_lbnk_no', 'bus_lic_no', 'bse_lice_nm', 'crp_nm', 'mercAdds', 'bus_exp_dt', 'crp_id_no', 'crp_exp_dt', 'stoe_nm', 'stoe_cnt_nm', 'stoe_cnt_tel', 'mcc_cd', 'stoe_area_cod', 'stoe_adds', 'trm_rec', 'mailbox', 'alipay_flg', 'yhkpay_flg', 'mercId', 'orgNo', 'imgTyp', 'imgNm', 'log_no', 'stoe_id', 'lbnk_nm'];
            $stra = '';

            foreach ($arr as $k => $v) {
                if (in_array($k, $data)) {
//                    echo $k."<br/>";
                    $stra .= $v;
                }
            }
//            var_dump($stra);die;
        } else if ($ids == 1111) {
            $data = ['check_flag', 'msg_cd', 'msg_dat', 'mercId', 'log_no', 'stoe_id', 'mobile', 'sign_stats', 'deliv_stats'];
            $stra = '';
            foreach ($arr as $key1 => $val) {
                if (in_array($key1, $data)) {
                    $stra .= $val;
                }
            }

        }

        return md5($stra . KEY);
    }
}

//批量上传图片
if (!function_exists('upload_logo')) {
    function upload_pics($files)
    {
        $goods_pics = [];
        foreach ($files as $file) {
            $info = $file->validate(['size' => 500 * 1024 * 1024, 'ext' => 'jpg,jpeg,gif,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                //图片上传成功
                $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
                $goods_logo = str_replace('\\', '/', $goods_logo);
                $goods_pics[] = $goods_logo;
            } else {
                $error = $info->getError();
                return_msg(400, $error);
            }
        }
        return $goods_pics;
    }
}

//图片上传
if (!function_exists('upload_pics')) {
    function upload_pics($file)
    {
        //移动图片
        $info = $file->validate(['size' => 5 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');

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

if (!function_exists("check_time")) {
    /**
     *
     * 验证是否请求超时
     * @param  [array] $arr [包含时间戳的参数数组]
     * @return [json]       [检测结果]
     */
    function check_time($arr)
    {
        if (!isset($arr['time']) || intval($arr['time'] <= 1)) {
            $this->return_msg(400, '时间戳不正确！！');
        }
        if (time() - intval($arr['time']) > 120) {
            $this->return_msg(400, '请求超时');
        }

    }
}

/**
 * 发送短信到手机
 * @param $phone
 * @param $msg
 */
function send_msg_to_phone($phone, $msg)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend.json');
    //curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //post数据
    curl_setopt($curl, CURLOPT_POST, 1);
    //配置submail
    $data = [
        'appid' => '27075', //应用id
        'to' => $phone,     //要接受短信的电话
        'project' => 'Jaayb', //模板标识
        'vars' => "{'code': '" . $msg . "'}",
        'signature' => '5ac305ef38fb126d2a0ec5304040ab7d', //应用签名
    ];

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($curl);
    curl_close($curl);
    $res = json_decode($res);
    if ($res->status !== 'success') {
        return false;
    } else {
        return true;
    }

}

/**
 * 图片缩略图
 * @param Request $request
 * @return string
 */
if (!function_exists('image_thumbnail')) {

    function image_thumbnail($file, $width = 150, $height = 150)
    {

        $image = \think\Image::open($file);
//                var_dump($image);die;

        $type = $image->type();

        //判断是否是图片格式
        if (in_array($type, ['jpg', 'png'])) {
            $date_path = 'uploads/thumb/' . date('Ymd');

            if (!file_exists($date_path)) {
                mkdir($date_path, 0777, true);
            }
            list($usec, $sec) = explode(" ", microtime());
            $times = str_replace('.', '', $usec + $sec);

            $thumb_path = $date_path . '/' . $times . '.' . $type;

            $image->thumb($width, $height)->save($thumb_path);
            return $thumb_path;
        } else {
            return 9;
        }

    }
}


if (!function_exists('tailor_img')) {

    function tailor_img($file, $width = 200, $height = 200)
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
<<<<<<< Updated upstream

}
=======
}
>>>>>>> Stashed changes
