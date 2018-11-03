<?php

namespace app\agent\controller;

use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Exception;
use think\Request;
use think\Session;

class Service extends Agent
{
    /**
     * 显示服务商列表
     *
     * @return \think\Response
     */
    public function service_list(Request $request)
    {
        //获取上级代理商id
        $id = Session::get("username_")['id'];
        $status = $request->param("status");
        $ky = $request->param("ky");
        if ($status) {
            $status_f = "eq";
        } else {
            $status_f = "neq";
            $status = -2;
        }
        if ($ky) {
            $ky_f = "LIKE";
            $ky = $ky . "%";
        } else {
            $ky_f = "NOT LIKE";
            $ky = "-2";
        }
        $where = [
            "status" => [$status_f, $status],
            "agent_name" => [$ky_f, $ky],
        ];

        //获取所有行数
        $rows = TotalAgent::where('parent_id', $id)->where($where)->count();

        $pages = page($rows);
        //获取当前代理商下的所有子代理
        $data['list'] = TotalAgent::field(['id,agent_name,contact_person,agent_phone,agent_mode,agent_area,create_time,status'])
            ->where('parent_id', $id)
            ->where($where)
            ->limit($pages['offset'], $pages['limit'])->select();
        $data['pages'] = $pages;

        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "没有数据");
        }
    }

    /**
     * 启用子代
     * @param [int]  当前代理商ID
     * @return \think\Response
     */
    public function open_agent()
    {
        //获取当前代理商id
        $id = Session::get("username_");

        $result = TotalAgent::get(['id' => $id])->save(['status' => 1]);
        if ($result) {
            return_msg(200, '启用成功');
        } else {
            return_msg(400, '启用失败');
        }
    }

    /**
     * 停用子代
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function stop_agent(Request $request)
    {
        $param = $request->param();

        check_params("service_stop_agent", $param);

        $id = $param['id'];

        if ($request->param("stop_reason")) {
            $stop_msg = $param['stop_reason'];
        } else {
            $stop_msg = '';
        }
        $result = TotalAgent::get(['id' => $id])->save([
            'status' => 0,
            "stop_reason" => $stop_msg,
        ]);

        if ($result) {
            return_msg(200, '停用成功');
        } else {
            return_msg(400, '停用失败');
        }
    }

    /**
     * 子代详情
     *
     * @param  int $id
     * @return \think\Response
     */
    public function agent_detail()
    {
        //
        if (request()->isPost()) {
            //获取子代id
            $data = request()->post();
            //验证
            $validate = Loader::validate('AgentValidate');
            if (!$validate->scene('agent_detail')->check($data)) {
                $error = $validate->getError();
                return_msg(400, 'failure', $error);
            }
            //判断是否上传新图片
            if (!empty($data['contract_picture'])) {
                $data['contract_picture'] = json_encode($data['contract_picture']);
            }
            $data['json'] = json_encode($data['json']);
            $result = TotalAgent::where('id', $data['id'])->update($data, true);
            if ($result) {
                return_msg(200, '修改成功');
            } else {
                return_msg(400, '修改失败');
            }
        } else {
            //获取子代id
            $id = request()->param('id');
            //通过子代id查询子代信息
            $data = TotalAgent::where('id', $id)->find();
//            $data = collection($data['data'])->toArray();
            $data['json'] = json_decode($data['json']);
            //解析图片
            $data['contract_picture'] = json_decode($data['contract_picture']);

            if (count($data)) {
                return_msg(200, 'success', $data);
            } else {
                return_msg(400, "no data");
            }

        }
    }

    /**
     * 新增子代
     *
     * @param  int $id
     * @return \think\Response
     */
    public function agent_add()
    {
        if (request()->isPost()) {
            $data = request()->post();
            //获取上级代理商id
            $data['parent_id'] = Session::get('username_')['id'];
            $data['create_time'] = time();
            //验证
            $validate = Loader::validate('AgentValidate');
            if (!$validate->scene('agent_detail')->check($data)) {
                $error = $validate->getError();
                return_msg(400, 'failure', $error);
            }
            //上传图片
            $data['contract_picture'] = json_encode($data['contract_picture']);
            $data['json'] = json_encode($data['json']);
            //保存
            $info = TotalAgent::insert($data);
            if ($info) {
                return_msg(200, '添加成功');
            } else {
                return_msg(400, '添加失败');
            }
        }
    }

    /**
     * 服务商商户
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     * @throws Exception
     */
    public function service_merchant()
    {
        //获取上级代理商id
        $id = request()->param("id");

        //获取总行数
        $rows = TotalAgent::where('parent_id', $id)->count();
        //分页
        $pages = page($rows);
        //获取子代中的商户信息
        $data['list'] = TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.channel,a.status,b.agent_name,b.agent_phone,a.opening_time')
            ->join('cloud_total_agent b', 'a.agent_id=b.id', 'left')
            ->where(['b.parent_id' => $id])
            ->limit($pages['offset'], $pages['limit'])
            ->select();
        $data['pages'] = $pages;

        if (count($data['list'])) {
            return_msg(200, 'success', $data);
        } else {
            return_msg(400, "没有数据");
        }
    }


    //上传图片
    public function upload_logo()
    {
        $files = request()->file('contract_picture');


        $info = $files->validate(['size' => 5 * 1024 * 1024, 'ext' => 'jpg,jpeg,gif,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        if ($info) {
            //图片上传成功
            $goods_logo = DS . 'uploads' . DS . $info->getSaveName();

            $goods_logo = str_replace('\\', '/', $goods_logo);

            return $goods_logo;
        } else {
            $error = $info->getError();

            return_msg(400, $error);
        }


    }
}
