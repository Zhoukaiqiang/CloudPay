<?php

namespace app\merchant\model;

use think\Model;

class ShopActiveExclusive extends Model
{
    //
    protected $autoWriteTimestamp=true;
    protected $createTime = "start_time";
    protected $updateTime = false;

    public function getEndTimeAttr($v) {
        return date("Y-m-d H:i:s", $v);
    }

    public function getCreateTimeAttr($v)
    {
        return date("Y-m-d H:i:s", $v);
    }

    /*public function getRegisterStatusAttr($v)
    {
        switch($v){
            case 0:
                return '新会员注册';
                break;
            case 1:
                return '老会员推荐新会员注册';
                break;
            case 2:
                return '';
                break;
            default:
                return "";
                break;
        }
    }*/
}
