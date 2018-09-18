<?php

namespace app\agent\controller;

use app\agent\model\AgentPartner;
use app\agent\model\Order;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        echo time()-(23*60*60).'<br/>';
        echo date('Ymd His',time()-(23*60*60));
    }

    /**
     * 显示交易数据
     * 默认显示昨天的交易数据
     * @return \think\Response
     */
    public function trad()
    {
        //获取当前代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_order c','c.merchant_id=a.id','left')
            ->where('a.agent_id',$agent_id)
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->count();
        //分页
        $pages=page($rows);
        //根据代理商id查询昨日交易商户
        $data=TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->join('cloud_order c','c.merchant_id=a.id','left')
            ->where('a.agent_id',$agent_id)
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        //取出商户支付宝和微信交易额交易量
        foreach($data as &$v){
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
     * 变更员工
     *
     * @param  id 商户id
     * @return \think\Response
     */
    public function change_partner(Request $request)
    {
        if($request->isPost()){
            //获取合伙人id
            $data['partner_id']=$request->post('pertner_id');
            //获取商户id
            $data['id']=$request->post('id');
            $request=TotalMerchant::update($data);
            if($request){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }else{
            $id=$request->param('id');
            //取出商户姓名
            $data=TotalMerchant::where('id',$id)->field(['id','name'])->select();
            //取出所有合伙人姓名和id
            $partner=AgentPartner::field(['id','partner_name'])->select();
            $data['partner']=$partner;
            return_msg(200,'success',$data);
        }
    }

    /**
     * 获取活跃商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function active()
    {
        //获取当前代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_order c','c.merchant_id=a.id','left')
            ->where('a.agent_id',$agent_id)
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->count();
        //分页
        $pages=page($rows);
        //根据代理商id查询昨日交易商户
        $data=TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->join('cloud_order c','c.merchant_id=a.id','left')
            ->where('a.agent_id',$agent_id)
            ->whereTime('pay_time','>=','yesterday')
            ->group('a.id')
            ->limit($pages['offset'],$pages['limit'])
            ->select();
//        dump($data);die;
        //取出商户支付宝和微信交易额交易量
        foreach($data as &$v){
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
     * 获取新增商户
     *
     * @param  status 0停用 1启用
     * @return \think\Response
     */
    public function new_merchant()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        //获取昨日新增商户
        //获取总行数
        $rows=TotalMerchant::whereTime('opening_time','>=','yesterday')
            ->where(['agent_id'=>$agent_id,'status'=>1])
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.phone,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->whereTime('a.opening_time','>=','yesterday')
            ->where(['a.agent_id'=>$agent_id,'a.status'=>1])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 总商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function total_merchant(Request $request)
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $rows=TotalMerchant::where('agent_id',$agent_id)
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 审核中商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     */
    public function review_merchant()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $rows=TotalMerchant::where(['review_status'=>['<>',3],'agent_id'=>['=',$agent_id]])
            ->count();
        $pages=page($rows);
        //显示当前代理商下审核中的商户
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.review_status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where(['a.review_status'=>['<>',3],'a.agent_id'=>['=',$agent_id]])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 已驳回商户
     *
     * @param  $review_status 审核状态 0待审核 1开通中 2通过 3未通过
     * @return \think\Response
     */
    public function reject_merchant()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $rows=TotalMerchant::where(['review_status'=>['=',3],'agent_id'=>['=',$agent_id]])
            ->count();
        $pages=page($rows);
        //显示当前代理商下已驳回的商户
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.create_time,a.rejected,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where(['a.review_status'=>['=',3],'a.agent_id'=>['=',$agent_id]])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 查询7日无交易数据
     */
    public function no_trad()
    {
        //获取代理商id
        $agent_id=session('agent_id');
        $agent_id=1;
        $time=time()-(7*24*60*60);//7天前时间
        //获取代理商下7天无交易商户
        $join=[
            ['cloud_agent_partner b','a.partner_id=b.id'],
            ['cloud_order c','a.id=c.merchant_id']
        ];
        $rows=TotalMerchant::alias('a')
            ->join($join)
            ->whereTime('pay_time','<=',$time)
            ->where('a.agent_id',$agent_id)
            ->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field(['a.id,a.name,a.contact,a.phone,b.partner_name'])
            ->join($join)
            ->whereTime('pay_time','<=',$time)
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 首页--交易数据搜索查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   public function search_deal(Request $request)
   {
       //获取商户|联系人|联系方式
    $name=$request->param('keyword');
    //获取合伙人id
    $partner_id=$request->param('partner_id');
    //获取是否交易
    $deal=$request->param('deal');

    //如果$deal=1是有交易用户，$deal=2是无交易用户
    $nodeal='between';

    if($deal==2){
        $nodeal='not between';
    }
    $symbol='<>';
    if($partner_id >0){
        $symbol='eq';
    }
       $total = Db::name('total_merchant')->count('id');
    $rows=TotalMerchant::alias('a')
           ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
           ->join('cloud_order','a.id=cloud_order.merchant_id','left')
           ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
           ->where('a.partner_id',$symbol,$partner_id)
        ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
           ->count('a.id');
       $pages = page($rows);
       $data=TotalMerchant::alias('a')
           ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
           ->join('cloud_order','a.id=cloud_order.merchant_id','left')
           ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
           ->where('a.partner_id',$symbol,$partner_id)
           ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
           ->limit($pages['offset'],$pages['limit'])
           ->select();
       $data['pages'] = $pages;
       $data['pages']['rows'] = $rows;
       $data['pages']['total_row'] = $total;
       if(count($data)!==0){
           return_msg(200,'success',$data);
       }else{
           return_msg(400,'没有数据');
       }
   }

    /**
     * 首页-昨日活跃商户
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function yesterday_active(Request $request)
    {
        $total = Db::name('total_merchant')->count('id');

        $rows=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.address,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order','a.id=cloud_order.merchant_id','left')
            ->whereTime('cloud_order.pay_time','between',[$request->param('pay_time'),$request->param('yesterday')])
            ->count('a.id');
        $pages = page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.address,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order','a.id=cloud_order.merchant_id','left')
            ->whereTime('cloud_order.pay_time','between',[$request->param('pay_time'),$request->param('yesterday')])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }
    }

    /**
     * 首页-七日无交易-筛选查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_trade(Request $request)
    {
        //获取商户|联系人|联系方式
        $name=$request->param('keyword');
        //获取合伙人id
        $partner_id=$request->param('partner_id');
        //获取是否交易
        $deal=$request->param('deal');

        //如果$deal=1是有交易用户，$deal=2是无交易用户
        $nodeal='between';
        $total = Db::name('total_merchant')->count('id');
        if($deal==2){
            $nodeal='not between';
        }
        $symbol='<>';
        if($partner_id >0){
            $symbol='eq';
        }

        $rows=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order','a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner','a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->count('a.id');
        $pages = page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order','a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner','a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }
    }

    /**
     * 商户列表-筛选查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_list(Request $request)
    {
        //获取商户|联系人|联系方式
        $name=$request->param('keyword');
        //获取合伙人id
        $partner_id=$request->param('partner_id');
        //获取是否交易
        $deal=$request->param('deal');

        //如果$deal=1是有交易用户，$deal=2是无交易用户
        $nodeal='between';

        if($deal==2){
            $nodeal='not between';
        }
        $symbol='<>';
        if($partner_id >0){
            $symbol='eq';
        }
        $total = Db::name('total_merchant')->count('id');
        $rows=TotalMerchant::alias('a')
            ->field('a.contact,a.phone,a.status,a.opening_time,a.review_status,a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order','a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner','a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->count('a.id');
        $pages = page($rows);

        $data=TotalMerchant::alias('a')
            ->field('a.contact,a.phone,a.status,a.opening_time,a.review_status,a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier,cloud_agent_partner.partner_name')
            ->join('cloud_order','a.id=cloud_order.merchant_id')
            ->join('cloud_agent_partner','a.partner_id=cloud_agent_partner.id')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }
    }

    /**
     * 商户交易--筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_deal(Request $request)
    {
        //获取商户|联系人|联系方式
        $name=$request->param('keyword');
        //获取合伙人id
        $partner_id=$request->param('partner_id');
        //获取是否交易
        $deal=$request->param('deal');

        //如果$deal=1是有交易用户，$deal=2是无交易用户
        $nodeal='between';

        if($deal==2){
            $nodeal='not between';
        }
        $symbol='<>';
        if($partner_id >0){
            $symbol='eq';
        }
        $total = Db::name('total_merchant')->count('id');

        $rows=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order','a.id=cloud_order.merchant_id','left')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->count('a.id');
        $pages = page($rows);

        $data=TotalMerchant::alias('a')
            ->field('a.abbreviation,a.contact,a.phone,cloud_order.received_money,cloud_order.cashier')
            ->join('cloud_order','a.id=cloud_order.merchant_id','left')
            ->where('a.abbreviation|a.contact|a.phone','like',"%$name%")
            ->where('a.partner_id',$symbol,$partner_id)
            ->whereTime('cloud_order.pay_time',$nodeal,[$request->param('pay_time'),$request->param('yesterday')])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }
    }

    /**
     * 服务商列表-筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function facilitator_list(Request $request)
    {
        //服务商名称|联系人|联系方式
        $name=$request->param('keyword');
        $status=$request->param('status');
        $agent_area=$request->param('agent_area');
        $agent_ateabol='eq';
        if(empty($agent_area)){
            $agent_ateabol='<>';
        }
        $statusbol='eq';
        if($status==3){
            $statusbol='<>';
        }
        $parent_id=0;
        $symbol='<>';
        if(!empty($name))
        {
            $symbol='eq';
            $parent_id=Db::name('total_agent')->where('agent_name|contact_person|agent_phone','like',"%$name%")
                ->field(['id'])->find();
        }
        $total = Db::name('total_agent')->count('id');

        $rows=Db::name('total_agent')->where([
            'parent_id'=>[$symbol,$parent_id['id']],
        'agent_area'=>[$agent_ateabol,$agent_area],
            'status'=>[$statusbol,$status]
        ,'create_time'=>['between time',[$request->param('create_time'),$request->param('end_time')]]
        ])
            ->count('id');
        $pages = page($rows);
        $data=Db::name('total_agent')->where([
            'parent_id'=>[$symbol,$parent_id['id']],
            'agent_area'=>[$agent_ateabol,$agent_area],
            'status'=>[$statusbol,$status]
            ,'create_time'=>['between time',[$request->param('create_time'),$request->param('end_time')]]
        ])
            ->field(['agent_name','contact_person','agent_phone','agent_area','create_time','status'])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }
    }

    /**
     * 服务商商户-筛选搜索
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function facilitator_tenant(Request $request)
    {
        $name=$request->param('keyword');
        $status=$request->param('status');
        $agent_id=$request->param('agent_id');
        $status_symbol='eq';
        if($status==2){
            $status_symbol='<>';
        }
        $total = Db::name('total_agent')->count('id');

        $rows=Db::name('total_merchant')
            ->where(['abbreviation|contact|phone'=>['like',"%$name%"],
                'status'=>[$status_symbol,$status],'agent_id'=>['eq',$agent_id]])
            ->count('id');
        $pages = page($rows);

        $data=Db::name('total_merchant')
            ->where(['abbreviation|contact|phone'=>['like',"%$name%"],
                'status'=>[$status_symbol,$status],'agent_id'=>['eq',$agent_id]])
            ->field(['id','agent_name','address','status','abbreviation','opening_time'])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages'] = $pages;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if(count($data)!==0){
            return_msg(200,'success',$data);
        }else{
            return_msg(400,'没有数据');
        }

    }
}
