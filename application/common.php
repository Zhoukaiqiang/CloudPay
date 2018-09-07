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

// 应用公共文件
if(!function_exists('encrypt_password')){
    //定义密码加密函数
    function encrypt_password($password){
        //加密方式
        $salt = 'yunshangfu';//自定义字符串
        return md5('$lt'. md5($password) . $salt );
    }
}
/**
 *
 */
if(!function_exists('curl_request')){
    //使用curl函数库发送请求
    function curl_request($url, $post = false, $params = [], $https = false){
        //使用curl_init初始化请求会话
        $ch = curl_init($url);
        //使用curl_setopt设置请求一些选项
        if($post){
            //设置请求方式、请求参数
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if($https){
            //https协议，禁止curl从服务器端验证本地证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //使用curl_exec执行，发送请求
        //设置 让curl_exec 直接返回接口的结果数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        //使用curl_close关闭请求会话
        curl_close($ch);
        return $res;
    }
}
/**
 * 发送短信
 * $phone 电话
 * $msg 验证内容
 */
if(!function_exists('sendmsg')){
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
        if(!$res){
            return '服务器异常，请求发送失败';
        }
        $arr = json_decode($res, true);
        if(isset($arr['code']) && $arr['code'] == 10000){
            //短信发送成功
            return true;
        }else{
            return $arr['msg'];
//            return '短信发送失败';
        }
    }
}
/**
 * 邮箱注册
 */
if(!function_exists('send_mail')){
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
        $mail->Body    = $body;                             //邮件内容
//        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';//纯文本内容

        if(!$mail->send()){
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
if(!function_exists('make_code')){
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
if(!function_exists('return_msg')){
    function return_msg($code, $msg = '', $data = [])
    {
        /* 组合数据 */
        $return_data['code'] = $code;
        $return_data['msg'] = $msg;
        $return_data['data'] = $data;
        /* ---------返回信息并终止脚本---------- */

        echo json_encode($return_data);die;
    }
}

/**
 * 验证请求是否超时
 * @param [int] $code [结果码 200：正常/4**数据问题/5**服务器问题]]
 * @param [string] $msg [接口码要返回的提示信息]
 * @param [array] $data [接口要返回的数据]
 *
 */
if(!function_exists('check_time')) {
    function check_time()
    {
        $time=time();
        session('check_time',$time);
        if((time()-session('check_time')) < 0.0001){
            return true;
        }else{
            return false;
        }
    }
}

