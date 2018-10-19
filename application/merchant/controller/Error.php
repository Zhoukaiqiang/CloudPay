<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/17
 * Time: 15:44
 */

namespace app\merchant\controller;


use think\Request;

class Error
{
    public function index(Request $request)
    {
        $name=$request->controller();
        return $this->city($name);
    }
    public function city($name)
    {
        return $name;
    }

}