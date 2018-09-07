#云商付api接口文档
-----------------
##1 用户找回密码

`post`  [http://www.domain.com/admin/user/login](http://www.domain.com/admin/user/login)

<table>
<tr>
<th>参数</th><th>类型</th><th>必选/可选</th><th>默认</th><th>描述</th>
</tr>
<tr>
<td>time</td>
<td>int</td>
<td>必需</td>
<td>无</td>
<td>**时间戳**用于判断请求是否超时</td>
</tr>
<tr>
<td>token</td>
<td>string</td>
<td>必需</td>
<td>无</td>
<td>**确定来访者身份**</td>
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

```json 
{
	"code": 200,
	"msg" : "密码修改成功！",
	"data" : []
}
```
