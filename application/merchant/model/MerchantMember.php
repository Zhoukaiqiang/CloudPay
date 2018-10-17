<?php

namespace app\merchant\model;

use think\Model;

class MerchantMember extends Model
{
    //
    protected $autoWriteTimestamp=true;
    protected $createTime = "register_time";
    protected $updateTime = false;

}
