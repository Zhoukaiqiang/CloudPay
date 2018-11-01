<?php

namespace app\merchant\controller;

use think\Controller;
use think\Request;

class Sweep extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $merchant_id=$request->param('merchant_id');
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($ua, 'MicroMessenger')) {
            $url="http://47.92.212.66/index.php/merchant/sweep/wxpay?merchant_id=$merchant_id";
            $this->redirect($url);
        }elseif (strpos($ua, 'AlipayClient')) {
            //支付宝链接
            $url="http://47.92.212.66/index.php/merchant/sweep/alipay?merchant_id=$merchant_id";
//            header('location: ' . $url);
            $this->redirect($url);
        }

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function wxpay(Request $request)
    {
        echo 1;
        echo $request->param('merchant_id');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function alipay(Request $request)
    {
        //
        echo 2;
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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
