<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use app\admin\model\TotalCapital;
use think\Controller;
use think\Db;
use think\Request;


class Capital extends Controller
{
    /**
     * 显示未结算的首页列表
     * status 0未结款 1已结款
     *
     * @return \think\Response
     */
    public function index()
    {
        //获取总行数
        $rows=TotalCapital::where('status',0)->count();
        $pages=page($rows);
        $data=TotalCapital::alias('a')
            ->field('a.id,a.date,a.settlement_start,a.settlement_end,a.description,a.account,a.invoice,b.agent_name,b.contact_person, b.agent_area,a.settlement_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status=0')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg('200','success',$data);
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
            //获取结款时间
            $time=time();
            $data=[
                'id'=>$id,
                'status'=>1,
                'settlement_time'=>$time
            ];
            $result=TotalCapital::update($data);
            if($result){
                return_msg(200,'已结款');
            }else{
                return_msg(400,'结款失败');
            }
        }else{
            $id=request()->param('id');
            $data=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg('200','success',$data);
        }
    }

    /**
     * 暂缓处理
     *
     * @param  id int 资金结算id
     * @param  description string 暂缓处理描述
     * @return \think\Response
     */
    public function deal()
    {
        if(request()->isPost()){
            $data=request()->post();
            //跟新数据
            $result=TotalCapital::update($data,true);
            if($result){
                $this->success('处理成功','index');
            }else{
                $this->error('处理失败','index');
            }
        }else{
            $id=request()->param('id');
            $data=TotalCapital::alias('a')
                ->field('a.id,a.settlement_start,a.settlement_end,b.agent_name,b.create_time')
                ->join('cloud_total_agent b','a.agent_id=b.id','left')
                ->where('a.id',$id)
                ->find();
            return_msg('200','success',$data);
        }
    }

    /**
     * 显示已结算列表
     *
     * @param
     * @return \think\Response
     */
    public function settled()
    {
        //获取总行数
        $rows=TotalCapital::where('status',1)->count();
        //分页
        $pages=page($rows);
        $data=TotalCapital::alias('a')
            ->field('a.date,a.settlement_start,a.settlement_end,a.account,b.agent_name,a.settlement_time,a.invoice,b.contact_person, b.agent_area,a.settlement_money ')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.status=1')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg('200','success',$data);
    }

    public function capital_search(Request $request) {
        /* 获取参数 */
        $query['keywords'] = $request->param('keywords') ? $request->param('keywords') : -2;
        $query['address'] = $request->param('address') ? $request->param('address') : -2;
        $query['time'] = $request->param('time') ? json_decode($request->param('time')) : -2;

        $this->get_search_result($query);
        /* 发送参数 */
    }

    /**
     * 结算搜索结果
     * @param array $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function get_search_result(Array $param) {

        /* 设置flag */
        $param['keywords_flag'] = 'like';
        $param['address'] = 'eq';
        $param['time'] = 'between';

        if ($param['keywords'] < -1) {
            $param['keywords_flag'] = '<>';
        }
        if ($param['address'] < -1) {
            $param['address_flag'] = '<>';
        }

        /* 前端参数格式 JSON.stringfy([xxx,xxx]) */
        switch (gettype($param['time'])) {
            case 'array':
                $param['time_flag'] = 'between';
                break;
            default:
                $param['time_flag'] = '>';
        }
        /* 总共有N条数据 */
        $total = Db::name('total_capital')->count('id');
        /* 条件搜索查询有N条数据 */
        $rows = Db::name('total_capital')->alias("c")
            ->join("cloud_total_agent a", "c.agent_id = a.id")
            ->field("a.agent_name,a.contact_person,a.agent_phone,a.agent_area,c.date,c.settlement_money,c.settlement_start,c.settlement_end,c.description,c.account,c.invoice")
            ->where("agent_name|contact_person|agent_phone|c.settlement_money|c.account","LIKE","{$param['keywords']}%")
            ->whereTime("c.settlement_time", $param['time_flag'], $param["time"])
            ->count("c.id");
        $pages = page($rows);
        $res = Db::name('total_capital')->alias("c")
            ->join("cloud_total_agent a", "c.agent_id = a.id")
            ->field("a.agent_name,a.contact_person,a.agent_phone,a.agent_area,c.date,c.settlement_money,c.settlement_start,c.settlement_end,c.description,c.account,c.invoice")
            ->where("agent_name|contact_person|agent_phone|c.settlement_money|c.account","LIKE","{$param['keywords']}%")
            ->whereTime("c.settlement_time", $param['time_flag'], $param["time"])
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
