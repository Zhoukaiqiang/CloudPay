<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class TotalAgent extends Model
{
    //
    use SoftDelete;
    protected $deleteTime="delete_time";

    public function getStatusAttr($value)
    {
        //账号状态 0开启 1关闭
        return $value ? '启用':'停用';
    }

    public function getAgentModelAttr($value)
    {
        //  当前通道 1直联 2间联 3直联和间联
        $attr_input_type = ['','直联','间联','直联 间联'];
        return $attr_input_type[$value];
    }

    public function getParentIdAttr($val) {
        if ($val == 0) {
            return "平台";
        }
        $agent = TotalAgent::get($val);
        return $agent->agent_name;
    }

    public function getAdminIdAttr($val) {

        return TotalAdmin::get($val)->field(["id", "name"])->find();
    }
}
