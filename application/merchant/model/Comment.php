<?php

namespace app\merchant\model;

use think\Model;

class Comment extends Model
{

    protected $auto = [
        "ctime",
    ];

    public function setCtimeAttr($val) {
        return time();
    }
}
