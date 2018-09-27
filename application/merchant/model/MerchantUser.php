<?php

namespace app\merchant\model;

use think\Model;

class MerchantUser extends Model
{
    //
    public function getRoleAttr($value)
    {
        //
        $attr_input_type = ['','服务员','店长','收银员'];
        return $attr_input_type[$value];
    }
}
