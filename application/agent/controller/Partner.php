<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Exception;
use think\Request;

class Partner extends Controller
{
    /**
     * 显示员工列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //显示当前代理商下面所有员工
        $id=session('agent_id');
        $id=1;
        $data=AgentPartner::field(['id,partner_name,partner_phone,create_time,model'])
            ->where('agent_id',$id)
            ->select();
        //取出负责商户数
        foreach($data as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 新增合伙人
     *
     * @return \think\Response
     */
    public function add_partner()
    {
        if(request()->isPost()){
            //传入当前代理商id
            $data=request()->post();
            $data['agent_id']=session('agent_id');
            //提交时间
            $data['create_time']=time();
            //验证
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::insert($data);
            if($result){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            return_msg(200,'success');
        }
    }

    /**
     * 删除合伙人
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function del()
    {
        //获取合伙人id
        $id=request()->param('id');
        $result=AgentPartner::destroy($id);
        if($result){
            return_msg(200,'删除成功');
        }else{
            return_msg(400,'删除失败');
        }
    }

    /**
     * 合伙人详情页
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function partner_detail()
    {
        if(request()->isPost){
            $data=request()->post();
            $data['password']=addslashes(encrypt_password($data['password']));
            $result=AgentPartner::update($data,true);
            if($result){
                return_msg(200,'修改成功');
            }else{
                return_msg(400,'修改失败');
            }
        }else{
            //获取合伙人id
            $id=request()->param('id');
            $id=1;
            $data=AgentPartner::where('id',$id)->field(['id,partner_name,partner_phone,create_time,model'])->select();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 查看商户
     *
     * @param  int  $id
     * @return \think\Response
     * @throws Exception
     */
    public function show_merchant()
    {
        //获取合伙人id
        $id=request()->param('id');
        $id=1;
        //取出合伙人下的所有有交易的商户
        $rows=TotalMerchant::alias('a')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->join('cloud_order c','a.id=c.merchant_id','left')
            ->where(['partner_id'=>$id])
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->count();
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->join('cloud_order c','a.id=c.merchant_id','left')
            ->where(['partner_id'=>$id])
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        //取出商户支付宝和微信交易额交易量
        foreach($data['list'] as &$v){
            $where=[
                'status'=>1,
                'pay_type'=>'alipay',
                'merchant_id'=>$v['id']
            ];
            $alipay=Order::where($where)->sum('received_money');
            $alipay_number=Order::where($where)->count();
            $where1=[
                'status'=>1,
                'pay_type'=>'wxpay',
                'merchant_id'=>$v['id']
            ];
            //取出微信交易额
            $wxpay=Order::where($where1)->sum('received_money');
            //取出微信交易量
            $wxpay_number=Order::where($where1)->count();
            $v['alipay']=$alipay;
            $v['alipay_number']=$alipay_number;
            $v['wxpay']=$wxpay;
            $v['wxpay_number']=$wxpay_number;
        }
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 员工报表
     * @param int id 代理商id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     */
    public function partner_report()
    {
        //获取当前代理商id
        $id=session('agent_id');
        // test mark
        $id=1;
        /** 代理商费率   */
        $res = TotalAgent::where('id',$id)->field("agent_rate")->find();
        $agent_rate = intval($res->agent_rate);
        $data['list']=AgentPartner::field(['id,partner_name,partner_phone,create_time,model,proportion,rate'])
            ->where('agent_id',$id)
            ->select();
//        dump($data);die;
        //取出负责商户数
        $flag=array();
        foreach($data['list'] as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
            //根据合伙人id取出昨天新增商户数
            $new_count=TotalMerchant::whereTime('opening_time','yesterday')->where('partner_id',$v['id'])->count();
            $v['new_count']=$new_count;
            //计算佣金
            $sum=TotalMerchant::alias('a')
                ->join('cloud_agent_partner b','a.partner_id=b.id')
                ->join('cloud_order c','c.merchant_id=a.id')
                ->where(['a.partner_id'=>$v['id'],'b.agent_id'=>$id])
                ->sum('c.received_money');
            if($v['model']=="按比例"){
                //安比例
                $v['money']=$sum*$v['proportion']/100;
            }elseif($v['model']=="按费率"){
                //按费率
                $v['money']=$sum * abs( $v['rate'] - $agent_rate )/100;
            }
            $flag[]=$v['new_count'];
        }
        //按照新增商户数降序排列
       array_multisort($flag, SORT_DESC, $data);
        return_msg(200,'success',$data);

    }

    /*
     *  搜索合伙人
     *  @param $start_time [int][string] 开始时间 时间戳
     *  @param $end_time  [int]  结束时间 时间戳
     *  @return $data     [json] 筛选后的数据
     * */
    public function search()
    {
        //按合伙人姓名和电话搜索
        $search=request()->param('search');
        $search='端';
        if(is_numeric($search)){
            //电话搜索
            $data=AgentPartner::where('partner_phone','like',$search.'%')
                ->select();
        }else{
            //姓名搜索
            $data=AgentPartner::where('partner_name','like',$search.'%')
                ->select();
        }
        return_msg(200,'success',$data);
    }

    /**
     * 榜单
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function list_search(Request $request)
    {
        //获取搜索条件
        $query['start_time']=$request->param('start_time') ? $request->param('start_time') : '';
        //默认商户新增磅
        $query['status']=$request->param('status') ? $request->param('status') : 1;
        $query['yesterday']=$request->param('yesterday') ? $request->param('yesterday') : '';
        $query['week']=$request->param('week') ? $request->param('week') : '';
        $query['month']=$request->param('month') ? $request->param('month') : '';
        if(!empty($query['start_time'])){
            $query['end_time']=$request->param('end_time') ? $request->param('end_time') : '';
            if(empty($query['end_time'])){
                return_msg(400,'请选择结束时间');
            }
            if(time()-$query['start_time']>5604000){
                return_msg(400,'您选择的时间大于两个月，请重新选择！');
            }
            $param="between";
            $time=[$query['start_time'],$query['end_time']];
            switch($query['status']){
                case 1:
                    //商户新增磅
                    $sort='new_count';
                    $this->get_search($time,$sort,$param);
                    break;
                case 2:
                    //商户总磅
                    $sort="count";
                    $this->get_search($time,$sort,$param);
                    break;
                case 3:
                    //佣金磅
                    $sort="money";
                    $this->get_search($time,$sort,$param);
                    break;
            }
        }elseif(!empty($query['yesterday'])){
            switch($query['status']){
                case 1:
                    //商户新增磅
                    $sort="new_count";
                    $this->get_search($query['yesterday'],$sort);
                    break;
                case 2:
                    //商户总磅
                    $sort="count";
                    $this->get_search($query['yesterday'],$sort);
                    break;
                case 3:
                    //佣金磅
                    $sort="money";
                    $this->get_search($query['yesterday'],$sort);
                    break;
            }
        }elseif(!empty($query['week'])){
            switch($query['status']){
                case 1:
                    //商户新增磅
                    $sort="new_count";
                    $this->get_search($query['week'],$sort);
                    break;
                case 2:
                    //商户总磅
                    $sort="count";
                    $this->get_search($query['week'],$sort);
                    break;
                case 3:
                    //佣金磅
                    $sort="money";
                    $this->get_search($query['week'],$sort);
                    break;
            }
        }elseif(!empty($query['month'])){
            switch($query['status']){
                case 1:
                    //商户新增磅
                    $sort="new_count";
                    $this->get_search($query['month'],$sort);
                    break;
                case 2:
                    //商户总磅
                    $sort="count";
                    $this->get_search($query['month'],$sort);
                    break;
                case 3:
                    //佣金磅
                    $sort="money";
                    $this->get_search($query['month'],$sort);
                    break;
            }
        }
    }

    /**
     * 返回搜索结果
     */

    public function get_search($time,$sort,$param='>')
    {
        //获取当前代理商id
        $id=session('agent_id');
        $id=1;
        $res = TotalAgent::where('id',$id)->field("agent_rate")->find();
        $agent_rate = intval($res->agent_rate);
        $data=AgentPartner::field(['id,partner_name,partner_phone,create_time,model,proportion,rate'])
            ->where('agent_id',$id)
            ->select();
//取出负责商户数
        $flag=array();
        foreach($data as &$v){
            $count=TotalMerchant::where('partner_id',$v['id'])->count();
            $v['count']=$count;
            //根据合伙人id取出新增商户数
            $new_count=TotalMerchant::whereTime('opening_time',$param,$time)->where('partner_id',$v['id'])->count();
            $v['new_count']=$new_count;
            //计算佣金
            $sum=TotalMerchant::alias('a')
                ->join('cloud_agent_partner b','a.partner_id=b.id')
                ->join('cloud_order c','c.merchant_id=a.id')
                ->where(['a.partner_id'=>$v['id'],'b.agent_id'=>$id])
                ->sum('c.received_money');
            if($v['model']=="按比例"){
                //安比例
                $v['money']=$sum*$v['proportion']/100;
            }elseif($v['model']=="按费率"){
                //按费率
                $v['money']=$sum * abs( $v['rate'] - $agent_rate )/100;
            }
            $flag[]=$v[$sort];
        }
        array_multisort($flag, SORT_DESC, $data);
        return_msg(200,'success',$data);
    }
}

