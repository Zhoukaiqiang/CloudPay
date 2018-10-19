<?php

namespace app\merchant\model;

use think\Model;

class ShopActiveDiscount extends Model
{
    //
    protected $autoWriteTimestamp=true;
    protected $createTime = "start_time";
    protected $updateTime = false;

    public function getEndTimeAttr($v) {
        return date("Y-m-d H:i:s", $v);
    }

}
