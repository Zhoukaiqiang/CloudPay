<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class TotalAgent extends Model
{
    //
    use SoftDelete;
    protected $deleteTime="delete_time";

    public function getStatusAttr($value)
    {
        //账号状态 0开启 1关闭
        return $value ? '启用':'停用';
    }
}
