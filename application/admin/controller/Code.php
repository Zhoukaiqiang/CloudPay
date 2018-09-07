<?php
namespace app\admin\controller;
use PHPMailer\PHPMailer;


class Code extends Common
{

    public function get_code()
    {

        $user_type = $this->params['user_type'];
        $exist = $this->params['is_exist'];


        $username_type = $this->check_username($user_type);

        //$username_type = 'phone';

        switch ($username_type) {
            case "phone":
                $this->get_code_by_username($user_type, 'phone', $exist);
                break;
            case "email":
                $this->get_code_by_username($user_type, 'email', $exist);
                break;
        }

    }

    /**
     * 通过手机获取验证码
     * @param [string] $phone 手机号/邮箱
     * @param [int]   $exist [手机号/邮箱是否应该存在于数据库]
     * @return [json] [api返回的json数据]
     *
     */

    public function get_code_by_username($username, $type, $exist)
    {

        if ($type == 'phone') {
            $type_name = '手机';
        } else {
            $type_name = '邮箱';
        }
        /* 检测手机号是否存在 */
        $this->check_exist($username, $type, $exist);
        /* 加测验证码请求频率 30秒一次 */
        if (session("?", $username . '_last_send_time')) {
            if (time() - session($username . '_last_send_time') < 30) {
                $this->return_msg(400, $type_name . '验证码，每30秒只能发送一次');
            }
        }
        /* 生成验证码 */
        $code = $this->make_code(6);
        /* 使用session存储验证码， 方便对比。 */
        $md5_code = md5($username . '_' . md5($code));
        session($username . '_code', $md5_code);
        /* 使用session存储验证码的发送时间 */
        session($username . '_last_send_time', time());
        /* 发送验证码 */
        if ($type == 'phone') {

            $this->send_code_to_phone($username, $code);

        } else {

            $this->send_code_to_email($username, $code);
        }
    }

    /**
     * 发送邮件验证码
     * @param $email
     * @param $code
     * @throws \PHPMailer\Exception
     */
    public function send_code_to_email($email, $code) {
        $toemail = $email;
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->CharSet = 'utf-8';
        $mail->Host = 'smtp.163.com';
        $mail->SMTPAuth = true;
        $mail->Username =  'a169kai@163.com';
        $mail->Password = 'zkq2552907';
        $mail->SMTPSecure= "ssl";
        $mail->Port = 994;
        $mail->setFrom('a169kai@163.com', 'test api');
        $mail->addAddress($toemail,'test');
        $mail->addReplyTo('a169kai@163.com', 'Reply');
        $mail->Subject = "您有新的验证码！";
        $mail->Body = '您的验证码是'.$code.', 验证码有效期为5分钟，请勿回复本邮件。';
        if (!$mail->send()) {
            $this->return_msg(400, $mail->ErrorInfo);
        }else {
            $this->return_msg(200, '邮箱发送成功！');
        }
    }
    /**
     * 手机验证码发送
     * @description Submail api
     * @param $phone
     * @param $code
     * @return [json] 返回成功或者失败的消息
     */
    public function send_code_to_phone($phone, $code)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend.json');
        //curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        $data = [
            'appid' => '27075',
            'to'  => $phone,
            'project' => 'YVMh44',
            'vars'  => '{"code":'. $code .', "time": "300"}',
            'signature' => '5ac305ef38fb126d2a0ec5304040ab7d',
        ];

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($res);
        if($res->status !== 'success') {
          $this->return_msg(400, '手机验证码发送失败！');
        }else {
            $this->return_msg(200, '手机验证码发送成功,每天发送5次，请在5分钟内验证!');
        }

    }



    /**
     * 生成验证码
     * @param [int] [验证码的位数]
     * @return [int]   [生成的验证码]
     *
     *
     */
    public function make_code($num)
    {
        $max = pow(10, $num) - 1;
        $min = pow(10, $num - 1);
        return rand($min, $max);
    }
}
