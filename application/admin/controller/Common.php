<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Common extends Controller
{
    protected $request;
    protected $validater;
    protected $params;

    protected $rules = array(
        'User' => array(
            'login' => array(
                ['phone','require|length:11|/^1[345678]{1}\d{9}$/','请填写电话号码|手机号为11位|手机号不正确'],
                ['password','require|max:6','请填写密码|密码最多不能超过6位'],
                //'user_name' => ['require'],
            ),
            'index' => [
                'id' => ['require', 'number'],
                'phone' => ['require', 'chsDash', 'max' => 10],
            ],
            'register' => [
                'phone' => 'require',
                'password' => 'require|length:32',
                'code' => 'require|number|length:6'
            ],
            'changepwd' => [
                'phone' => 'require',
                'password' => 'require|length:32',
                'ini_pwd' => 'require|length:32',

            ],
            'findpwd' => [
                'phone' => 'require',
                'password' => 'require|length:32',
                'code' => 'require|length:6|number',

            ],
            'bind_phone' => [
                'user_id' => 'require|number',
                'phone' => ['require', 'regex' => '/^1[34578]\d{9}$/'],
                'code' => 'require|length:6|number',

            ],
            'bind_email' => [
                'user_id' => 'require|number',
                'email' => ['require', 'email'],
                'code' => 'require|length:6|number',

            ],
            'addstaff' => [
                ['name','require|max:25','请填写名称|名称最多不能超过25个字符'],
                ['phone','require|length:11|/^1[345678]{1}\d{9}$/','请填写电话号码|手机号为11位|手机号不正确'],
                ['password','require|max:6','请填写密码|密码最多不能超过6位'],
                ['status', 'require', '请选择状态']
            ],
            'test' => [],
        ),
        'Advertise' => [
            'index' => [],
            'delete'=> [
                'id' => 'require'
            ],
        ],
        'Code' => [
            'get_code' => [
                'user_type' => 'require',
                'is_exist' => 'require|number|length:1',
            ],
            'get_code_by_username' => [],
            'send_code_to_phone' => [],

        ]

    );


    protected function _initialize()
    {
        parent::_initialize();
        $this->params = Request::instance()->param();

        //验证时间戳
        //$this->check_time($this->request->only(['time']));
        //验证token
        //$this->check_token($this->request->param());
        //$this->check_username($this->params['phone']);

        $this->params['token'] = $this->check_params($this->request->except(['time', 'token']));
    }

    /**
     *
     * 验证是否请求超时
     * @param  [array] $arr [包含时间戳的参数数组]
     * @return [json]       [检测结果]
     */
    public function check_time($arr)
    {
        if (!isset($arr['time']) || intval($arr['time'] <= 1)) {
            $this->return_msg(400, '时间戳不正确！！');
        }
        if (time() - intval($arr['time']) > 120) {
            $this->return_msg(400, '请求超时');
        }

    }

    /**
     *  验证token是否正确
     * @param [array] $arr 所有参数
     * @return [json] token 验证结果
     */
    public function check_token($arr)
    {
        /* api 传过来的token */
        if (!isset($arr['token']) || empty($arr['token'])) {
            $this->return_msg(400, 'token不能为空！');
        }

        $app_token = $arr['token'];
        /* 服务器端生成token */
        unset($arr['token']);
        $service_token = '';

        foreach ($arr as $k => $v) {
            $service_token .= md5($v);
        }
        $service_token = md5("api_" . $service_token . '_api');

        if ($app_token !== $service_token) {
            $this->return_msg(400, 'token值不正确！');
        }

    }

    /**
     *  验证参数是否正确
     * @param   [array] $arr 所有参数
     * @return [json] 参数验证结果/返回参数
     */
    public function check_params($arr)
    {
        /* 获取参数的验证规则 */
        try {
            $rule = $this->rules[$this->request->controller()][$this->request->action()];

        }catch (Exception $e) {return true;}

        /* 验证参数并返回错误 */
        $this->validater = new Validate($rule);
        if (!$this->validater->check($arr)) {
            $this->return_msg(400, $this->validater->getError());
        }

        return $arr;
    }

    /**
     * 检测用户名并返回用户名类型
     * @param [string] $username [用户名，可能是邮箱，可能是手机号]
     * @return [string]          [检测结果]
     *
     */
    public function check_username($phone)
    {

//        $result = Db::table('cloud_total_admin')->where('phone', $phone)->find();
//
//        if (!$result) {
//            return $this->return_msg(400, '用户不存在！');
//        } else {
//            $name = Db::table('cloud_total_admin')->where('phone', $phone)->value('name');
//            if ($name) {return 'phone'; }
//        }


        /* 判断是否为邮箱 */
        $is_email = Validate::is($phone, 'email') ? 1 : 0;
        /* 判断是否为手机  */
        $is_phone = preg_match('/^1[34578]\d{9}$/', $phone) ? 4 : 2;
        $flag = $is_email + $is_phone;
        switch ($flag) {
            /* not phone not email */
            case 2:
                $this->return_msg(400, '邮箱或手机号不正确!' );
                break;
            /* is email not phone */
            case 3:
                return 'email';
                break;
            case 4:
                return 'phone';
                break;

        }

    }

    public function check_exist($value, $type = 'phone', $exist)
    {
        $type_num = $type == 'phone' ? 2 : 4;
        $flag = $type_num + $exist;
        $phone_res = Db("total_admin")->where("phone", $value)->find();
//        $email_res = db("total_admin")->where("email", $value)->find();
        $email_res = 0;
        switch ($flag) {
            /* 2+0 phone need no exist */
            case 2:
                if ($phone_res) {
                    $this->return_msg(400, '手机号已经被占用');
                }
                break;
            /*  2+1 phone need exist */
            case 3:
                if (!$phone_res) {
                    $this->return_msg(400, '手机号不存在！');
                }
                break;
            /* 4+0 email need no exist */
            case 4:
                if ($email_res) {
                    $this->return_msg(400, '此邮箱已经被占用！');
                }
                break;
            /* 4+1 email need exist */
            case 5:
                if (!$email_res) {
                    $this->return_msg(400, '此邮箱不存在！');
                }
                break;

        }

    }

    /**
     * @param string $user_name [用户名]
     * @param int $code [验证码]
     */
    public function check_code($user_name, $code)
    {
        /* 检测是否超时 创建session */
        $last_time = session($user_name . '_last_send_time');

        if (time() - $last_time > 60) {
            $this->return_msg(400, '验证超时，请在1分钟内验证！');
        }

        /* 检测验证码是否正确 */
        $md5_code = md5($user_name . '_' . md5($code));

        if (session($user_name . "_code") !== $md5_code) {
            $this->return_msg(400, '验证码不正确！');
        }

        /*不管正确与否，每个验证只验证一次*/
        session($user_name . '_code', null);
    }

    /**
     * api 数据返回
     * @param [int] $code [结果码 200：正常/4**数据问题/5**服务器问题]]
     * @param [string] $msg [接口码要返回的提示信息]
     * @param [array] $data [接口要返回的数据]
     *
     */
    public function return_msg($code, $msg = '', $data = [])
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
