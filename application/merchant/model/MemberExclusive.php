<?php

namespace app\merchant\model;

use think\Model;

class MemberExclusive extends Model
{
    //
    protected $autoWriteTimestamp=true;
    protected $createTime = "cancel_time";
    protected $updateTime = false;

    public function getStatusAttr($v)
    {
        switch($v){
            case 0:
                return '已核销';
                break;
            case 1:
                return '进行中';
                break;
        }
    }

    public function getStartTimeAttr($v)
    {
        return date("Y-m-d", $v);
    }

    public function getEndTimeAttr($v)
    {
        return date("Y-m-d", $v);
    }
}
