<?php

namespace app\merchant\model;

use think\Model;

class ShopActiveRecharge extends Model
{
    //
    protected $autoWriteTimestamp=true;
    protected $createTime = "start_time";
    protected $updateTime = false;

    public function getEndTimeAttr($v) {
        return date("Y-m-d", $v);
    }
}
