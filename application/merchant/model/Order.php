<?php

namespace app\merchant\model;

use think\Model;

class Order extends Model
{
    public function getStatusAttr($value)
    {
        //
        $attr_input_type = ['未支付','已支付','已关闭','会员充值'];
        return $attr_input_type[$value];
    }

    public function getPayTypeAttr($value)
    {
        //
        $attr_input_type = [
            'alipay'=>'支付宝',
            'wxpay'=>'微信',
            'etc'=>'银联'
        ];
        return $attr_input_type[$value];
    }
}
