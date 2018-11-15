<?php

namespace app\admin\controller;

use app\admin\model\TotalAd;
use app\admin\controller\Admin;
use think\Controller;
use think\File;
use think\Request;
use think\Db;

class Advertise extends Admin
{
    /**
     * 显示广告
     *
     * @return \think\Response
     */

    public function index()
    {
        /* 获取最新的一则广告  */
        $res = Db('total_ad')->where("admin_id <> 0")->order('id', 'DESC')->find();
        check_data($res);
    }

    /**
     * 总后台 - 广告上传处理
     * @param [file]  image
     * @return [json]  成功或者失败的消息
     * @param  [int] admin_id  /  agent_id  管理员ID / 代理商ID
     */
    // 图片上传处理
    public function upload(Request $request)
    {
//        $check_exist = Db::name("total_ad")->count("id");
//        if ($check_exist) {
//            return_msg(400, "请先删除广告！");
//        }
        $param = $request->param();
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('image');
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
                $insertData = $param;
                $insertData["url"] = $url;
                $result = Db::name("total_ad")->insertGetId($insertData);

                if ($result) {
                    $res = Db::name("total_ad")->where("id = $result")->find();
                    return_msg(200, '图片上传成功', $res);
                } else {
                    return_msg(400, '图片上传失败');
                }
            } else {
                // 上传失败获取错误信息
                return_msg(400, '上传失败', $file->getError());
            }
        }
    }


    /**
     * 广告曝光次数 --- 扫码进入页面次数+1
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adSetInc(Request $request)
    {
        if ($request->isPost()) {
            $msg = Session::get("username_", "app");
            if ($msg["role"] !== -1) {
                $mid = Db::name("merchant_user")->where("id",$msg["id"])->find()["id"];
            }else {
                $mid = $msg["id"];
            }
            $state = Db::name("total_merchant")->where("id",$mid)->find()["bg"];
            if ($state) {
                $res = Db::name("total_merchant")->where("id",$mid)->setInc("bg",1);
            }else {
                $res = Db::name("total_merchant")->where("id",$mid)->setField("bg",1);
            }

            check_data($res);
        }
    }


    /**
     * 获取广告曝光
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBg(Request $request)
    {
        if ($request->isGet()) {
            $res = Db::name("total_agent")->field("agent_name,bg, agent_area")->find();
            check_data($res);
        }
    }
    /**
     * 删除广告
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        /* 删除指定id图片 */
        $id = $request->param("id");

        $result = Db::name('total_ad')->where("id = $id")->find();
        $path = DS . 'uploads' . DS .$result["url"];
        if (file_exists( $path )) {
            unlink($path);//删除文件
        };
        $res = Db::name("total_ad")->where("id = $id")->delete();
        if ($res) {
            return_msg(200, "删除成功");
        }else {
            return_msg(400, "删除失败");
        }
    }

}
