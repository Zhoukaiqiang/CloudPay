<?php

namespace app\admin\controller;

use alipay\Pagepay;
use app\admin\model\TotalAgent;
use think\Controller;
use think\Loader;
use think\Request;

class Order extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 支付接口
     *
     * @param  \think\Request  [pay_type] 支付方式
     * @return \think\Response
     */
    public function pay(Request $request)
    {
        //接收参数
//        $data=$request->param();
//        $url=EXTEND_PATH."pay/alipay/pagepay/pagepay.php";
//        echo $url;die;
//        $url=str_replace('\\','/',$url);
//        echo $url;die;
        $out_trade_no=time().rand(1000,9999);
        $order=array(
            'out_trade_no'=>$out_trade_no,
            'total_amount'=>0.01,
            'subject'=>'宝宝'
        );
        $params = [
            'out_trade_no' => $order['out_trade_no'],
            'total_amount' => $order['total_amount'],
            'subject'      =>$order['subject']
        ];
        Pagepay::pay($params);
        /*switch($data['pay_type']){
            case 'alipay':
                //支付宝
                //跳转到支付宝支付页面
                $html = "<form id='alipayment' action='/pay/alipay/pagepay/pagepay.php' method='post' style='display:none'>
<input id='WIDout_trade_no' name='WIDout_trade_no' value='123465789' />
<input id='WIDsubject' name='WIDsubject' value='云商付订单'/>
<input id='WIDtotal_amount' name='WIDtotal_amount' value='123' />
<input id='WIDbody' name='WIDbody' value='没有货可发'/>
</form><script>document.getElementById('alipayment').submit();</script>";
                echo $html;
                break;
            case 'wechat':
                //微信
                echo '支付成功';
                break;
            case 'cart':
                //银联
                echo '支付成功';
                break;
        }*/
    }

    /**
     * 支付宝回调地址
     *
     * @param
     * @return \think\Response
     */
    public function callback(Request $request)
    {
        //接收参数
        $data=$request->get();
        unset($data['/admin/order/callback']);
//        dump($data);die;
        //验签
        $config = config('alipay');
        Loader::import('pay.alipay.pagepay.service.AlipayTradeService');
        $alipaySevice = new \AlipayTradeService($config);
//        dump($alipaySevice);die;
        $result=$alipaySevice->check($data);
        if($result){
            echo 'success';die;
            //验证成功
            $order_sn=$data['out_trade_no'];
            $total_amount=$data['total_amount'];
            //跳转到付款成功页面
            return view('paysuccess', ['total_amount' => $total_amount]);
        }else{
            //验证失败
            echo 'false';die;
            return view('payfail', ['error' => '支付失败，请稍后再试']);
        }
    }

    //异步通知地址
    public function notify()
    {
        $data=request()->param();
        //验证签名  验签
        require_once("./plugins/alipay/config.php");
        require_once './plugins/alipay/pagepay/service/AlipayTradeService.php';
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($data);
        if($result){
            //验证成功  修改订单状态
            if($data['trade_status'] == 'TRADE_FINISHED') {
                echo 'success';die;

            }else if ($data['trade_status'] == 'TRADE_SUCCESS') {
                //检测 订单金额 是否正确
                //修改订单状态为 已付款
                echo 'success';die;
            }
        }else{
            //验证失败
            echo 'fail';die;
        }
    }
    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
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
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
