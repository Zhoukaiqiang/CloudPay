<?php

namespace app\admin\model;

use think\Model;

class TotalAdmin extends Model
{
    protected $createTime = "create_time";
    protected $updateTime = false;
    public function getRoleIdAttr($value)
    {
        //0 运营人员 1运营总监
        return $value ? '运营总监':'运营人员';
    }

    /**
     * 过滤获取的值--状态
     * @param $value
     * @return string
     */
//    public function getStatusAttr($value)
//    {
//        return $value ? '启用' : '停用';
//    }

}
