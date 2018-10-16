<?php

namespace app\merchant\model;

use think\Model;

class Order extends Model
{
    public function getStatusAttr($value)
    {
        //
        switch($value){
            case 0:
                return "未支付";
                break;
            case 1:
                return "已支付";
                break;
            case 2:
                return "退款成功";
                break;
            case 3:
                return "退款中";
                break;
            case 3:
                return "退款失败";
                break;
        }
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
