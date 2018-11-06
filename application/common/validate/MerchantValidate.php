<?php

namespace app\common\validate;

use think\Validate;

class MerchantValidate extends Validate
{
    protected $rule = [
        //添加员工
        ['name', 'require', '请输入名称'],
        ['phone', 'require|regex:/^1[3-9]\d{9}$/', '请填写手机号|手机号格式不正确'],
        ['password', 'require|length:6', '请输入密码|密码固定6位'],
        ['role', 'require', '请选择角色'],
        ['shop_id', 'require', '请选择门店'],

        //充值送
        ['recharge_money', 'require', '请设置充值金额'],
        ['give_money', 'require', '请设置赠送金额'],
        ['active_time', 'require', '请设置活动时间'],
        ['start_time', 'require', '请选择开始时间'],
        ['end_time', 'require', '请选择结束时间'],

        //折扣
        ['apply_name', 'require', '请选择适用用户 '],
        ['shop_id', 'require', '请选择门店 '],
        ['discount', 'require|number', '请设置折扣|折扣必须为数字'],
        ['number', 'number', '数量必须为数字'],

        //会员专享
        ['consump_number', 'number', '消费次数必须为数字 '],
        ['last_consump', 'number', '距上次消费必须为数字 '],
        ['recharge_total', 'number', '会员充值总额必须为数字'],
        ['consump_total', 'number', '消费总额必须为数字'],
        ['coupons_title', 'require', '请输入优惠券标题'],
        ['coupons_money', 'require|number', '请输入优惠券金额|优惠券金额必须为数字'],
        ['order_money', 'require|number', '请输入订单金额|订单金额必须为数字'],

        //分享红包
        ['money', 'require|number', '请输入红包金额|红包金额必须为数字 '],
        ['lowest_consump', 'number', '最低消费必须为数字  '],

        //设置会员卡
        ['member_color', 'require', '请选择会员卡颜色 '],
        ['member_content', 'require', '请输入宣传标语'],
        ['member_cart_name', 'require', '请输入会员卡名称'],

        //会员充值
        ['amount', 'require|number', '请输入支付金额|支付金额必须是数字 '],

        //绑定银行卡
        ["account_name", "require", "开户者名称必填"],
        ["account_no", "require", "银行卡号必填"],
        ["id_card", "require", "身份证号必填"],
        ["open_bank", "require", "银行支行必填"],
        ["open_branch", "require", "联行行号必填"],

        //会员注册
        ["member_name", "require", "请输入昵称"],
        ['member_phone', 'require|regex:/^1[3-9]\d{9}$/', '请填写手机号|手机号格式不正确'],
        ["member_birthday", "require", "请输入生日"],

        //新增门店  对私
        ["mailbox", "require|email", "邮箱必填|邮箱格式不正确"],
//        ["alipay_flg", "require", "请至少在扫码产品或银行卡产品选择一项"],
//        ["yhkpay_flg", "require", "请至少在扫码产品或银行卡产品选择一项"],
        ["trm_rec", "require|between:0,10", "请选择终端数量"],
        ["stoe_adds", "require|chsAlphaNum", "门店地址必填"],
        ["stoe_area_cod", "require|length:6", "地区码必填|请填写6位数地区码"],
        ["mcc_cd", "require|max:6", "MCC码必填"],
        ["stoe_cnt_tel", "require|length:11", "联系人手机号必填"],
        ["stoe_cnt_nm", "require|chsAlpha", "联系人名称必填"],
        ["stoe_nm", "require|chsAlphaNum", "签购单名称必填"],
        ["wc_lbnk_no", "require|length:12|number", "联行行号必填"],
        ["crp_exp_dt_tmp", "require|date", "结算人身份证有限期必填"],
        ["icrp_id_no", "require|length:18", "身份证号必填"],
        ["bnk_acnm", "require|chsAlpNum|max:45", "户名必填"],
        ["stoe_oac", "require|number|between:1,23", "结算账户必填"],
        ["stl_sign", "require|number|length:1", "结算标志必填"],
        ["stl_type", "require|number|length:1", "结算类型必填"],

        //台牌码支付
        ["mid", "require", "商户ID必填"],

    ];

    //命名规则 控制器_函数名称
    protected $scene = [
        //新增用户
        'bind_card' => ["account_name", "account_no", "id_card", "open_bank","open_branch", "phone"],
        'add_user' => ['name', 'phone', 'password', 'role', 'shop_id'],
        'edit_user' => ['name', 'phone', 'role', 'shop_id'],
        //充值送
        'recharge'=>[
            'recharge_money', 'give_money', 'active_time,shop_id'
        ],
        //选择时间充值送
        'new_recharge'=>[
            'recharge_money', 'give_money', 'active_time','start_time','end_time,shop_id'
        ],
        //折扣
        'discount'=>[
            'apply_name', 'shop_id', 'discount','number',
        ],
        'new_discount'=>[
            'apply_name', 'shop_id', 'discount','number','start_time','end_time'
        ],

        //会员专享
        'exclusive'=>[
            'consump_number', 'last_consump', 'recharge_total','consump_total','coupons_title','coupons_money','order_money','start_time','end_time'
        ],
        //分享红包
        'share'=>[
            'money', 'lowest_consump', 'start_time','end_time','shop_id'
        ],
        //会员卡
        'card'=>[
            'member_color','member_content','member_cart_name'
        ],

        //会员充值
        'member_recharge'=>[
            'amount'
        ],

        //会员注册
        'member_register'=>[
            'member_name','member_phone','member_birthday'
        ],

        //增加门店  对私
        //结算类型  1--T+1   2-- D+1  对公不能选择D+1
        //服务费率  结算类型 D+1 必输
        //结算人身份证有限期 9999-12-31 永久 结算标志为 1--对私必输  [1999-12-31]
        "add_store" => ["stl_sign", "stl_typ", "stl_oac", "bnk_acnm", "icrp_id_no","crp_exp_dt_tmp", "wc_lbnk_no"
        ,"stoe_nm", "stoe_cn_nm", "stoe_cnt_tel", "mcc_cd", "stoe_area_cod", "stoe_adds", "trm_rec",
            "mailbox", "alipay_flg", "yhkpay_flg"
        ],

        //台牌码 --支付
        "tag_pay" => ["amount", "mid"],
    ];

}