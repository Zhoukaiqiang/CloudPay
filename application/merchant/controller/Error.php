<?php
/**
 * Created by PhpStorm.
 * User: guoyang
 * Date: 2018/10/24
 * Time: 17:08
 */

namespace app\merchant\controller;


class Error
{
    public function index()
    {
        return_msg(400,'error','404,您所找的页面已经遨游太空了');
        $data = ['total_amount','amount','orderNo','result','logNo','tradeNo',
            'sysTime','message','returnCode','check_flag', 'msg_cd', 'msg_dat',
            'mercId', 'log_no', 'stoe_id', 'mobile', 'sign_stats', 'deliv_stats'];


    }
}