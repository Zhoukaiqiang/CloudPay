<?php

namespace app\admin\model;

use think\Model;

class Index extends Model
{
    public function getReviewStatusAttr($value)
    {
        //  直联和间联 0待审核 1开通中 2通过 3未通过
        $attr_input_type = ['待审核 ','开通中','通过','未通过'];
        return $attr_input_type[$value];
    }
}
