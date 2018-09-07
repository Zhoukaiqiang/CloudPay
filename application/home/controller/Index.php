<?php
namespace app\home\controller;

class Index
{
    public function index()
    {
        echo APP_PATH;
        echo __DIR__;
    }
}
