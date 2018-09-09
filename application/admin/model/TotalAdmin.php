<?php

namespace app\admin\model;

use think\Model;

class TotalAdmin extends Model
{
    public function getRoleIdAttr($value)
    {
        //0 运营人员 1运营总监
        return $value ? '运营总监':'运营人员';
    }

    public function getStatusAttr($value)
    {
        return $value ? '启用' : '停用';
    }
}
