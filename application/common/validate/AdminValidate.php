<?php
namespace app\common\validate;

use think\Validate;

class AdminValidate extends Validate{
    protected $rule=[
        //代理商
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
        ['agent_phone' ,'require|regex:/^1[3-9]\d{9}$/|unique:agent_phone','请填写手机号|手机号格式不正确|手机号已存在'],

        //商户
        ['merchants_type','require','请选择商户类型'],
        ['merchant_name','require','请输入商户全称'],
        ['address','require','请选择地址信息'],
        ['detail_address','require','请输入详细地址'],
        ['contact','require','请填写联系人'],
        ['phone','require|regex:/^1[3-9]\d{9}$/|unique:phone','请填写联系电话|联系电话格式不正确|联系电话已存在'],
        ['category','require','请选择经营类目'],
        ['account_type','require','请选择账户类型'],
        ['account_name','require','请输入账户名'],
        ['account_no','require','请输入账户号'],
        ['location_account','require','请输入开户所在地'],
        ['merchant_rate','require|number|max:0.6','请输入间联费率|间联费率必须是数字|间联费率最高为0.6'],
        ['agent_id','require','请选择所属代理商'],
        ['attachment','require','请上传证件材料'],

        //人员管理
        ['name','require','请输入用户名'],
        ['role_id','require','请选择所属角色'],
        ['status','require','请选择状态'],
    ];

    //命名规则 控制器_函数名称
    protected $scene=[
        //新增代理商
        'add'=>['agent_mode','agent_name','contact_person','detailed_address','admin_id','username','open_bank','open_bank_branche','home','account','account_name','agent_area','agent_money','contract_time','agent_rate','contract_picture','agent_phone'],
        //代理商修改
        'detail'=>['agent_mode','agent_name','contact_person','detailed_address','admin_id','username','password','open_bank','open_bank_branche','home','account','account_name','agent_area','agent_money','contract_time','agent_rate','agent_phone'],
        //商户间联修改
        'middle_submit'=>['merchants_type','name','address','detail_address','contact','username','password','phone','category','account_type','account_name','account_no','location_account','open_bank','merchant_rate','agent_id','attachment'],
        //添加人员
        'user'=>[
            'name','role_id','status','phone','password'
        ],
    ];
}