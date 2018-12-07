<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use app\admin\model\TotalCapital;
use app\admin\controller\Admin;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;


class Capital extends Admin
{
    /**
     * 显示未结算的首页列表
     * status 0未结款 1已结款
     *
     * @return \think\Response
     * @throws  Exception
     */
    public function index()
    {

        $id = \request()->param("id");
//        is_user_can($id);
        //获取总行数
        $rows=TotalCapital::where('status = 0')->count();
        $pages=page($rows);
        $data['data']=TotalCapital::alias('a')
            ->field('a.id,a.date,a.settlement_start,a.settlement_end,a.description,a.account,a.invoice,b.agent_name,b.contact_person, b.agent_area,a.settlement_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status=0')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        check_data($data["data"], $data);
    }

    /**
     * 结款
     * @param   id int 资金结算id
     * @return \think\Response
     */
    public function settlement()
    {
        if(request()->isPost()){
            //确认结算
            //改变结算状态为已结款
            $id=request()->param('id');
            if (empty($id)) {
                return_msg(400, "错误");
            }

            $data=[
                'description' => \request()->param("description"),
                'settlement_time'=>time(),
                "status" => 1,
            ];
            $result=TotalCapital::update($data,["id" =>$id]);
            if($result){
                return_msg(200,'结款成功');
            }else{
                return_msg(400,'未处理');
            }
        }else{
            $id=request()->param('id');
            $data['list']=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_money,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 暂缓处理
     *
     * @param  $id int 资金结算id
     * @param   string 暂缓处理描述
     * @return \think\Response
     * @throws Exception
     */
    public function deal()
    {
        if(request()->isGet()){
            $data = request()->post();
            $data["status"] = 0;
            //跟新数据
            $result = TotalCapital::update($data,["id" => $data["id"]]);
            if($result){
                return_msg("200",'暂缓处理成功');
            }else{
                return_msg("400",'处理失败');
            }
        }else{
            $id=request()->param('id');
            $data=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_money,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 显示已结算列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settled()
    {
        //获取总行数
        $rows=TotalCapital::where('status',1)->count();
        //分页
        $pages=page($rows);
        $data['list']=TotalCapital::alias('a')
            ->field('a.date,a.settlement_start,a.settlement_end,a.account,b.agent_name,a.settlement_time,a.invoice,b.contact_person, b.agent_area,a.settlement_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status = 1')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        check_data($data["list"], $data);
    }

    /**
     * 待结算搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function capital_search(Request $request) {
        /* 获取参数 */
        $query['keywords'] = $request->param('keywords');
        $query['time'] = $request->param('time');  //2018-9-28 0:0:0
        $query['agent_area']  = $request->param('agent_area');
        $query['status'] = 0;
        /* 发送参数 */
        $this->get_search_result($query);

    }

    /**
     * 已结算搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ready_capital_search(Request $request) {
        /* 获取参数 */
        $query['keywords'] = $request->param('keywords');
        $query['agent_area']  = $request->param('agent_area');
        $query['ready_time'] = $request->param('time');
        $query['status'] = 1;


        $this->get_search_result($query);
        /* 发送参数 */
    }

    /**
     * 结算搜索结果
     * @param address [string]  代理区域
     * @param keywords [string]  关键字
     * @param time     [array]   时间
     * @param array $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function get_search_result(Array $param) {

        /* 设置flag */

        $param['status_flag'] = "eq";

        if (empty($param['ready_time'])) {

            $param['ready_time_flag'] = ">";
            $param['ready_time'] = 0;
        }else {
            $param['ready_time_flag'] = "between";
            $first_day = date("Y-m-01", $param['ready_time']);
            $last_day = date('Y-m-d', strtotime("{$first_day} +1 month -1 day"));
            $param['ready_time'] = [$first_day ,  $last_day];

        }
        if (empty($param['keywords'])) {
            $param['keywords_flag'] = '<>';
            $param['keywords'] = '1';
        }else {
            $param['keywords_flag'] = 'LIKE';
        }
        if ($param['agent_area'] == '') {
            $param['area_flag'] = '<>';
            $param['agent_area'] = '-2';
        }else {
            $param['area_flag'] = "eq";
        }

        /* 前端参数格式 */
        if (empty($param['time'])) {
            $param['time_flag'] = '>';
            $param['time'] = 0;
        }else {
            $param['time_flag'] = "between";
            $param['time'] = [date('Y-m-d', (int)$param['time']), date("Y-m-d",(int)$param['time']+24 *60 *60) ];
        }

        /**
         * 根据条件SQL搜索
         */
        /* 总共有N条数据 */
        $total = Db::name('total_capital')->count('id');
        /* 条件搜索查询有N条数据 */
        $rows = Db::name('total_capital')->alias("c")
            ->join("cloud_total_agent a", "c.agent_id = a.id")
            ->where([
                "a.agent_name|contact_person|agent_phone|c.settlement_money|c.account" => [$param['keywords_flag'], $param['keywords']."%"],
                "a.agent_area"  => [$param['area_flag'] , "{$param['agent_area']}"],
                "c.status"  => [$param['status_flag'] , $param['status']],
            ])
            ->whereTime("c.date", $param['time_flag'], $param["time"])
            ->whereTime("c.settlement_time", $param['ready_time_flag'], $param["ready_time"])
            ->count("c.id");
        $pages = page($rows);

        $res['list'] = Db::name('total_capital')->alias("c")
            ->join("cloud_total_agent a", "c.agent_id = a.id")
            ->field("c.settlement_time,c.id,a.agent_name,a.contact_person,a.agent_phone,a.agent_area,c.date,c.settlement_money,c.settlement_start,c.settlement_end,c.description,c.account,c.invoice")
            ->where([
                "a.agent_name|contact_person|agent_phone|c.settlement_money|c.account" => [$param['keywords_flag'], $param['keywords']."%"],
                "a.agent_area"  => [$param['area_flag'] , $param['agent_area'] ],
                "c.status"  => [$param['status_flag'] , $param['status']],
            ])
            ->whereTime("c.date", $param['time_flag'], $param["time"])
            ->whereTime("c.settlement_time", $param['ready_time_flag'], $param["ready_time"])
            ->limit($pages['offset'],$pages['limit'])
            ->select();


        $res['pages'] = $pages;
        $res['pages']['total_row'] = $total;
        if ($rows !== 0) {
            return_msg(200, '获取搜索结果成功', $res);
        }else {
            return_msg(400, '没有找到数据');
        }
    }

}
