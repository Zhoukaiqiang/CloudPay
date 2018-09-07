<?php

namespace app\User\model;

use think\Model;

class User extends Model
{
    public function getUserModel() {
        return 'this is user model';
    }
}
