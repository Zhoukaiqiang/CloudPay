<?php

namespace app\admin\controller;

use app\admin\model\TotalAd;
use app\admin\controller\Admin;
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
            $this->return_msg(200, 'success！', $res);
        }else {
            $this->return_msg(400, 'fail');
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
                $insertData = array(
                    'url' => $url,
                    "admin_id" => $param['admin_id'],
                    "agent_id" => $param['agent_id'],
                );
                $result = TotalAd::create($insertData);
                if ($result) {
                    $id = TotalAd::where(['url' => $url])->value('id');
                    $data_arr = ['id' => $id, 'url' => $url];
                    $this->return_msg(200, '图片上传成功', $data_arr);
                } else {
                    $this->return_msg(400, '图片上传失败');

                }

            } else {
                // 上传失败获取错误信息
                $this->return_msg(400, '上传失败', $file->getError());
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
            return $this->return_msg(200,'删除成功', $result);
        } else {
            return $this->return_msg(400,'删除失败');

        }
    }

}
