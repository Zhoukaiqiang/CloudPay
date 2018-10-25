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
            'name','consump_number', 'last_consump', 'recharge_total','consump_total','coupons_title','coupons_money','order_money','start_time','end_time'
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
    ];

}