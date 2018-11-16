<?php

namespace app\agent\controller;

use app\admin\controller\Incom;
use app\admin\model\AreaCode;
use app\admin\model\Mcc;
use app\agent\model\AgentCategory;
use app\agent\model\AgentPartner;
use app\agent\model\MerchantGroup;
use app\agent\model\MerchantIncom;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use app\agent\model\TotalMerchantMember;
use think\Controller;
use think\Exception;
use think\Loader;
use think\Request;
use think\Session;
use think\Db;

class Merchant extends Incom
{

    public function index_list()
    {

        $agent_id=Session::get("username_")["id"];

        //获取总行数
        $rows=TotalMerchant::where('agent_id',$agent_id)->count();
        $pages=page($rows);
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,a.review_status,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 显示当前代理商下所有商户列表
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $agent_id=Session::get("username_")["id"];
        //获取总行数
        $total=TotalMerchant::where('agent_id',$agent_id)->count('id');
        $rows=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,a.review_status,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->where('a.agent_id',$agent_id)
            ->count('a.id');
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,ag.agent_area as address,a.contact,a.channel,a.opening_time,a.status,a.review_status,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id = b.id','left')
            ->join("cloud_total_agent ag", "ag.id = a.agent_id")
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $arr = Db::name('agent_partner')->where('agent_id',$agent_id)->field(['id','partner_name'])->select();


        $data['pages']=$pages;
        $data['partner']=$arr;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        check_data($data['list'], $data);
    }
    /**
     * 启用商户
     */
    public function start()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>1]);
        if($result){
            return_msg(200,"启用成功");
        }else{
            return_msg(400,"启用失败");
        }
    }
    /**
     * 停用商户 0关闭 1开启
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function stop()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,"已停用");
        }else{
            return_msg(400,"停用失败");
        }
    }
    /**
     * 显示当前代理商下正常使用的商户列表
     *  review_status 审核状态 0待审核 1开通中 2通过 3未通过
     *  status  账号状态 0开启 1关闭
     * @return \think\Response
     * @throws Exception
     */
    public function normal_list()
    {
        //获取代理商id
        $agent_id=Session::get("username_")["id"];

        $where=[
            'a.review_status' =>2,
            'a.status'=>0,
            'a.agent_id'=>$agent_id
        ];
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 显示已停用商户
     *
     * @return \think\Response
     */
    public function stop_list()
    {
        //获取代理商id
        $agent_id=Session::get("username_")["id"];

        $where=[
            'a.review_status' =>2,
            'a.status'=>1,
            'a.agent_id'=>$agent_id
        ];
        //获取总行数
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();

        return_msg(200,'success',$data);
    }

    /**
     * 显示审核中的商户
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function review_list()
    {
        //获取代理商id
        $agent_id=Session::get("username_")["id"];

        $where=[
            'a.review_status'=>['<',2],
            'a.agent_id'=>['=',$agent_id]
        ];
        $rows=TotalMerchant::alias('a')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->count();
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 显示已驳回商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function reject()
    {
        //获取代理商id
        $agent_id=Session::get("username_")["id"];

        $where=[
            'review_status'=>3,
            'agent_id'=>$agent_id
        ];
        $rows=TotalMerchant::where($where)->count();
        $pages=page($rows);
        $data['list']=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.rejected,a.opening_time,a.status,b.contact_person,b.agent_phone')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where($where)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 新增商户
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function add_middle()
    {
        //
        $agent_id=Session::get("username_")["id"];
        if(request()->isPost()){
            $data=request()->post();
            $data['agent_id']=$agent_id;
//            $data['channel']=3;//表示间联
            //验证
            check_params('add_middle',$data,'AgentValidate');
            //上传图片
//            $data['attachment']=$this->upload_logo();
//            $data['agent_id']=$agent_id;
//            $data['attachment']=json_encode($data['attachment']);
//            halt($data);

            $insert_id=TotalMerchant::insertGetId($data,true);
            $arr=[];
            if($insert_id){
                if(empty($data['wc_lbnk_no'])){
                    //用户自己输入支行
                    $open_branch=$this->bank_query($data['open_branch']);
                    $arr['wc_lbnk_no']=$open_branch;
                }else{
                    $arr['wc_lbnk_no']=$data['wc_lbnk_no'];
                }
                $arr['merchant_id']=$insert_id;//商户id
                $arr['stl_sign']=$data['account_type'];//账户类型
                $arr['stl_oac']=$data['account_no'];//账户号
                $arr['bnk_acnm']=$data['account_name'];//账户名
                $arr['stoe_cnt_nm']=$data['contact'];//联系人
                $arr['stoe_cnt_tel']=$data['phone'];//联系电话
                $arr['stoe_adds']=$data['detail_address'];//详细地址
                $arr['mailbox']=$data['email'];//邮箱
                $arr['incom_type']=$data['merchants_type']; //商户类型 1个人 2企业
                $arr['icrp_id_no']=$data['id_card'];//结算人身份证号
                $arr['crp_exp_dt_tmp']=$data['id_card_time'];//结算人身份证到期日
                $arr['crp_id_no']=$data['id_card'];//法人身份证号
                $arr['crp_exp_dt']=$data['id_card_time'];//法人身份证到期日
                $arr['fee_rat_scan']=$data['merchant_rate'];//间联费率
                $arr['fee_rat3_scan']=$data['merchant_rate'];//
                $arr['bus_lic_no']=$data['business_license'];//营业执照号
                $arr['bus_exp_dt']=$data['license_time'];//营业执照有限期
                $arr['bse_lice_nm']=$data['license_name'];//营业执照名
                $arr['mercAdds']=$data['address'];//营业执照地址
                $arr['stoe_nm']=$data['stoe_nm'];//签购单名称=省市+门店名
                $arr['mcc_cd']=$data['mcc_cd'];//mcc码
                $arr['stoe_area_cod']=$data['stoe_area_cod'];//地区码
                $arr['orgNo']=ORG_NO;//合作商机构号
                $arr['crp_nm']=$data['contact'];//法人姓名
//                halt($arr);
                MerchantIncom::insert($arr,true);
                $this->merchant_incom($insert_id);
//                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            //取出当前代理商下所有合伙人
            $data['list']=AgentPartner::field(['id','partner_name'])->where('agent_id',$agent_id)->select();
            //显示所有一级分类
//            $data['category']=AgentCategory::where('pid',0)->select();
            $data['category']=Mcc::field('sup_mcc_cd,sup_mcc_nm')->group('sup_mcc_cd')->select();
            //取出所有省份和省份名称
            $data['province']=AreaCode::field('merc_prov,prov_nm')->group('merc_prov')->select();
            return_msg(200,'success',$data);
        }
    }


    /**
     * 启用商户 0关闭 1开启
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function enable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>1]);
        if($result){
            return_msg(200,"启用成功");
        }else{
            return_msg(400,"启用失败");
        }
    }

    /**
     * 停用商户 0关闭 1开启
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function disable()
    {
        //获取商户id
        $id=request()->param('id');
        //修改商户状态
        $result=TotalMerchant::where('id',$id)->update(['status'=>0]);
        if($result){
            return_msg(200,"已停用");
        }else{
            return_msg(400,"停用失败");
        }
    }
    //获取二级分类和三级分类
    public function getCatePid()
    {
        $sup_mcc_cd=request()->param('sup_mcc_cd');
        $data=Mcc::field('mcc_nm,mcc_cd')->where('sup_mcc_cd',$sup_mcc_cd)->select();
        return_msg(200,'success',$data);
    }

    /**
     * 取出省下的城市
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function city()
    {
        $merc_prov=request()->param('merc_prov');
        $data=AreaCode::field('merc_city,city_nm')->where('merc_prov',$merc_prov)->group('merc_city')->select();
        return_msg(200,'success',$data);
    }

    /**
     * 取出城市下的区县
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function area()
    {
        $merc_city=request()->param('merc_city');
        $data=AreaCode::field('area_nm,merc_area')->where('merc_city',$merc_city)->select();
        return_msg(200,'success',$data);
    }
    /**
     * 会员互通
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function merchant_group()
    {

        $agent_id=Session::get("username_")["id"];

        //取出当前代理商下所有会员互通商户
        $data=MerchantGroup::where('agent_id',$agent_id)->select();
        foreach($data as &$v){
            $v['merchant_id'] = explode(',',$v['merchant_id']);
            $res = TotalMerchant::field('name')->where('id','in',$v['merchant_id'])->select();
            $res = collection($res)->toArray();
            $v['merchant_id']=$res;
        }
        return_msg(200,'success',$data);
    }

    /**
     * 添加会员互通
     *
     * @param  \think\Request  group_id 分组id
     * @param  int  $id 商户id
     * @return \think\Response
     */
    public function add_group(Request $request)
    {
        $agent_id=Session::get('username_')['id'];

        if($request->isPost()){
            //获取分组id和当前商户id
            $data=$request->post();
            $info=MerchantGroup::where('id',$data['group_id'])->find();
            $arr=explode(',',$info['merchant_id']);
            //加入商户id
            $arr[]=$data['id'];
            $merchant_id=implode(',',$arr);
            $result=MerchantGroup::where('id',$data['group_id'])->update(['merchant_id'=>$merchant_id]);
            if($result){
                return_msg(200,'操作成功');
            }else{
                return_msg(400,'操作失败');
            }
        }else{
            //取出代理商下所有商户
            //分页
            $count=TotalMerchant::where('agent_id',$agent_id)->count();
            $pages=page($count);
            $data['list']=TotalMerchant::field('id,name')
                ->where('agent_id',$agent_id)
                ->limit($pages['offset'],$pages['limit'])
                ->select();
            $data['page']=$pages;
            return_msg(200,'success',$data);
        }

    }

    /**
     * 搜索商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function search(Request $request)
    {
        $agent_id=Session::get('username_')['id'];

        //获取商户名
        $name=$request->param('name');
        //搜索商户
        $data=TotalMerchant::field('id,name')
                ->where(['agent_id'=>$agent_id,'name'=>['like',$name.'%']])
                ->select();
        return_msg(200,'success',$data);
    }

    /**
     *新增会员互通
     */
    public function new_group()
    {
        //新增会员互通
        $agent_id=Session::get('username_')['id'];
        $arr=[
            'agent_id'=>$agent_id
        ];
        MerchantGroup::insert($arr);
    }

    /**
     * 删除商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function del(Request $request)
    {
        $group_id=$request->param('group_id');
        if($request->isPost()){
            //获取商户id
            $id=$request->post('id');
            $group_id=$request->param('group_id');
            //取出分组数据
            $data=MerchantGroup::where('id',$group_id)->find();
            $arr=explode(',',$data['merchant_id']);
            for($i=0;$i<count($arr);$i++){
                if($arr[$i]==$id){
                    unset($arr[$i]);
                }
            }
            $merchant_id=implode(',',$arr);
            if($merchant_id==null){
                $info=MerchantGroup::where('id',$group_id)->delete();
            }else{
                $info=MerchantGroup::where('id',$group_id)->update(['merchant_id'=>$merchant_id]);
            }
            if($info){
                return_msg(200,'删除成功');
            }else{
                return_msg(400,'删除失败');
            }
        }else{
            $data=MerchantGroup::where('id',$group_id)->find();
            $data['merchant_id']=explode(',',$data['merchant_id']);
            $info=TotalMerchant::field('id,name')->where('id','in',$data['merchant_id'])->select();
            $info=collection($info)->toArray();
            return_msg(200,'success',$info);
        }

    }
    /**
     * 新增直联商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
//    public function add_straight(Request $request)
//    {
//        //
//        $agent_id=session('agent_id');
//        if($request->isPost()){
//            $data=request()->post();
//            //验证
//
//            //上传图片
//            $data['agent_id']=$agent_id;
//            $data['attachment']=$this->upload_logo();
//            $data['attachment']=json_encode($data['attachment']);
//            $info=TotalMerchant::insert($data);
//            if($info){
//                return_msg(200,'添加成功');
//            }else{
//                return_msg(400,'添加失败');
//            }
//        }else{
//            //取出所有合伙人信息
//            $data=AgentPartner::field(['id','partner_name'])->where('agent_id',$agent_id)->select();
//            return_msg(200,'success',$data);
//        }
//    }



    //上传图片
    private function upload_logo(){
        $files=request()->file('attachment');
        $goods_pics=[];
        foreach($files as $file){
            $info=$file->validate(['size'=>5*1024*1024,'ext'=>'jpg,jpeg,gif,png'])->move(ROOT_PATH.'public'.DS.'uploads');
            if($info){
                //图片上传成功
                $goods_logo=DS.'uploads'.DS.$info->getSaveName();
                $goods_logo=str_replace('\\','/',$goods_logo);
                $goods_pics[]=$goods_logo;
            }else{
                $error=$info->getError();
                return_msg(400,$error);
            }
        }
        return $goods_pics;
    }
}
