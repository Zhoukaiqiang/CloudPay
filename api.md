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