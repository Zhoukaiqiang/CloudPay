<?php

namespace app\agent\model;

use think\Model;

class TotalAgent extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $createTime = 'create_time';

}
