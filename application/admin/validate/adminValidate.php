<?php
namespace app\admin\validate;

use think\Validate;

class adminValidate extends Validate{
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
        ['phone' ,'require|regex:/^1[3-9]\d{9}$/|unique:agent_phone','请填写手机号|手机号格式不正确|手机号已存在']
    ];
    protected $msg=[
        'agent_mode.require'             =>'代理方式必填',
        'agent_name.require'             =>'代理商名称必填',
        'contact_person.require'        =>'请填写联系人',
        'detailed_address.require'      =>'请填写详细地址',
        'admin_id.require'              =>'请选择运营人员',
        'username.require'              =>'请填写登录账号',
        'password.require'              =>'请填写登录密码',
        'open_bank.require'              =>'请填写开户行名称',
        'open_bank_branche.require'     =>'请填写开户行网点',
        'home.require'                    =>'请选择所在地',
        'account.require'                =>'请填写账户号',
        'account_name.require'           =>'请填写账户名',
        'agent_area.require'             =>'请选择代理范围',
        'agent_money.require'            =>'请输入代理费用',
        'agent_money.number'            =>'代理费用必须是数字',
        'contract_time.require'        =>'请选择合同期限',
        'alipay_rate.number'            =>'支付宝间联费率必须是数字',
        'alipay_rate.max'               =>'支付宝间联费率最高是0.6',
        'wechat_rate.number'            =>'微信间联费率必须是数字',
        'wechat_rate.max'               =>'微信间联费率最高是0.6',
        'contract_picture.require'      =>'请上传合同图片',
        'phone.require'                 =>'请填写联系方式',
        'phone.regex.require'          =>'联系方式格式不正确',
        'phone.unique.require'         =>'手机号已存在'
    ];
}