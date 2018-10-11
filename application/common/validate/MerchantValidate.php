<?php

namespace app\common\validate;

use think\Validate;

class MerchantValidate extends Validate
{
    protected $rule = [
        //添加员工
        ['name', 'require', '请输入员工姓名'],
        ['phone', 'require|regex:/^1[3-9]\d{9}$/|unique:phone', '请填写手机号|手机号格式不正确|手机号已存在'],
        ['password', 'require|between:6,6', '请输入密码|密码固定6位'],
        ['role', 'require', '请选择角色'],
        ['shop_id', 'require', '请选择门店'],


        ['partner_id', 'require', '请选择合伙人'],
        ['category_id', 'require', '请选择经营类目'],
        ['merchant_rate', 'require|number', '请填写支付宝/微信费率|支付宝/微信费率必须为数字'],
        ['account_type', 'require', '请选择账户类型'],
        ['account_name', 'require', '请填写账户名'],
        ['account_no', 'require', '请填写账户号'],
        ['open_bank', 'require', '请填写开户银行'],
        ['id_card', 'require', '请填写法人身份证号'],
        ['law_name', 'require', '请填写法人姓名'],
        ['id_card_time', 'require', '请填写法人身份证到期日'],
        ['contact', 'require', '请填写商户联系人'],
        ['business_license', 'require', '请填写营业执照号'],
        ['license_time', 'require', '请填写营业执照有效期'],
        ['law_name', 'require', '请填写法人姓名'],


    ];

    //命名规则 控制器_函数名称
    protected $scene = [
        //新增商户
        'add_user' => ['name', 'phone', 'password', 'role', 'shop_id'],

    ];

}