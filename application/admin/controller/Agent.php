<?php

namespace app\admin\controller;

use app\admin\model\TotalAgent;
use think\Controller;
use think\Request;

class Agent extends Controller
{
    /**
     * 代理商首页
     *
     * @return \think\Response
     */
    public function index()
    {
            //取出数据表中的数据
            $data=TotalAgent::field(['id','agent_name','contact_person','agent_mode','agent_area','admin_id','create_time','contract_time','status'])->select();
            return_msg('200','success',$data);
    }

    /**
     * 新增代理商
     * @param  $contract_time 合同有效期
     * @param  $contract_picture 合同图片
     * @return \think\Response
     */
    public function add()
    {
            $data=request()->post();
            dump($data);die;
            /*$rule=[
                'agent_mode'    =>'require',
                'agent_name'    =>'require',
                'contact_person'=>'require',
                'detailed_address'=>'require',
                'admin_id'      =>'require',
                'username'      =>'require',
                'password'      =>'require',
                'open_bank'     =>'require',
                'open_bank_branche'=>'require',
                'home'          =>'require',
                'account'       =>'require',
                'account_name'  =>'require',
                'agent_area'    =>'require',
                'agent_money'   =>'require|number',
                'contract_time' =>'require',
                'alipay_rate'   =>'number|max:0.6',
                'wechat_rate'   =>'number|max:0.6',
                'contract_picture'=>'require',
                'phone'           =>'require|regex:/^1[3-9]\d{9}$/|unique:total_agent',
            ];
            $msg=[
                'agent_mode.require'             =>'代理方式必填',
                'agent_name.require'             =>'代理商名称必填',
                'contact_person.require'        =>'请填写联系人',
                'detailed_address.require'      =>'请填写详细地址',
                'admin_id.require'              =>'请选择运营人员',
                'username.require'              =>'请填写登录账号',
                'password.require'              =>'请填写登录密码',
                'open_bank.require'              =>'请填写开户行名称',
                'open_bank_branche.require'     =>'请填写开户行网点',
                'home.require'                    =>'请选择所在地',
                'account.require'                =>'请填写账户号',
                'account_name.require'           =>'请填写账户名',
                'agent_area.require'             =>'请选择代理范围',
                'agent_money.require'            =>'请输入代理费用',
                'agent_money.number'            =>'代理费用必须是数字',
                'contract_time.require'        =>'请选择合同期限',
                'alipay_rate.number'            =>'支付宝间联费用必须是数字',
                'alipay_rate.max'               =>'支付宝间联费用最高是0.6',
                'wechat_rate.number'            =>'微信间联费用必须是数字',
                'wechat_rate.max'               =>'微信间联费用最高是0.6',
                'contract_picture.require'      =>'请上传合同图片',
                'phone.require'                 =>'请填写联系方式',
                'phone.regex.require'          =>'联系方式格式不正确',
                'phone.unique.require'         =>'手机号已存在'
            ];
            $validate=new Validate($rule,$msg);
            if($validate->check($data)){*/
            //上传图片
            $data['contract_picture']=$this->upload_logo();
            $data['contract_picture']=json_encode($data['contract_picture']);
//            $data['contract_time']=strtotime($data['contract_time']);
            //保存到数据表
            $info=TotalAgent::create($data,true);
            if($info){
                //保存成功
                return_msg('200','保存成功',$info);
            }else{
                //保存失败
                return_msg('400','保存失败');
            }
            /* }else{
                 $error=$validate->getError();
                 $this->error($error);
             }*/

    }


    /**
     * 修改代理商.
     *
     * @param  $contract_time 合同有效期
     * @param  $contract_picture 合同图片
     * @return \think\Response
     */
    public function edit(Request $request)
    {
        //
        if(request()->isPost()){
            $data=$request->post();
            //上传图片
            $data['contract_picture']=$this->upload_logo();
            $data['contract_picture']=json_encode($data['contract_picture']);
//            $data['contract_time']=strtotime($data['contract_time']);
            //保存到数据表
            $info=TotalAgent::where('id','=',$data['id'])->update($data,true);
            if($info){
                //保存成功
                return_msg('200','修改成功',$info);
            }else{
                //保存失败
                return_msg('400','修改失败');
            }
        }else{
            $id=request()->param('id');
            $data=TotalAgent::where('id',$id)->find();
            return_msg(200,'success',$data);
        }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除代理商
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $result=TotalAgent::destroy($id);
        if($result){
            //删除成功
            $this->success('删除成功','index');
        }
    }

    //上传图片
    private function upload_logo(){
        $files=request()->file('contract_picture');
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
                $this->error($error);
            }
        }
        return $goods_pics;
    }
}
