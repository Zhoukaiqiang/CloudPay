<?php

namespace app\admin\model;

use think\Model;

class TotalCapital extends Model
{
    //
    public function getInvoiceAttr($value)
    {
        //0 运营人员 1运营总监
        return $value ? '无发票':'有发票';
    }
}
