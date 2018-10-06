<?php
namespace app\common\validate;

use think\Validate;

class AgentValidate extends Validate{
    protected $rule=[
        //商户进件
        ['merchants_type' ,'require','商户类型必填'],
        ['name'  ,'require','商户名必填'],
        ['address','require','请填写地址'],
        ['detail_address','require','请填写详细地址'],
        ['email'      ,'email','邮箱格式不正确'],
        ['partner_id'      ,'require','请选择合伙人'],
        ['category_id'      ,'require','请选择经营类目'],
        ['merchant_rate'     ,'require|number','请填写支付宝/微信费率|支付宝/微信费率必须为数字'],
        ['account_type','require','请选择账户类型'],
        ['account_name'          ,'require','请填写账户名'],
        ['account_no'       ,'require','请填写账户号'],
        ['open_bank'  ,'require','请填写开户银行'],
        ['id_card'    ,'require','请填写法人身份证号'],
        ['law_name'    ,'require','请填写法人姓名'],
        ['id_card_time'    ,'require','请填写法人身份证到期日'],
        ['contact'   ,'require','请填写商户联系人'],
        ['business_license' ,'require','请填写营业执照号'],
        ['license_time'   ,'require','请填写营业执照有效期'],
        ['law_name','require','请填写法人姓名'],
        ['phone' ,'require|regex:/^1[3-9]\d{9}$/|unique:phone','请填写手机号|手机号格式不正确|手机号已存在'],

    ];

    //命名规则 控制器_函数名称
    protected $scene=[
        //新增商户
        'add_middle'=>['merchants_type','name','address','detail_address','email','partner_id','category_id','merchant_rate','account_type','account_name','account_no','open_bank','id_card','law_name','id_card_time','contact','business_license','license_time','law_name','phone'],

    ];

}