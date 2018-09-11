<?php
namespace app\admin\controller;


/**
 * Class Index
 * @package app\index\controller
 */
class Index
{

    public function index()
    {
        echo dirname(dirname ( __FILE__ ));
//        echo __FILE__;
    }

}