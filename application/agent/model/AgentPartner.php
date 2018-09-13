<?php

namespace app\agent\model;

use think\Model;
use traits\model\SoftDelete;

class AgentPartner extends Model
{
    use SoftDelete;
    protected $deleteTime="delete_time";
}
