<?php

namespace app\merchant\model;

use think\Model;

class MerchantShop extends Model
{
    public function comments() {

        return $this->hasMany("Comment","shop_id", "id");
    }
}
