<?php

namespace app\merchant\controller;

use app\agent\model\TotalQrcode;
use app\merchant\model\MerchantShop;
use think\Controller;
use think\Request;

class Qrcode extends Controller
{
    /**
     * 查询二维码是否绑定
     *
     * @return \think\Response
     */
    public function index()
    {

        $data = request()->param();

        TotalQrcode::where('id',$data['id'])->update(['partner_id'=>$data['user_id']]);

        $info = TotalQrcode::where('id',$data['id'])->field('shop_id')->find();

        if($info['shop_id'] == null) {
            return_msg(400,"二维码未激活");
        }else{
            $shop_name = MerchantShop::where('id',$info['shop_id'])->field('shop_name')->find();

            $this->redirect("http://pay.hzyspay.com/tag/pay?shop_id=".$info['shop_id']."&shop_name=".$shop_name['shop_name']);
        }
    }

    /**
     *绑定二维码
     * @param Request $request
     */
    public function active(Request $request)
    {
        $data = $request->param();
        $res = TotalQrcode::where('id',$data['id'])->field('qrcode')->find();
        $re = TotalQrcode::where('id',$data['id'])->find('shop_id');
        if($re['shop_id'] != null){
            return_msg(400,"此二维码已绑定",$res['qrcode']);
        }
        $info = TotalQrcode::where('id',$data['id'])->update(['shop_id'=>$data['shop_id']]);
        if ($info) {
            return_msg(200,"绑定成功",$res['qrcode']);
        }else{
            return_msg(400,"绑定失败");
        }
    }


}
