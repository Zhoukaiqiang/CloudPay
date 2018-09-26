<?php

namespace app\admin\controller;

use app\admin\model\TotalAdmin;
use app\admin\model\TotalAgent;
use think\Controller;
use think\Loader;
use think\Request;
use think\Validate;

class Agent extends Controller
{
    /**
     * 代理商首页
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取总行数
        $rows = TotalAgent::count();
        $pages = page($rows);
        //取出数据表中的数据
        $data['data'] = TotalAgent::alias('a')
            ->field('a.id,a.agent_name,a.contact_person,a.agent_mode,a.agent_area,a.create_time,a.contract_time,a.status,b.name')
            ->join('cloud_total_admin b', 'a.admin_id=b.id', 'left')
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;
        return_msg('200', 'success', $data);
    }

    /**
     * 新增代理商
     * @param  $contract_time 合同有效期
     * @param  $contract_picture 合同图片
     * @return \think\Response
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $data['password']  = make_code(6);
            //发送密码到代理商
            $check_send = send_msg_to_phone($data['phone'], $data['password']);
            if (!$check_send) {
                return_msg(400, "短信发送失败");
            }
            //验证
            $validate = Loader::validate('AdminValidate');
            if ($validate->scene('add')->check($data)) {
                //            $data['contract_time']=strtotime($data['contract_time']);
                //保存到数据表
                $info = TotalAgent::create($data, true);
                if ($info) {
                    //保存成功
                    return_msg('200', '保存成功', $info);
                } else {
                    //保存失败
                    return_msg('400', '保存失败');
                }
            } else {
                $error = $validate->getError();
                return_msg(400, 'failure', $error);
            }
        } else {
            //取出所有人员信息
            $data['data'] = TotalAdmin::field(['id', 'name', 'role_id'])->select();
            //取出所有一级代理商名称
            $info = TotalAgent::where('parent_id', 0)->field('agent_name')->select();
            $data['agent'] = $info;
            return_msg(200, 'success', $data);
        }
    }


    /**
     * 代理商详情.
     *
     * @param  $contract_time 合同有效期
     * @param  $contract_picture 合同图片
     * @return \think\Response
     */
    public function detail(Request $request)
    {
        //
        if (request()->isPost()) {
            $data = $request->post();
            //验证
            $validate = Loader::Validate('AdminValidate');
            if (!$validate->scene('detail')->check($data)) {
                $error = $validate->getError();
                return_msg(400, 'failure', $error);
            }
            //上传图片
            //如果没有上传图片用原来的图片
            $file = $request->file('contract_picture');
            if (!empty($file)) {
                $data['contract_picture'] = $this->upload_logo();
                $data['contract_picture'] = json_encode($data['contract_picture']);
            }
//            $data['contract_time']=strtotime($data['contract_time']);
            //保存到数据表
            $info = TotalAgent::where('id', '=', $data['id'])->update($data, true);
            if ($info) {
                //保存成功
                return_msg('200', '修改成功', $info);
            } else {
                //保存失败
                return_msg('400', '修改失败');
            }
        } else {
            $id = request()->param('id');
            $data = TotalAgent::where('id', $id)->find();

            $pic_list = json_decode($data->toArray()['contract_picture']);

            $data['contract_picture'] = $pic_list;
            //取出所有人员信息
            $admin = TotalAdmin::field(['id', 'name', 'role_id'])->select();
            //取出所有一级代理
            $info = TotalAgent::where('parent_id', 0)->field(['id','agent_name'])->select();
            $data['admin'] = $admin;
            $data['agent'] = $info;
//            dump($data);die;
            return_msg(200, 'success', $data);
        }
    }

    /**
     * 启用代理商
     *
     * @param  status 1启用 0停用
     * @return \think\Response
     */
    public function open(Request $request)
    {
        //获取商户id
        $id = $request->param('id');
        //修改商户状态
        $result = TotalAgent::where('id', $id)->update(['status' => 1]);
        if ($result) {
            return_msg(200, "启用成功");
        } else {
            return_msg(400, "启用失败");
        }
    }

    /**
     * 停用代理商
     *
     * @param  status 1启用 0停用
     * @return \think\Response
     */
    public function stop(Request $request)
    {
        //获取商户id
        $id = $request->param('id');
        //修改商户状态
        $result = TotalAgent::where('id', $id)->update(['status' => 0]);
        if ($result) {
            return_msg(200, "已停用");
        } else {
            return_msg(400, "停用失败");
        }
    }

    /**
     * 删除代理商
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $result = TotalAgent::destroy($id);
        if ($result) {
            //删除成功
            $this->success('删除成功', 'index');
        }
    }

    //上传图片
    private function upload_img(){
        $file=request()->file('file');
        //移动图片
        $info=$file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH.'public'.DS.'uploads');
        if($info){
            //文件上传成功
            //获取文件路径
            $goods_logo=DS.'uploads'.DS.$info->getSaveName();
            $goods_logo=str_replace('\\','/',$goods_logo);
            return $goods_logo;
        }else{
            $error=$file->getError();
            $this->return_msg(400,'failure',$error);
        }
    }

}
