<?php

namespace app\merchant\model;

use think\Model;
use traits\model\SoftDelete;

class MerchantShop extends Model
{
    /** 模型使用示范------------------- */

    /** 设置类型转换 */
//    protected $type = [
//        "status" => "integer",
//        "name" => "string",
//        "merchant" => "array",
//
//    ];
    /** 自动写入 */
    //protected $auto = [];

    /** 设置只读字段，一旦写入不可以更改 */
//    protected $readonly = ['name','email'];


//    use SoftDelete;
//    protected $deleteTime = "delete_time";
    /** usage  软删除 User::destroy(1), User::delete(1) 真实删除 User::destroy(1,true),  User::delete(1, true)*/
    /**
     * 表关联  1-N
     * @return \think\model\relation\HasMany
     */
    public function comments()
    {

        return $this->hasMany("Comment", "shop_id", "id");
    }


}
