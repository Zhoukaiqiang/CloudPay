<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/27
 * Time: 14:06
 */
namespace app\merchant\controller;

use app\agent\model\MerchantIncom;
use app\agent\model\Order;
use app\agent\model\TotalMerchant;
use app\merchant\model\MerchantShop;
use think\Controller;
use think\Db;
use think\Request;

class Deposit extends Controller
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
        $merchant_id=3;
        //账户可提现余额
        $data=TotalMerchant::where('id',$merchant_id)->field(['money','password'])->select();
//        return json_encode($data);
        if(Request::instance()->isGet()){
            return json_encode(['money'=>$data[0]['money']]);
        }else{
            $money=$request->param('money');
            //判断密码是否正确
//
            if($data[0]['password']==md5($request->param('password'))){

                //orgNo机构号 tot_fee提现费率 mercId商户编号 stl_oac结算卡号 wc_lbnk_no开户行
                $del=MerchantIncom::where('merchant_id',$merchant_id)
                    ->field(['orgNo','mercId','stoe_id','tot_fee','stl_oac','wc_lbnk_no'])
                    ->find();
                //总手续费
                $tot_fee=$money*$del['tot_fee'];
                //流水号
                list($usec, $sec) = explode(" ", microtime());
                $times=str_replace('.','',$usec + $sec);
                $timese=date('YmdHis',time());
                $serial_number=$timese.$times;

                $bese=['serial_number'=>$serial_number,'poundage'=>$tot_fee,'money'=>$money,'status'=>2,'create_time'=>time(),'bank'=>$del['wc_lbnk_no'],'bank_card'=>$del['stl_oac'],'merchant_id'=>$merchant_id];
                //提现记录id
                $id=Db::name('merchant_withdrawal')->insertGetId($bese);
                if(!$id){
                    return_msg(400,'error','提现失败');
                }
                //体现处理中返回的数据 bank开户行 bank_card结算卡号 money提现金额 poundage手续费
                $resu=['bank'=>$del['wc_lbnk_no'],'bank_card'=>$del['stl_oac'],'money'=>$money,'poundage'=>$tot_fee];
                for ($i=0;$i<2;$i++){
                    if($i==0){
                        return_msg(200,'success',$resu);
                    }else{

                        $par=$this->confirm_withdrawal($money,$del,$tot_fee);
                        //返回数据的签名域
                        $return_sign = sign_ature(1111, $par);
                        if ($par[ 'repCode' ] == '000000') {
                            if ($par[ 'signValue' ] == $return_sign) {
                                Db::name('merchant_withdrawal')->update(['end_time'=>$par['serviceTime'],'status'=>1,'id'=>$id]);
                                return_msg(200, 'success',$par['repMsg']);
                            } else {
                                Db::name('merchant_withdrawal')->update(['end_time'=>$par['serviceTime'],'status'=>3,'id'=>$id,'repMsg'=>$par['repMsg']]);

                                return_msg(400, 'error',$par['repMsg']);
                            }
                        } else {
                            Db::name('merchant_withdrawal')->update(['end_time'=>$par['serviceTime'],'status'=>3,'id'=>$id,'repMsg'=>$par['repMsg']]);

                            return_msg(500, 'error',$par['repMsg']);
                        }

                    }

                }
            }else{
                return_msg(400,'error','密码错误，请重新输入');
            }
        }
    }

    /**
     * 提现记录
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_record()
    {
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        $data=Db::name('merchant_withdrawal')->where(['merchant_id'=>$merchant_id])->select();
        return json_encode($data);
    }

    /**
     * 提现记录 查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_record_query(Request $request)
    {
        $merchant_id=session('merchant_id');
        $merchant_id=1;
        //提现方式 1普通提现 2自动提现 3快速提现 0全部方式
        $way=$request->param('way') ? $request->param('way') : 0;
        $waysymo='=';
        if($way==0){
            $waysymo='<>';
        }
        //提现状态 1 提现成功 2提现中 3提现失败 0全部方式
        $status=$request->param('status') ? $request->param('status') : 0;
        $statussyom='=';
        if($status==0){
            $statussyom='<>';
        }
        //时间
        $create_time=$request->param('create_time') ? strtotime($request->param('create_time')) : 1533950117;
        $end_time=$request->param('end_time') ? strtotime($request->param('end_time'))+60*60*24-1 : 1912641317;

        $data=Db::name('merchant_withdrawal')
            ->where(['merchant_id'=>$merchant_id,'status'=>[$statussyom,$status],'way'=>[$waysymo,$way],
            'create_time'=>['between time',[$create_time,$end_time]]])
            ->select();
        if($data){
            return_msg(200,'success',json_encode($data));

        }else{
            return_msg(400,'error','查无此记录');
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
        $stor_ids=import(',',$del['stoe_id']);
        $stor_ids=rtrim($stor_ids,',');
        if ($par[ 'repCode' ] == '000000') {
            if ($par[ 'signValue' ] == $return_sign) {
                Db::table('merchant_shop')->whereIn('stoe_id',$stor_ids)->update(['sts'=>1,'serviceTime'=>$par['serviceTime']]);
                return_msg(200, 'success',$par['repMsg']);
            } else {
                return_msg(400, 'error',$par['repMsg']);
            }
        } else {
            Db::table('merchant_shop')->whereIn('stoe_id',$stor_ids)->update(['sts'=>0]);

            return_msg(500, 'error',$par['repMsg']);
        }

    }

    /**
     * 获取提现信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Withdrawal_information()
    {
        $merchant_id=session('merchant_id');
        $del=MerchantIncom::where('merchant_id',$merchant_id)
            ->field(['orgNo','mercId'])
            ->select();
        $del=$del[0];
        $arr=['serviceId'=>'6060661','version'=>'V1.0.0','orgNo'=>$del['orgNo'],'merc_id'=>$del['mercId'],'signType'=>'MD5'];

        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue']=$sign_ature;
        $arr=json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);
        //$par['respBody']  商户的json数组
        if ($par[ 'repCode' ] == '000000') {
            if ($par[ 'signValue' ] == $return_sign) {
                return_msg(200, 'success',$par['repMsg']);
            } else {
                return_msg(400, 'error',$par['repMsg']);
            }
        } else {
            return_msg(500, 'error',$par['repMsg']);
        }
    }

    /**
     * 确定提现
     * @param $money
     * @param $del
     * @param $tot_fee
     * @return mixed|string
     */
    public function confirm_withdrawal($money,$del,$tot_fee)
    {


        //mercId商户编号 stoe_id门店编号 txn_amt提现金额 pre_fee预估手续费
        $reqBody=['merc_id'=>$del['merc_id'],'stoe_id'=>$del['stoe_id'],'txn_amt'=>$money,'pre_fee'=>$tot_fee];
        //serviceId交易码  version版本号 tot_amt总金额 tot_fee总手续费
        $arr=['serviceId'=>'6060662','version'=>'V1.0.0','orgNo'=>$del['orgNo'],'signType'=>'MD5','tot_amt'=>$money,'tot_fee'=>$tot_fee];
        //json数组
        $arr['reqBody']=json_encode($reqBody);
        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue']=$sign_ature;
        $arr=json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        return $par;

    }

    /**
     * 2.4	提现结果查询
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function results_query(Request $request)
    {
        //获取日期
        $ac_dt=$request->param('create_time');
        $merchant_id=session('merchant_id');
        $del=MerchantIncom::where('merchant_id',$merchant_id)
            ->field(['orgNo','mercId'])
            ->select();
        $del=$del[0];
        $arr=['serviceId'=>'6060661','version'=>'V1.0.0','orgNo'=>$del['orgNo'],'merc_id'=>$del['mercId'],'signType'=>'MD5'];
        if($ac_dt){
            $arr['ac_dt']=$ac_dt;
        }
        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue']=$sign_ature;
        $arr=json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);
        //$par['respBody']  商户的json数组
        if ($par[ 'repCode' ] == '000000') {
            if ($par[ 'signValue' ] == $return_sign) {
                return_msg(200, 'success',$par['repMsg']);
            } else {
                return_msg(400, 'error',$par['repMsg']);
            }
        } else {
            return_msg(500, 'error',$par['repMsg']);
        }
    }

    public function close_jurisdiction(Request $request)
    {

        //获取门店号
        $shop_id=$request->param('shop_id');
        $merchant_id=session('merchant_id');
        $del=Db::name('merchant_incom')->alias('a')
            ->field(['a.orgNo','a.mercId','b.stoe_id'])
            ->join('merchant_shop b','a.merchant_id=b.merchant_id','left')
            ->where(['merchant_id'=>$merchant_id,'b.id'=>['in',$shop_id]])
            ->select();

        $arr=['serviceId'=>'6060661','version'=>'V1.0.0','orgNo'=>$del[0]['orgNo'],'signType'=>'MD5'];
        $array=[];
        foreach ($del as $k=>$v){
            $array[]=$v['stoe_id'];
        }
        $arr['reqBody']['stoe_id']=$array;
        $arr['reqBody']['merc_id']=$del[0]['mercId'];

        $sign_ature = sign_ature(0000, $arr);
        $arr['signValue']=$sign_ature;
        $arr=json_encode($arr);
        //新大陆开启提现权限接口
        $par = curl_request($this->url, true, $arr, true);

        $par = json_decode($par, true);
        //返回数据的签名域

        $return_sign = sign_ature(1111, $par);
        //$par['respBody']  商户的json数组
        if ($par[ 'repCode' ] == '000000') {
            if ($par[ 'signValue' ] == $return_sign) {
                return_msg(200, 'success',$par['repMsg']);
            } else {
                return_msg(400, 'error',$par['repMsg']);
            }
        } else {
            return_msg(500, 'error',$par['repMsg']);
        }
    }
}