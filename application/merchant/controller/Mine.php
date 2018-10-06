<?php

namespace app\merchant\controller;

use app\merchant\model\MerchantShop;
use think\Controller;
use think\Request;

class Mine extends Controller
{
    /**
     * 显示签约信息
     *
     * @return \think\Response
     */
    public function get_sign()
    {
        $rate['alipay_rate'] = 0.38;
        $rate['wx_rate'] = 0.38;
        $rate['union_rate'] = 0.55;
        return $rate;
    }


    /**
     * 显示我的资料.
     *
     * @return \think\Response
     */
    public function get_profile(Request $request)
    {
        if ($request->isGet()) {
            $query = $request->param();
            $res = MerchantShop::get($query['id'])->field('name, phone, role')->find();
            return json_encode($res);
        }
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
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
