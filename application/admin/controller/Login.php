<?php

namespace app\admin\controller;

use app\admin\model\TotalAdmin;
use think\Controller;
use think\Request;
use think\Validate;

class Login extends Controller
{
    /**
     * 登录
     * @return
     * $username 用户名
     * $password 密码
     */
    public function login(Request $request,TotalAdmin $user)
    {
        if($request->isPost()){
            $data=$request->post();
            $rule=[
                'name'=>'require',
                'password'=>'require|min:6'
            ];
            $msg=[
                'name.require'=>'用户名必填',
                'password.require'=>'密码必填',
                'password.min'=>'密码至少6位',
            ];
            $validate=new Validate($rule,$msg);
            if($validate->check($data)){
                $where=[
                    'name'=>addslashes($data['name']),
                    'password'=>encrypt_password(addslashes($data['password']))
                ];
                if($info=$user->where($where)->find()){
                    //使用session保存用户信息
                    session('userinfo',$info->toArray());
                    echo '登录成功';die;
                    $this->redirect('admin/index/index');
                }else{
                    $this->error('用户名或密码错误');
                }
            }else{
                $error=$validate->getError();
                $this->error($error);
            }
        }else{
            $this->view->engine->layout(false);
            //return view();
        }
    }

    /**
     * 注册
     *
     * @return
     * $username 用户名
     * $password 密码
     */
    public function register(Request $request)
    {
        if($request->isPost()){
            $data=$request->post();
            $rule=[
                'username'=>'require|unique:user',
                'password'=>'require|length:6,16|confirm:repassword',
            ];
            $msg=[
                'username.require'=>'用户名必填',
                'username.unique'=>'用户名已经注册',
                'password.require'=>'密码必填',
                'password.length'=>'密码在6-16位之间',
                'password.confirm'=>'两次输入的密码必须一致'
            ];
            $validate=new Validate($rule,$msg);
            if($validate->check($data)){
                if(User::create($data,true)){
                    $this->success('注册成功','user/login/login');
                }else{
                    $this->error('注册失败');
                }
            }else{
                $error=$validate->getError();
                $this->error($error);
            }
        }else{
            return view();
        }
    }

    /**
     * 发送短信接口
     *
     * @param  \think\Request  $code 验证码
     * @param  \think\Request  $phone 手机号
     * @return [json] 返回json数据
     */
    public function send_code(Request $request)
    {
        //获取手机号
        $phone=$request->param('phone');
        //获取验证码
        $code=make_code(6);
        $msg='【云商付】欢迎注册,验证码为'.$code;
        //发送验证码
        $res=sendmsg($phone,$msg);
        $res=true;
        if($res===true){
            //将验证码保存到session中
           session('register_'.$phone,$code);
            $result=['code'=>200,'msg'=>'发送成功','data'=>$code];
            return json_encode($result);
        }else{
            $result=['code'=>300,'msg'=>'发送失败'];
            return json_encode($result);
        }
    }


    /**
     * 手机号注册
     * @param  $code 验证码
     * @return \think\Response
     */
    public function phone(Request $request)
    {
        $data=$request->param();
        $rule=[
            'phone'=>'require|regex:/^1[3-9]\d{9}$/|unique:user',
            'code'=>'require',
            'password'=>'require,confirm:repassword'
        ];
        $msg=[
            'phone.require'=>'手机号必填',
            'phone.regex'=>'手机号格式不正确',
            'phone.unique'=>'手机号已被注册',
            'code.require'=>'验证码必填',
            'password.require'=>'密码必填',
            'password.confirm'=>'两次输入的密码不一致'
        ];
        $validate=new Validate($rule,$msg);
        if($validate->check($data)){
            //在sessoin中取出验证码
             $code=session('register_'.$data['phone']);
             if($data['code']!=$code){
                 $this->error('验证码错误');
             }
             //密码加密
             $data['password']=addslashes(encrypt_password($data['password']));
             User::create($data,true);
             $this->success('注册成功','login');
        }else{
            $error=$validate->getError();
            $this->error($error);
        }
    }
    /**
     * 邮箱注册
     * @param  int  $id
     * @return \think\Response
     */
    public function register_email(Request $request)
    {
        if($request->isPost()){
            $data=$request->param();
            $rule=[
                'email'=>'require|email|unique:user',
                'password'=>'require|confirm:repassword|length:6,16'
            ];
            $msg=[
                'email.require'=>'邮箱必填',
                'email.email'=>'邮箱格式不正确',
                'email.unique'=>'邮箱已被注册',
                'password.require'=>'密码必填',
                'password.confirm'=>'两次输入的密码不一致',
                'password.length'=>'密码长度在6-16位之间'
            ];
            $validate=new Validate($rule,$msg);
            if($validate->check($data)){
                $data['username']=$data['email'];
                //生成验证码
                $data['email_code']=make_code(6);
                //密码加密
                $data['password']=addslashes(encrypt_password($data['password']));
                $info=User::create($data,true);
                $msg="云商付注册邮件";
                //拼接发送邮件的url
                $url=url('user/login/jihuo',['id'=>$info['id'],'code'=>$info['email_code']],true,true);
                $content="欢迎注册云商付，请点击<a href='$url'>这里</a>进行激活";
                //发送邮件
                $res=send_mail($info['email'],$msg,$content);
                if($res===true){
                    $this->success('注册成功','login');
                }else{
                    $this->error('邮件发送失败');
                }
            }else{
                $error=$validate->getError();
                $this->error($error);
            }
        }else{
            return view();
        }
    }

    /**
     * 邮箱激活
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function jihuo(Request $request)
    {
        $data=$request->param();
        $user=User::where(['id'=>$data['id'],'email_code'=>$data['email_code']])->find();
        if(empty($user)){
            $this->error('用户不存在');
        }
        $user->is_check=1;
        $user->save();
        $this->success('激活成功','login');
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
