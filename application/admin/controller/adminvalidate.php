<?php
/**
 * Created by KaiQiang-use by PhpStorm.
 * User: fennu
 * Date: 2018/9/20
 * Time: 14:20
 */

namespace app\admin\validate;

use think\Validate;


class AdminValidate extends Validate
{

    protected  $rule = [
        'name' => 'required|max:25',
        'email' => 'email',
    ];
}