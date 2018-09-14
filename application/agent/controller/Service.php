<?php

namespace app\agent\controller;

use app\agent\model\TotalAgent;
use app\agent\model\TotalMerchant;
use think\Controller;
use think\Request;

class Service extends Controller
{
    /**
     * 显示服务商列表
     *
     * @return \think\Response
     */
    public function service_list()
    {
        //获取上级代理商id
        $id=session('agent_id');
        //获取所有行数
        $rows=TotalAgent::where('parent_id',$id)->count();
        $pages=page($rows);
        //获取当前代理商下的所有子代理
        $data=TotalAgent::field(['id,agent_name,contact_person,agent_phone,agent_mode,agent_area,create_time,status'])->where('parent_id',$id)->limit($pages['offset'],$pages['limit'])->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 启用子代
     *
     * @return \think\Response
     */
    public function open_agent()
    {
        //获取当前代理商id
        $id=request()->param('id');
        $result=TotalAgent::where('id',$id)->update(['status'=>1]);
        if($result){
            return_msg(200,'启用成功');
        }else{
            return_msg(400,'启用失败');
        }
    }

    /**
     * 停用子代
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function stop_agent(Request $request)
    {
        //
        $data=$request->post();
        $data['status']=0;
        $result=TotalAgent::update($data);
        if($result){
            return_msg(200,'停用成功');
        }else{
            return_msg(400,'停用失败');
        }
    }

    /**
     * 子代详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function agent_detail()
    {
        //
        if(request()->isPost()){
            //获取上级代理商id
            $data=request()->post();
            //验证
            //判断是否上传新图片
            $file=request()->file('contract_picture');
            if(!empty($file)){
                $data['contract_picture']=$this->upload_logo();
                $data['contract_picture']=json_encode($data['contract_picture']);
            }
            $result=TotalAgent::update($data,true);
            if($result){
                return_msg(200,'修改成功');
            }else{
                return_msg(400,'修改失败');
            }
        }else{
            //获取子代id
            $id=request()->param('id');
            //通过子代id查询子代信息
            $data=TotalAgent::where('id',$id)->find();
            //解析图片
            $data['contract_picture']=json_decode($data['contract_picture']);
            return_msg(200,'success',$data);
        }
    }

    /**
     * 新增子代
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function agent_add()
    {
        if(request()->isPost()){
            $data=request()->post();
            //获取上级代理商id
            $data['parent_id']=session('agent_id');
            //验证
            //上传图片
            $data['contract_picture'] = $this->upload_logo();
            $data['contract_picture'] = json_encode($data['contract_picture']);
            //保存
            $info = TotalAgent::create($data, true);
            if($info){
                return_msg(200,'添加成功');
            }else{
                return_msg(400,'添加失败');
            }
        }else{
            return_msg(200,'success');
        }
    }

    /**
     * 服务商商户
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function service_merchant(Request $request)
    {
        //获取上级代理商id
        $id=session('agent_id');
        //获取总行数
        $rows=TotalAgent::where('parent_id',$id)->count();
        //分页
        $pages=page($rows);
        //获取子代中的商户信息
        $data=TotalMerchant::alias('a')
            ->field('a.id,a.name,a.address,a.channel,a.status,b.agent_name,b.agent_phone,a.opening_time')
            ->join('cloud_total_agent b','a.agent_id=b.id','left')
            ->where(['b.parent_id'=>$id])
            ->limit($pages['offset'],$pages['limit'])
            ->select();
        $data['pages']=$pages;
        return_msg(200,'success',$data);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
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
                return_msg(400,$error);
            }
        }
        return $goods_pics;
    }
}
