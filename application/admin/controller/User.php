<?php
/**
 * Created by KaiQiang-use by PhpStorm.
 * User: fennu
 * Date: 2018/9/4
 * Time: 16:35
 */

namespace app\index\controller;


use app\index\model\TotalAd;
use think\Db;
use think\Controller;
use think\Request;

/**
 * Class User
 * @package app\index\controller
 */
class User extends Common
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->request = Request::instance();
    }


    /**
     * 用户登录
     * @return mixed
     */
    public function login()
    {

        $data = $this->params;
        $user_name_type = 'phone';
        $this->check_exist($data['phone'], 'phone', 1);
        $db_res = db('total_admin')->field('id,name,phone,status,password,is_super_vip')
            ->where('phone', $data['phone'])->find();

        if ($db_res['password'] !== $data['password']) {
            $this->return_msg(400, '用户密码不正确！');
        }else {
            unset($db_res['password']);  //密码不返回
            $this->return_msg(200, '登录成功！', $db_res);
        }
    }

    /**
     * 注册方法
     */
    public function register()
    {
        /* 接受参数 */
        $data = $this->request->param();
        dump($data['code']);die;
        $this->check_code($data['phone'], $data['code']);
        /* 检测用户名 */

//        $user_type = $this->check_username($data['user_name']);
        $user_type = 'phone';
        switch ($user_type) {
            case 'phone':
                $this->check_exist($data['phone'], 'phone', 0);
                break;
            case 'email':
                $this->check_exist($data['user_name'], 'phone', 0);
                $data['user_email'] = $data['user_name'];
                break;

        }
        /** 将用户信息写入数据库 **/
        //unset($data['phone']);
        $d['create_time'] = time();
        $d['name'] = $data['phone'];
        $d['password'] = $this->encrypt_password($data['password']);
        $res = db('total_admin')->insert($d);
        if (!$res) {
            $this->return_msg(400, '用户注册失败!');
        }else {
            /*注册成功发送密码到用户手机*/

            $this->return_msg(200, '用户注册成功！', $res);

        }
    }




    /**
     * 总后台 - 广告图片上传处理
     * @param [file]  image
     * @return [json]  成功或者失败的消息
     */
    // 图片上传处理
    public function img_upload()
    {

        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        //校验器，判断图片格式是否正确
        if (true !== $this->validate(['image' => $file], ['image' => 'require|image'])) {
            $this->error('请选择图像文件');
        } else {
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                //存入相对路径/upload/日期/文件名
                $data = DS . 'uploads' . DS . $info->getSaveName();
                $url = str_replace('\\', '/', $data);
                $insertData = array(
                    'url' => $url,
                );
                $result = TotalAd::create($insertData);
                if ($result) {
                    $id = TotalAd::where(['url' => $url])->value('id');
                    $data_arr = ['id' => $id, 'url' => $url];
                    Common::return_msg(200, '图片上传成功', $data_arr);
                } else {
                    Common::return_msg(400, '图片上传失败');

                }


                //模板变量赋值
                // $this->assign('image', $data);
                // return $this->fetch('index');
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    /**
     * 删除图片
     * @param [id]  图片;
     */
    public function deleteAd($id)
    {
        $result = TotalAd::destroy($id);
        if ($result) {
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败', 'login');
        }
    }

    public function addStaff()
    {

        $data['name'] = $this->request->param('name');
        $this->check_phone($this->request->param('phone'));
        $data['phone'] = $this->request->param('phone');
        $data['status'] = $this->request->param('status');
        $data['create_time'] = date('Y-m-d H:m:s');
        $data['password'] = $this->request->param('password');

        $result = Db::table('cloud_total_admin')->insertGetId($data);
        if (!$result) {
            $this->return_msg(400, '插入新成员失败!');
        } else {
            $this->return_msg(200, '插入新成员成功！', $result);
        }

    }

    public function check_phone($phone)
    {
        $result = Db::table('cloud_total_admin')->where('phone', $phone)->find();
        if ($result) {
            return $this->return_msg(400, '用户已存在！');
        }
    }

    public function delStaff($id)
    {
        if (empty($id)) {
            return;
        }
        $result = Db::table('cloud_total_admin')->delete($id);
        if ($result) {
            $this->return_msg(200, '删除成功');
        } else {
            $this->return_msg(400, '删除失败');
        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editStaff()
    {
        $id = $this->request->param('id');
        if (empty($id)) return;
        $data['name'] = $this->request->param('name');

        $data['phone'] = $this->request->param('phone');
        $data['status'] = $this->request->param('status');
        $data['create_time'] = date('Y-m-d H:m:s');
        $data['password'] = $this->request->param('password');

        $result = Db::table('cloud_total_admin')->where('id', $id)->update($data);
        if (!$result) {
            $this->return_msg(400, '修改新成员失败!');
        } else {
            $this->return_msg(200, '修改新成员成功！', $result);
        }

    }

    public function test() {
       //获取当前模块名字
        dump($this->request->header());
    }

}