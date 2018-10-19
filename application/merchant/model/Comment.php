<?php

namespace app\merchant\model;

use think\Model;

class Comment extends Model
{

    protected $autoWriteTimestamp = true;
    protected $createTime = "ctime";
    protected $updateTime = false;
}
