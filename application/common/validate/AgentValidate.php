<?php

namespace app\common\validate;

use think\Validate;

class AgentValidate extends Validate
{
    protected $rule = [
        //商户进件
        ['merchants_type', 'require', '商户类型必填'],
        ['name', 'require', '商户名必填'],
        ['address', 'require', '请填写地址'],
        ['detail_address', 'require', '请填写详细地址'],
        ['email', 'email', '邮箱格式不正确'],
        ['partner_id', 'require', '请选择合伙人'],
        ['category', 'require', '请选择经营类目'],
        ['merchant_rate', 'require|number', '请填写支付宝/微信费率|支付宝/微信费率必须为数字'],
        ['account_type', 'require', '请选择账户类型'],
        ['account_name', 'require', '请填写账户名'],
        ['account_no', 'require', '请填写账户号'],
        ['open_bank', 'require', '请填写开户银行'],
        ['open_branch', 'require|min:10', '请填写开户支行|输入字数不低于10个字'],
        ['id_card', 'require|regex:/(^\d(15)$)|((^\d{18}$))|(^\d{17}(\d|X|x)$)/', '请填写法人身份证号|身份证格式不正确'],
        ['law_name', 'require', '请填写法人姓名'],
        ['id_card_time', 'require', '请填写法人身份证到期日'],
        ['contact', 'require', '请填写商户联系人'],
        ['business_license', 'require', '请填写营业执照号'],
        ['license_time', 'require', '请填写营业执照有效期'],
        ['law_name', 'require', '请填写法人姓名'],
        ['phone', 'require|regex:/^1[34578]\d{9}$/|unique:phone', '请填写手机号|手机号格式不正确|手机号已存在'],

        //新增子代
        ['agent_name', 'require', '请输入代理商名称'],
        ['contact_person', 'require', '请输入联系人'],
        ['detailed_address', 'require', '请填写详细地址'],
        ['username', 'require', '请填写登录账号'],
        ['password', 'require', '请填写登录密码'],
        ['open_bank', 'require', '请填写开户行名称'],
        ['open_bank_branche', 'require', '请填写开户行网点'],
        ['home', 'require', '请填写开户行所在地'],
        ['account', 'require', '请填写账户号'],
        ['account_name', 'require', '请填写账户名'],
        ['account_no', 'require', '请填写账户号'],
        ['agent_level', 'require', '请选择代理等级'],
        ['agent_area', 'require', '请输入代理范围'],
        ['agent_money', 'require', '请输入代理费用'],
        ['contract_time', 'require', '请选择合同期限'],
        ['agent_rate', 'require', '请填写费率'],
        ['contract_picture', 'require', '请上传合同图片'],

        //新增合伙人
        ['partner_name', 'require', '请输入合伙人姓名'],
        ['commission', 'require', '请选择佣金计算'],
        ['partner_phone', 'require', '请输入合伙人电话'],
        ['password', 'email', '请填写登录密码'],
        ['model', 'require', '请选择分佣模式'],
    ];

    //命名规则 控制器_函数名称
    protected $scene = [
        //新增商户
        'add_middle' => ['merchants_type', 'name', 'address', 'detail_address', 'email', 'partner_id', 'category_id', 'merchant_rate', 'account_type', 'account_name', 'account_no', 'open_bank', 'id_card', 'law_name', 'id_card_time', 'contact', 'business_license', 'license_time', 'law_name', 'phone'],
        //子代详情
        'agent_detail'=>[
            'agent_name', 'contact_person', 'detailed_address', 'username', 'password', 'open_bank', 'open_bank_branche', 'home', 'account', 'account_name', 'account_no', 'agent_level', 'agent_area', 'agent_money', 'contract_time', 'agent_rate', 'contract_picture'
        ],
        //新增合伙人
    ];

}