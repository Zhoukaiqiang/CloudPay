<?php

namespace app\merchant\model;

use think\Model;

class MemberRecharge extends Model
{
    public function getPayTypeAttr($val)
    {
        switch ($val) {
            case 'ALIPAY':
                return "支付宝";
                break;
            case 'WXPAY':
                return "微信";
                break;
            case 'YLPAY':
                return "银联";
                break;
            case 'PAY':
                return "现金";
                break;
        }
    }

    public function getStatusAttr($val)
    {
        switch ($val) {
            case 0:
                return "充值失败";
                break;
            case 1:
                return "充值成功";
                break;
        }
    }
}
