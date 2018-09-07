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