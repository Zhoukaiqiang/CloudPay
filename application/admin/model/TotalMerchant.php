<?php

namespace app\admin\model;

use think\Model;

class TotalMerchant extends Model
{
    //
    public function getMerchantsTypeAttr($value)
    {
        //  0企业 1个体商户 2事业单位
        $attr_input_type = ['企业', '个体商户', '事业单位'];
        return $attr_input_type[$value];
    }

    public function getStatusAttr($value)
    {
        //账号状态 0开启 1关闭
        return $value ? '关闭':'开启';
    }

    public function getAccountTypeAttr($value)
    {
        //账户类型 0对公账户 1个体账户
        return $value ? '个体账户':'对公账户';
    }

    public function getChannelAttr($value)
    {
        //  当前通道 0支付宝 1微信 2支付宝间联 3微信间联
        $attr_input_type = ['支付宝', '微信', '支付宝','微信','微信 支付宝'];
        return $attr_input_type[$value];
    }
}
