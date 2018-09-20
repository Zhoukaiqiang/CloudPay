<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/9/18
 * Time: 14:22
 */

namespace app\agent\controller;


use think\Controller;
use think\Request;
use think\Db;

class Payment extends Controller
{
    //商户申请修改
    public function mermachant_edit(Request $request)
    {
        $id=$request->param('id');
        $serviceId=6060605;
        $version='v1.0.1';
        $data=Db::name('merchant_store')->where('merchant_id',$id)->field('mercId,orgNo')->select();
        $resul=md5($serviceId.$version.$data[0]['mercId'].$data[0]['orgNo'].'AFDFAASDASDAS');

        $arr=['serviceId'=>$serviceId,'version'=>$version,'mercId'=>$data[0]['mercId'],'orgNo'=>$data[0]['orgNo'],'signValue'=>$resul];

       $par= curl_request('zhdj.zhonghetc.com/api/mercha',true,json_encode($arr),true);

       $bbntu=json_decode($par);
       if($bbntu['msg_cd']===000000 && $data[0]['mercId']==$bbntu['mercId']){
            $statu=Db::table('merchant_store')->where('merchant_id',$id)->update(['store_sn'=>$bbntu['stoe_id'],'log_no'=>$bbntu['log_no'],'status'=>1]);
            return_msg(200,'商户修改申请成功');
       }else{
           return_msg(400,'商户修改申请失败');
       }

    }
    //商户资料修改
    public function commercial_edit(Request $request)
    {
//        $del=$request->post();
//
//        $aa['serviceId']=6060604;
//        $aa['version']='V1.0.1';
//        $data=Db::name('merchant_store')->where('merchant_id',$del['merchant_id'])->field('log_no,mercId,store_sn')->select();
//        $aa=[];
//        foreach ($data as $k=>$v)
//        {
//            $aa=$v;
//        }
//        $dells=$del+$aa;
        $key='AFDFAASDASDAS';
//        //获取加密的签名域
//        $sign_ature=$this->sign_ature(0000,$dells,$key);
//        $del['signValue']=$sign_ature;
        //向新大陆接口发送信息验证
        $par= curl_request('zhdj.zhonghetc.com/api/ecit',true,$key,true);

        $par=json_decode($par);
        dump($par);
        //返回数据的签名域
//        $signreturn=$this->sign_ature(1111,$par['data'],$key);
//        //&& $par['signValue']==$signreturn
//        if($par['msg_cd']==000000){
////            $rebul=Db::table('think_user')->where('merchant_id',$del['merchant_id'])->update($del);
//           return_msg(200,修改成功);
//        }else{
//            return_msg(400,'修改失败');
//        }




    }
    public function sign_ature($ids,$arr,$key)
    {
        ksort($arr);
        if($ids==0000){
            $data=['serviceId','stoe_id','log_no','mercId','version','stl_sign','orgNo','stl_oac','bnk_acnm','wc_lbnk_no',
                'bus_lic_no','bse_lice_nm','crp_nm','mercAdds','bus_exp_dt','crp_id_no','crp_exp_dt','stoe_nm','stoe_cnt_nm'
                ,'stoe_cnt_tel','mcc_cd','stoe_area_cod','stoe_adds','trm_rec','mailbox','alipay_flg','yhkpay_flg'];
            $stra='';
            foreach ($arr as $key=>$val)
            {
                if(in_array($key,$data)){
                    $stra.=$val;
                }
            }
        }else if($ids==1111){
            $data=['check_flag','msg_cd','msg_dat','mercId','log_no','stoe_id','mobile','sign_stats','deliv_stats'];
            $stra='';
            foreach ($arr as $key=>$val)
            {
                if(in_array($key,$data)){
                    $stra.=$val;
                }
            }
        }
        return md5($stra.$key);
    }
    public function payment_result(Request $request)
    {
        $json_data=file_get_contents('php://input');
//        var_dump($json_data);
        $bb=[
    "msgTp"=> "0210",
    "code"=> "0",
    "message"=> "交易成功",
    "payTp"=> "0",
    "timeStamp"=> "20171201180213",
    "merName"=> "上海市隆力奇蛇油世博店",
    "merId"=> "888888888888888",
    "termId"=> "88888888",
    "batchNo"=> "000119",
    "traceNo"=> "000044",
    "transDate"=> "20171201",
    "transTime"=> "180213",
    "transAmount"=> "0.01",
    "operator"=> "001",
    "referNo"=> "120100141223",
    "acqNo"=> "48570000",
    "iisNo"=> "建设银行",
    "expDate"=> "0000",
    "cardNo"=> "621700*********6354",
    "cardType"=> "00",
];
        $bb=json_encode($bb);
        $key = 'jJ18kfFvXqtPN*****';//md5钥匙
        $privateKey ="-----BEGIN PRIVATE KEY----- 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 KFpQd9yOpyYpEXGQfcyCxENEowhb4S0= -----END PRIVATE KEY----- ";//私钥
        $public_key="-----BEGIN PUBLIC KEY----- MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA0jaDXt0XP+THm7Oe67Ad x8R5UKUBqI87ypdBIRCcfB+mzxlT3GqKyknWy8yBN6W++0VdN3hhCHbfk5bDaFhE OfCsyNwfenyrvy4i3gpmWmfFMDm2dm4HiTai9KWODMNKyss3EKrK9425a+PNKbAl IpeJ7c7xsjLQhmkwDamOEt/5HKfaP5xZBqLyLFhPTPunD1p4TQPKT9XAEoaYL2AG 1A3MGNdJpVJm22aXXx0qdp8TM5R+XQHG35tAve+sL+OEV06K4I+hXmMOvXz7eT3s ZQ6cNJoay5hqcotQarGcY6gJw2p0r3+kePEW7FO1YRwsRQ18LcEatRWawqELOqIM d0SAy9qRcT+DhsUPH+UMRIorliVDwD2s0a3F9adKK4/qnBolrtkn7NmQ++FpcsMt K+YJs/nfIUpbPk3ikfGh0XtgfT0fT9I2ptrvAbDzrvwRmatYhSjUF9bdnGzMsIiH jUGDyE2Eov6gW3KQ6qMR21y8Pk4zFQat4jArnCeKaX3rv9LAszIrJ765oZMhM2JB exETBJEwbh4H39MuoTnJEJ3WTxR/zWXo6Zpy2Y3gevpwA/KHmOZvJzp344PxEqPo IL8IFt8oeIviH11BqEssdMRYy+y2ndUDiwfUlyL+c2g98+7GNVANbfGvwT3KJExT MuhRAEaco4U2aQ+vJXY6luMCAwEAAQ== -----END PUBLIC KEY----- ";
        $encrypt='';
        openssl_public_encrypt($bb,$encrypt,$public_key);
        $aa=bin2hex($encrypt);
//        $aa=bin2hex(openssl_private_decrypt(strtoupper(md5($datas.$key))));
        var_dump($aa);
        $origin_data = json_decode($json_data, true);

        if(strtoupper(md5($origin_data['Datas'].$key)) != $origin_data['signValue']) {
            exit('验签失败');
        }

        $encode_data_arr = str_split($origin_data['Datas'], 256);
        $decode_data_all = '';
        foreach($encode_data_arr as $value) {
            openssl_private_decrypt(hex2bin($value), $decode_data, $privateKey);
            $decode_data_all .= $decode_data;
        }
        $data = json_decode($decode_data_all, true);//解密后数据（数组格式）

        print_r($data);
    }

    public function key()
    {
        $opensslConfigPath = "D:/phpStudy/Apache/conf/openssl.cnf";
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,           //字节数  512 1024 2048  4096 等
            "private_key_type" => OPENSSL_KEYTYPE_RSA,   //加密类型
            "config" => $opensslConfigPath,
        );

        $res = openssl_pkey_new($config);

        if ($res == false) return false;
        openssl_pkey_export($res, $private_key, null, $config);
        $public_key = openssl_pkey_get_details($res);
        $public_key = $public_key[ "key" ];
        var_dump($private_key);
        var_dump($public_key);

        file_put_contents("/cert_public.key", $public_key);
        file_put_contents("/cert_private.pem", $private_key);

    }

       public function encrypt(){
            $bb=[
                "msgTp"=> "0210",
                "code"=> "0",
                "message"=> "交易成功",
                "payTp"=> "0",
                "timeStamp"=> "20171201180213",
                "merName"=> "上海市隆力奇蛇油世博店",
                "merId"=> "888888888888888",
                "termId"=> "88888888",
                "batchNo"=> "000119",
                "traceNo"=> "000044",
                "transDate"=> "20171201",
                "transTime"=> "180213",
                "transAmount"=> "0.01",
                "operator"=> "001",
                "referNo"=> "120100141223",
                "acqNo"=> "48570000",
                "iisNo"=> "建设银行",
                "expDate"=> "0000",
                "cardNo"=> "621700*********6354",
                "cardType"=> "00",
            ];
            $bb=json_encode($bb);
            $config = array(
                "private_key_bits" => 1024,                     //字节数    512 1024  2048   4096 等
                "private_key_type" => OPENSSL_KEYTYPE_RSA,     //加密类型
                "config" => "D:/xampp/apache/conf/openssl.cnf"
            );

            $privkeypass = '123456789'; //私钥密码
            $numberofdays = 365;     //有效时长
            $cerpath = "./test.cer"; //生成证书路径
            $pfxpath = "./test.pfx"; //密钥文件路径

            $dn = array(
                "countryName" => "UK",
                "stateOrProvinceName" => "Somerset",
                "localityName" => "Glastonbury",
                "organizationName" => "The Brain Room Limited",
                "organizationalUnitName" => "PHP Documentation Team",
                "commonName" => "Wez Furlong",
                "emailAddress" => "wez@example.com"
            );
// 生成公钥私钥资源
            $res = openssl_pkey_new($config);

// 导出私钥 $priKey
            openssl_pkey_export($res, $priKey,null,$config);

//  导出公钥 $pubKey
            $pubKey = openssl_pkey_get_details($res);
            $pubKey = $pubKey["key"];
//print_r($priKey); 私钥
//print_r($pubKey); 公钥
//直接测试私钥 公钥
            $data = '直接测试成功!';
// 加密
            openssl_public_encrypt($data, $encrypted, $pubKey);

    }

}