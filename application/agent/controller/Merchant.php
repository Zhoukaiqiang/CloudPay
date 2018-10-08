<?php

namespace app\agent\controller;

use app\admin\controller\Incom;
use app\agent\model\AgentCategory;
use app\agent\model\AgentPartner;
use app\agent\model\MerchantIncom;
use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
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
            ->field('a.id,a.name,a.phone,a.address,a.contact,a.channel,a.opening_time,a.status,a.review_status,b.partner_name')
            ->join('cloud_agent_partner b','a.partner_id=b.id','left')
            ->where('a.agent_id',$agent_id)
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $arr = Db::name('agent_partner')->where('agent_id',$agent_id)->field(['id','partner_name'])->select();


        $data['pages']=$pages;
        $data['partner']=$arr;
        $data['pages']['rows'] = $rows;
        $data['pages']['total_row'] = $total;
        if (count($data['list'])  > 0 ) {
            return_msg(200,'success',$data) ;
        } else {
            return_msg(400, '没有数据');
        }


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
        return_msg('200','success',$data);
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

        return_msg('200','success',$data);
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
        return_msg('200','success',$data);
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
        return_msg('200','success',$data);
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

//            $data['channel']=3;//表示间联
            //验证
            //$validate = Loader::validate('AgentValidate');
//            if (!$validate->scene('add_middle')->check($data)) {
//                $error = $validate->getError();
//                return_msg(400, 'failure', $error);
//            }
            //上传图片
//            $data['attachment']=$this->upload_logo();
//            $data['agent_id']=$agent_id;
//            $data['attachment']=json_encode($data['attachment']);

            $insert_id=TotalMerchant::insertGetId($data,true);
            $arr=[];
            if($insert_id){
                $arr['merchant_id']=$insert_id;//商户id
                $arr['stl_sign']=$data['account_type'];//账户类型
                $arr['stl_oac']=$data['account_no'];//账户号
                $arr['bnk_acnm']=$data['account_name'];//账户名
                $arr['wc_lbnk_no']=$data['open_bank'];//开户银行  联行行号
                $arr['stoe_cnt_nm']=$data['contact'];//联系人
                $arr['stoe_cnt_tel']=$data['phone'];//联系电话
                $arr['stoe_adds']=$data['detail_address'];//详细地址
                $arr['mailbox']=$data['email'];//邮箱
                $arr['incom_type']=$data['merchants_type'];//商户类型 1个人 2企业
                $arr['icrp_id_no']=$data['id_card'];//结算人身份证号
                $arr['crp_exp_dt_tmp']=$data['id_card_time'];//结算人身份证到期日
                $arr['crp_id_no']=$data['id_card'];//法人身份证号
                $arr['crp_exp_dt']=$data['id_card_time'];//法人身份证到期日
                $arr['fee_rat_scan']=$data['merchant_rate'];//间联费率
                $arr['fee_rat3_scan']=$data['merchant_rate'];//
                $arr['bus_lic_no']=$data['business_license'];//营业执照号
                $arr['bus_exp_dt']=$data['license_time'];//营业执照有限期
                $arr['bse_lice_nm']=$data['name'];//营业执照名
                $arr['mercAdds']=$data['address'];//营业执照地址
                $arr['stoe_nm']=$data['address'].$data['name'];//签购单名称=省市+门店名
                $arr['mcc_cd']=$data['mcc_cd'];//mcc码
                $arr['stoe_area_cod']=$data['stoe_area_cod'];//地区码
                $arr['trm_rec']=5;//终端数量
                $arr['alipay_flg']="Y";//扫码产品
                $arr['yhkpay_flg']="Y";//银行卡产品
                $arr['tranTyps']="C1";//交易类型
                $arr['orgNo']="518";//合作商机构号
                $arr['crp_nm']=$data['contact'];//法人姓名

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
            $data['category']=AgentCategory::where('pid',0)->select();
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
        $id=request()->param('pid');
        $data=AgentCategory::where('pid',$id)->select();
        return_msg(200,'success',$data);
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
