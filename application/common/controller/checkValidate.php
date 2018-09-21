<?php
namespace app\common\validate;

use think\Validate;

class CheckValidate extends Validate{
    protected $rule=[
        ['agent_mode' ,'require','代理方式必填'],
        ['agent_name'  ,'require','代理商名称必填'],
        ['contact_person','require','请填写联系人'],
        ['detailed_address','require','请填写详细地址'],
        ['admin_id'      ,'require','请选择运营人员'],
        ['username'      ,'require','请填写登录账号'],
        ['password'      ,'require','请填写登录密码'],
        ['open_bank'     ,'require','请填写开户行名称'],
        ['open_bank_branche','require','请填写开户行网点'],
        ['home'          ,'require','请选择所在地'],
        ['account'       ,'require','请填写账户号'],
        ['account_name'  ,'require','请填写账户名'],
        ['agent_area'    ,'require','请选择代理范围'],
        ['agent_money'   ,'require|number','请输入代理费用|代理费用必须是数字'],
        ['contract_time' ,'require','请选择合同期限'],
        ['agent_rate'   ,'number|max:0.6','间联费率必须是数字|间联费率最高为0.6'],
        ['contract_picture','require','请上传合同图片'],
        ['agent_phone' ,'require|regex:/^1[3-9]\d{9}$/|unique:agent_phone','请填写手机号|手机号格式不正确|手机号已存在']
    ];

    protected $param=[
        'add'=>['agent_mode','agent_name','contact_person','detailed_address','admin_id','username','password','open_bank','open_bank_branche','home','account','account_name','agent_area','agent_money','contract_time','agent_rate','contract_picture','agent_phone'],

    ];
}