<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/26
 * Time: 10:06
 */

namespace app\merchant\controller;


use app\agent\model\MerchantIncom;
use app\agent\model\Order;
use app\agent\model\TotalMerchant;
use app\merchant\model\MerchantShop;
use think\Controller;
use think\Request;

class Index extends Controller
{
    public $url='http://139.196.141.163:4243/emercapp';
    /**
     * 商户提现页面
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw_list(Request $request)
    {
        //获取商户id
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        //账户可提现余额
        $data=TotalMerchant::where('id',$merchant_id)->field(['money','password'])->select();
        if(Request::instance()->isGet()){
            return json_encode(['money'=>$data[0]['money']]);
        }else{
            //判断密码是否正确
            if($data[0]['password']==$request->param('password')){
                for ($i=0;$i<2;$i++){
                    if($i==0){
                        return_msg(200,'success','银行正在处理中');
                    }else{
                        $del=MerchantIncom::where('merchant_id',$merchant_id)
                            ->field(['a.serviceId','a.version','a.orgNo','a.merc_id',])
                            ->select();
                        $del=json_decode($del,true);
                        dump($del);
                        $stoe_id=MerchantShop::where('merchant_id',$merchant_id)->field(['id'])->select();


                    }

                }
            }else{
                return_msg(400,'error','密码错误，请重新输入');
            }
        }
    }

    /**
     * 开通提现权限接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw()
    {
        $merchant_id=17;
        $del=MerchantIncom::where('merchant_id',$merchant_id)
            ->field(['serviceId','version','orgNo','mercId'])
            ->select();
        $del=$del[0];
        $stoe_id=MerchantShop::where('merchant_id',$merchant_id)->field(['stoe_id'])->select();
        $regBody['merc_id']=$del['mercId'];
        foreach ($stoe_id as $k=>$v){
            $regBody['stoe_id'][]=$v['stoe_id'];
        }
        $regBod[]=$regBody;
        $del['regBody']=$regBod;

        $sign_ature = sign_ature(0000, $del);
        $del['signValue']=$sign_ature;
        $del['signType']='MD5';
        $del=json_encode($del);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $del, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);

        if ($par[ 'repCode' ] == '000000') {
            if ($par[ 'signValue' ] == $return_sign) {
                Db::table('merchant_shop')->whereIn('stoe_id',$del['stoe_id'])->update(['sts'=>1,'serviceTime'=>$par['serviceTime']]);
                return_msg(200, 'error',$par['msg_dat']);
            } else {
                return_msg(400, 'error',$par['msg_dat']);
            }
        } else {
            Db::table('merchant_shop')->whereIn('stoe_id',$del['stoe_id'])->update(['sts'=>0]);

            return_msg(500, 'error',$par['msg_dat']);
        }

    }
}