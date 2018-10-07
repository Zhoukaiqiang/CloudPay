<?php

namespace app\agent\model;

use think\Model;
use traits\model\SoftDelete;

class AgentPartner extends Model
{
    use SoftDelete;
    protected $deleteTime="delete_time";
//    public function getModelAttr($value)
//    {
////        账号状态 0开启 1关闭
//        return $value ? '按费率':'按比例';
//    }
}
