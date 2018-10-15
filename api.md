---

---

#云商付api接口文档
-----------------
##1 用户修改密码

`post`  [http://www.domain.com/admin/user/changePwd](http://www.domain.com/admin/user/changePwd)

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>time</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>时间戳用于判断请求是否超时</td>
</tr>
<tr>
<td>token</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>确定来访者身份</td>
</tr>
<tr>
<td>user_name</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户名(手机号)</td>
</tr>
<tr>
<td>user_ini_pwd</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户的老密码</td>
</tr>
<tr>
<td>user_pwd</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户的新密码</td>
</tr>
</table>


```
{
	"code": 200,
	"msg" : "密码修改成功！",
	"data" : []
}
```


##2 用户找回密码

`post`  [http://www.domain.com/admin/user/findPwd](http://www.domain.com/admin/user/findPwd)

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>time</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>时间戳用于判断请求是否超时</td>
</tr>
<tr>
<td>token</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>确定来访者身份</td>
</tr>
<tr>
<td>user_name</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户名(手机号)</td>
</tr>
<tr>
<td>code</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>验证码</td>
</tr>
<tr>
<td>password</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户的新密码</td>
</tr>
</table>

```
{
	"code": 200,
	"msg" : "密码修改成功！",
	"data" : []
}
```

##3 用户绑定手机号

`post`  [http://www.domain.com/admin/user/bindPhone](http://www.domain.com/admin/user/bindPhone)

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>time</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>时间戳用于判断请求是否超时</td>
</tr>
<tr>
<td>token</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>确定来访者身份</td>
</tr>
<tr>
<td>user_id</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>用户id</td>
</tr>
<tr>
<td>code</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>验证码</td>
</tr>
<tr>
<td>phone</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户的手机号</td>
</tr>
</table>

```
{
	"code": 200,
	"msg" : "手机号绑定成功！",
	"data" : []
}
```


##4 用户绑定邮箱

`post`  [http://www.domain.com/admin/user/bindEmail](http://www.domain.com/admin/user/bindEmail)

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>time</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>时间戳用于判断请求是否超时</td>
</tr>
<tr>
<td>token</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>确定来访者身份</td>
</tr>
<tr>
<td>user_id</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>用户id</td>
</tr>
<tr>
<td>code</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>验证码</td>
</tr>
<tr>
<td>email</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>用户的邮箱</td>
</tr>
</table>

```
{
	"code": 200,
	"msg" : "邮箱绑定成功！",
	"data" : []
}
```
## 5 首页--交易数据--搜索查询

##### `post`http://www.domain.com/agent/index/search_deal

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>商户|联系人|联系方式</td>
</tr>
<tr>
<td>partner_id</td>
<td>int</td>
<td>必需</td>
<td>0(全部)</td>
<td>合伙人id</td>
</tr>

<tr>
<td>deal</td>
<td>int</td>
<td>必需</td>
<td>1(有交易商户)</td>
<td>1(有交易商户)，2（无交易商户）</td>
</tr>

<tr>
<td>pay_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>yesterday</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>



	{
	"code": 200,
	"msg" : "success",
	"data" : []
	}


## 6 首页-昨日活跃商户

`post`http://www.domain.com/agent/index/yesterday_active

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>pay_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>yesterday</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}

```





## 7 首页-七日无交易-筛选查询

`post`http://www.domain.com/agent/index/merchant_trade

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>商户|联系人|联系方式</td>
</tr>
<tr>
<td>partner_id</td>
<td>int</td>
<td>必需</td>
<td>0(全部)</td>
<td>合伙人id</td>
</tr>

<tr>
<td>deal</td>
<td>int</td>
<td>必需</td>
<td>1(有交易商户)</td>
<td>1(有交易商户)，2（无交易商户）</td>
</tr>

<tr>
<td>pay_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>yesterday</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 8 商户列表-筛选查询

`post`http://www.domain.com/agent/index/merchant_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>商户|联系人|联系方式</td>
</tr>
<tr>
<td>partner_id</td>
<td>int</td>
<td>必需</td>
<td>0(全部)</td>
<td>合伙人id</td>
</tr>

<tr>
<td>status</td>
<td>int</td>
<td>必需</td>
<td>0</td>
<td>0(开启)，1（关闭）</td>
</tr>

<tr>
<td>pay_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>yesterday</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 9 商户交易--筛选搜索

`post`http://www.domain.com/agent/index/merchant_deal

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>商户|联系人|联系方式</td>
</tr>
<tr>
<td>partner_id</td>
<td>int</td>
<td>必需</td>
<td>0(全部)</td>
<td>合伙人id</td>
</tr>

<tr>
<td>deal</td>
<td>int</td>
<td>必需</td>
<td>1(有交易商户)</td>
<td>1(有交易商户)，2（无交易商户）</td>
</tr>

<tr>
<td>pay_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>yesterday</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 10  服务商列表-筛选搜索

`post`http://www.domain.com/agent/index/facilitator_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>服务商|联系人|联系方式</td>
</tr>
<tr>
<td>status</td>
<td>int</td>
<td>必需</td>
<td>2（全部）</td>
<td>服务商状态 0（启用）1（停用）</td>
</tr>

<tr>
<td>agent_area</td>
<td>string</td>
<td>可选</td>
<td>null</td>
<td>所选区域</td>
</tr>

<tr>
<td>create_time</td>
<td>date</td>
<td>必需</td>
<td>昨天</td>
<td>起始时间</td>
</tr>

<tr>
<td>end_time</td>
<td>date</td>
<td>必需</td>
<td>今天</td>
<td>终止时间</td>
</tr>
</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 11服务商商户-筛选搜索

`post`http://www.domain.com/agent/index/facilitator_tenant

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>keyword</td>
<td>string|int</td>
<td>可选</td>
<td>无</td>
<td>商户|联系人|联系方式</td>
</tr>
<tr>
<td>status</td>
<td>int</td>
<td>必需</td>
<td>2（全部）</td>
<td>商户状态 0（启用）1（停用）</td>
</tr>

<tr>
<td>agent_id</td>
<td>int</td>
<td>必需</td>
<td>默认服务商</td>
<td>服务商id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 12运营后台-取出一则广告图片

`post`http://www.domain.com/admin/advertise/index
###无参数

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 13运营后台-取出一则广告图片

`post`http://www.domain.com/admin/advertise/upload


<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>image</td>
<td>file</td>
<td>必选</td>
<td>无</td>
<td>要上传的图片文件name="image"</td>
</tr>


</table>
```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 14运营后台-删除广告

`post`http://www.domain.com/admin/advertise/upload


<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>传广告id</td>
</tr>


</table>
```
{
"code": 200,
"msg" : "success",
"data" : []
}
```


## 15运营后台-资金结算-未结算列表

`post`http://www.domain.com/admin/capital/index


<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>当前登录者id</td>
</tr>


</table>
```
{
"code": 200,
"msg" : "success",
"data" : []
}
```
## 16 卡券核销 SN码搜索查询

`post`http://www.domain.com/merchant/Coupon/cancel

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>sncode</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>sn码</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
name:优惠券活动名称 
money:优惠价格
create_time
end_time
cancel_time 核销日期
status 状态 0使用中 1已核销
max_money 满足多少钱可以使用
user_id  操作人员
}
```



## 17 核销优惠卷

`post`http://www.domain.com/merchant/Coupon/cancel

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td> 优惠券id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
name:优惠券活动名称 
money:优惠价格
create_time
end_time
cancel_time 核销日期
status 状态 0使用中 1已核销
max_money 满足多少钱可以使用
user_id  操作人员
}
```



## 18 核销纪录

`post

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>

</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
name:优惠券活动名称 
money:优惠价格
create_time
end_time
cancel_time 核销日期
status 状态 0使用中 1已核销
max_money 满足多少钱可以使用
user_id  操作人员
}
```

## 19 提现

`get`http://www.domain.com/merchant/Deposit/withdraw_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
money:

}
```

## 20 确认提现

`post`http://www.domain.com/merchant/Deposit/withdraw_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr><tr>
<td>money</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td> 提现金额</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
bank开户行 
bank_card结算卡号 
money提现金额 
poundage手续费

}
```



## 21 提现记录

`post`http://www.domain.com/merchant/Deposit/Withdrawal_record

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr><tr>

</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
bank开户行 
bank_card结算卡号 
money提现金额 
poundage手续费
way 提现方式 1普通提现 2自动提现 3快速提现
create_time
end_time 提现成功时间
serial_number 流水号
repMsg 提现失败原因
status 提现状态 1 提现成功 2提现中 3提现失败
}
```

## 22 提现记录筛选查询

`post`http://www.domain.com/merchant/Deposit/Withdrawal_record_query

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>way</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td> 提现方式 1普通提现 2自动提现 3快速提现 0全部方式</td>
</tr>

<tr>
<td>status</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td> 提现状态 1 提现成功 2提现中 3提现失败 0全部方式</td>
</tr>

<tr>
<td>create_time</td>
<td>int</td>
<td>可选</td>
<td>今天</td>
<td> 开始时间</td>
</tr>

<tr>
<td>end_time</td>
<td>int</td>
<td>可选</td>
<td>今天</td>
<td> 结束时间</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
bank开户行 
bank_card结算卡号 
money提现金额 
poundage手续费
way 提现方式 1普通提现 2自动提现 3快速提现
create_time
end_time 提现成功时间
serial_number 流水号
repMsg 提现失败原因
status 提现状态 1 提现成功 2提现中 3提现失败
}
```

## 23 对账

`post`http://www.domain.com/merchant/Reconciliation/index

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
money{
proceeds 实收总金额
discounts 优惠总金额
refund 退款总金额
ordercount 订单笔数
}
data{
pay_type 支付类型
received_money 实收金额
discount 优惠金额
order_money 订单金额
}
shop{
  shop_name 门店名称
  shopid
  name 员工名称
  userid
}
}
```

## 24 对账首页筛选

`post`http://www.domain.com/merchant/Reconciliation/index_query

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>user_id</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td> 员工id</td>
</tr>

<tr>
<td>create_time</td>
<td>int</td>
<td>可选</td>
<td>今天</td>
<td> 开始时间</td>
</tr>

<tr>
<td>end_time</td>
<td>int</td>
<td>可选</td>
<td>今天</td>
<td> 结束时间</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
money{
proceeds 实收总金额
discounts 优惠总金额
refund 退款总金额
ordercount 订单笔数
}
data{
pay_type 支付类型
received_money 实收金额
discount 优惠金额
order_money 订单金额
}

}
```

## 25 日周月账单首页

`post`http://www.domain.com/merchant/Reconciliation/oneday

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>pid</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td> 1日  2周  3月</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
money{
countmoney 实收总金额
count 订单笔数
}
arr{
days 日期
minutes 时间
received_money 订单金额

}

}
```

```

```

## 26 日周月账单筛选查询

`post`http://www.domain.com/merchant/Reconciliation/turuend_query

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>days</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td> 天数</td>
</tr>

<tr>
<td>up</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td> 1上一  2下一</td>
</tr>

<tr>
<td>create_time</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td> 起始时间</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
money{
countmoney 实收总金额
count 订单笔数
}
arr{
days 日期
minutes 时间
received_money 订单金额

}

```

## 27 服务设置

`post`http://www.domain.com/merchant/Waiter/waiter_set

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 27 店小二首页

`post`http://www.domain.com/merchant/Waiter/index

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 28 桌位设置

`get`http://www.domain.com/merchant/Waiter/table_set

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 生成二维码

`post`http://www.domain.com/merchant/Waiter/qrcode2

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 保存桌码

`post`http://www.domain.com/merchant/Waiter/save_code

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>image</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td》二维码图片</td>
</tr>

<tr>
<td>name</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>桌子名称</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 

## 下载桌码 二维码

`post`http://www.domain.com/merchant/Waiter/proceeds

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>桌位id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 

## 删除桌码

`post`http://www.domain.com/merchant/Waiter/table_delete

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>桌位id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 





## 29 餐具费设置

`post`http://www.domain.com/merchant/Waiter/tableware_set

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>istableware</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>餐具是否收费 1收费 0不收费</td>
</tr>

<tr>
<td>tableware_money</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td>餐具收费金额</td>
</tr>





</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 30 菜品设置

`get`http://www.domain.com/merchant/Waiter/cuisine_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 31 菜品设置搜索

`post`http://www.domain.com/merchant/Waiter/cuisine_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 32 菜品下架

`post`http://www.domain.com/merchant/Waiter/cuisine_soldout

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 33 管理分类

`post`http://www.domain.com/merchant/Waiter/manage_type

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 34 添加管理分类

`post`http://www.domain.com/merchant/Waiter/add_type

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_norm</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品分类名称</td>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 34 编辑管理分类

`post`http://www.domain.com/merchant/Waiter/set_type

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_norm</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品分类名称</td>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 

## 34 删除管理分类

`post`http://www.domain.com/merchant/Waiter/delete_type

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>id</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品分类id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 



## 35 新增菜品首页面

`get`http://www.domain.com/merchant/Waiter/add_cuisine

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_norm</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品分类名称</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 36 新增菜品

`post`http://www.domain.com/merchant/Waiter/add_cuisine

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品名称</td>
</tr>

<tr>
<td>dish_img</td>
<td>file</td>
<td>必选</td>
<td>无</td>
<td>菜品图片</td>
</tr>

<tr>
<td>norm_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品分类id</td>
</tr>

<tr>
<td>dish_describe</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品描述</td>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>dish_label</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品标签id</td>
</tr>

<tr>
<td>money</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品价格</td>
</tr>

<tr>
<td>dish_attr</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品属性id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 37 编辑菜品

`post`http://www.domain.com/merchant/Waiter/edit_cuisine

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_name</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>菜品名称</td>
</tr>

<tr>
<td>dish_img</td>
<td>file</td>
<td>可选</td>
<td>无</td>
<td>菜品图片</td>
</tr>

<tr>
<td>norm_id</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td>菜品分类id</td>
</tr>

<tr>
<td>dish_describe</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>菜品描述</td>
</tr>

<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品id</td>
</tr>

<tr>
<td>dish_label</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>菜品标签id</td>
</tr>

<tr>
<td>money</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td>菜品价格</td>
</tr>

<tr>
<td>dish_attr</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>菜品属性id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 38 新增属性 首页面

`get`http://www.domain.com/merchant/Waiter/add_attribute

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 39 新增属性

`post`http://www.domain.com/merchant/Waiter/add_attribute

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>dish_norm</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品属性名称</td>
</tr>

<tr>
<td>parent_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品属性上级id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 40 删除属性

`post`http://www.domain.com/merchant/Waiter/add_attribute

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>norm_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品属性id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 41 服务数据

`post`http://www.domain.com/merchant/Waiter/serve_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 42 服务员数据详情

`post`http://www.domain.com/merchant/Waiter/serve_query

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>user_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>员工id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 43 订单数据

`post`http://www.domain.com/merchant/Waiter/Today_order

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 44 订单数据查询

`post`http://www.domain.com/merchant/Waiter/indent_query

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>status</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td> 支付状态 5全部状态  0未支付 1已支付 2已关闭 3会员充值</td>
</tr>

<tr>
<td>create_time</td>
<td>可选</td>
<td>必选</td>
<td>无</td>
<td>开始时间</td>
</tr>

<tr>
<td>end_time</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>结束时间</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 45 订单详情

`post`http://www.domain.com/merchant/Waiter/today_orderquery

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>订单id</td>
</tr>

<tr>
<td>status</td>
<td>int</td>
<td>可选</td>
<td>无</td>
<td> 支付状态 5全部状态  0未支付 1已支付 2已关闭 3会员充值</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 46 服务员端--首页

`post`http://www.domain.com/merchant/Waiter/call_service

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>





</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 47 服务员端--接单

`post`http://www.domain.com/merchant/Waiter/receiving

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>order_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>订单id</td>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 48 服务员端--桌位展示 选择桌位

`post`http://www.domain.com/merchant/Waiter/choose_table

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 49 服务员端--点菜页面

`get`http://www.domain.com/merchant/Waiter/cuisine_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 50 服务员端--点菜页面搜索

`post`http://www.domain.com/merchant/Waiter/cuisine_list

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>dish_name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品名称</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 51 服务员端--去下单

`post`http://www.domain.com/merchant/Waiter/place_order

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>table_name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>桌号名称</td>
</tr>

<tr>
<td>money</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品价格</td>
</tr>

<tr>
<td>table_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>桌号id</td>
</tr>

<tr>
<td>deal</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>分量</td>
</tr>

<tr>
<td>order_remark</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>订单备注</td>
</tr>

<tr>
<td>name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品名称</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 52 服务员端--确定订单

`post`http://www.domain.com/merchant/Waiter/confirm_order

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>

<tr>
<td>shop_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>门店id</td>
</tr>

<tr>
<td>table_name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>桌号名称</td>
</tr>

<tr>
<td>money</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>菜品价格</td>
</tr>

<tr>
<td>table_id</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>桌号id</td>
</tr>

<tr>
<td>deal</td>
<td>int</td>
<td>必选</td>
<td>无</td>
<td>分量</td>
</tr>

<tr>
<td>order_remark</td>
<td>string</td>
<td>可选</td>
<td>无</td>
<td>订单备注</td>
</tr>

<tr>
<td>name</td>
<td>string</td>
<td>必选</td>
<td>无</td>
<td>菜品名称</td>
</tr>

</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

## 52 APP首页面

`post`http://www.domain.com/merchant/Index/index

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>



</table>

```
{
"code": 200,
"msg" : "success",
"data" : []
}
```

