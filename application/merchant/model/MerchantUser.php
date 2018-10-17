<?php

namespace app\merchant\model;

use think\Model;
use traits\model\SoftDelete;

class MerchantUser extends Model
{
    use SoftDelete;
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $deleteTime = 'delete_time';
//    public function getRoleAttr($val) {
//        switch ($val) {
//            case 1:
//                return '服务员';
//                break;
//            case 2:
//                return '店长';
//                break;
//            case 3:
//                return '收银员';
//                break;
//        }
//    }
}
