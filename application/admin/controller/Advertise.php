<?php

namespace app\admin\controller;

use app\admin\model\TotalAd;
use app\admin\controller\Admin;
use think\Controller;
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
        $res = Db('total_ad')->order('id', 'DESC')->find();

        if ($res) {
           return_msg(200, 'success！', $res);
        }else {
           return_msg(400, 'fail');
        }
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
                    $res = Db::name("total_ad")->where($result)->find();
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
     * 删除广告
     * @param [id]  图片;
     * @return [json] 返回信息
     */
    public function delete(Request $request)
    {
        /* 删除指定id图片 */
        $result = Db('total_ad')->delete($request->param('id'));
        if ($result) {
            return_msg(200,'删除成功', $result);
        } else {
           return_msg(400,'删除失败');

        }
    }

}
