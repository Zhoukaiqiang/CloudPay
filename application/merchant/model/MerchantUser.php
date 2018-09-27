<?php

namespace app\merchant\model;

use think\Model;

class MerchantUser extends Model
{
    public function getRoleAttr($val) {
        switch ($val) {
            case 1:
                return '服务员';
                break;
            case 2:
                return '店长';
                break;
            case 3:
                return '收银员';
                break;
        }
    }
}
