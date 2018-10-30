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
        return date("Y-m-d", $v);
    }

    public function getApplyNameAttr($v) {
        switch($v){
            case 0:
                return "全部";
                break;
            case 1:
                return "会员";
                break;
            case 0:
                return "非会员";
                break;
        }
    }
}
