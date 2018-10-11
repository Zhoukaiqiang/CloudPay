<?php

namespace app\common\validate;

use think\Validate;

class MerchantValidate extends Validate
{
    protected $rule = [
        //添加员工
        ['name', 'require', '请输入名称'],
        ['phone', 'require|regex:/^1[3-9]\d{9}$/|unique:phone', '请填写手机号|手机号格式不正确|手机号已存在'],
        ['password', 'require|between:6,6', '请输入密码|密码固定6位'],
        ['role', 'require', '请选择角色'],
        ['shop_id', 'require', '请选择门店'],

        //充值送
        ['recharge_money', 'require|number', '请选择充值金额|充值金额必须为数字'],
        ['give_money', 'require|number', '请选择赠送金额|充值金额必须为数字'],
        ['active_time', 'require', '请设置活动时间'],
        ['start_time', 'require', '请选择开始时间'],
        ['end_time', 'require', '请选择结束时间'],

        //折扣
        ['apply_name', 'require', '适用用户 '],
        ['shop_id', 'require', '门店 '],
        ['discount', 'require', '设置折扣'],
        ['number', 'number', '数量必须为数字'],

        //会员专享
        ['consump_number', 'require', '消费次数 '],
        ['last_consump', 'require', '距上次消费 '],
        ['recharge_total', 'require', '会员充值总额'],
        ['consump_total', 'number', '消费总额'],
        ['coupons_title', 'number', '优惠券标题'],
        ['coupons_money', 'number', '优惠券金额'],
        ['order_money', 'number', '订单金额'],
        ['number', 'number', '数量必须为数字'],
        ['number', 'number', '数量必须为数字'],

        //分享红包
        ['money', 'require', '红包金额 '],
        ['lowest_consump', 'require', '最低消费  '],
    ];

    //命名规则 控制器_函数名称
    protected $scene = [
        //新增商户
        'add_user' => ['name', 'phone', 'password', 'role', 'shop_id'],
        //充值送
        'recharge'=>[
            'recharge_money', 'give_money', 'active_time'
        ],
        //选择时间充值送
        'new_recharge'=>[
            'recharge_money', 'give_money', 'active_time','start_time','end_time'
        ],
    ];

}