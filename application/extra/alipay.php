<?php
/**
 * 支付宝支付
 */

return [
        //应用ID,您的APPID。
        'app_id' => "2016091600526284",

        //商户私钥, 请把生成的私钥文件中字符串拷贝在此
        'merchant_private_key' => "MIIEpAIBAAKCAQEA9obcOPzWGU7KQcURqamN15ELCTds5sEvfUm3DsqkBt0vwTKAgUUKHp6dGorCYnq9mCaKCVuh/e5pTfD9zNe3HjvcduqmtTTTucyt40n9EW9v082Z8cB7pUZwZx6KoVwsqNwcIKa12Loqv82Kv7OuifQYTK3Xp2YfdDSVlqBhxQ8463UXoRacvQHxVjQmssNEgbufUbiKU2LTAMt4S7keSwueZ5K/Q433lXDCGlDDxXHZwdym3h7K4+iqWzTSQ7kegE426shbDQ8WRWOlm7BIaZRwvN/A+85Y95XSpG7zQG2AtUNK6kRjeZgtlO9jjt4kXcYnwQxjs8OWeQjsUqixNwIDAQABAoIBAGPJ8xqePYvA+N3mh1/F4kR/0ZfJd6twR2jPjenO3NZqcgoiByJMb+w1CCLWSBjU1ingb7+Z99hxyO5jvlhMeTMjx/naweBXodxznW+DY4zLLtjtQIM7BtG/0X/sPPFT/j/b2QbedH1l3igFa0Rt1xjAVZW0SLbZ/6FqJ6LxCKJa26urery8tXMSiJfIKeNN2qwHE/XYiHGtwmkIwt4C8VFFb7rYC5Ijdf7yuJqzqtknULOES5PMWfwMhqxE30XokgoFl3oqcBoLUAzom2In22BZGH9hkiNYZxnqYudwFi9WEew8yoduP8uzRGZV8q+5f8IdKBbSnsPoRdsSqvprSgECgYEA/cnLS9qsb0YhcdjCqAttywjcReHY7UPsEj+rWjtcJtHhtrN8eXuMc01a7OMRAgk7QPGuxbXFlvB1wZxSoHRuLFw3C0Z/dq2CwdgcFwWwO+C6659+nBQDvwEiDo3JAMihw9bPVziA1i6+Icyu4yG6tOIoEqzDSLugnvF+yHfKr68CgYEA+KzdnRDrZEeNeJjD7gWZbmoIa6+s/GICcrciyCaKhaNAISN9qR5TlKACxLg2IVdeB2phJmgKUw8ttdy58D0m5K7TN/8OHrG8GOcFFW4iCppBCflOcl5FZ8JnccYs6w/rOUug0WBPD/nCuu/VadQpdlGmUla62KreAk4sg3VdMPkCgYAuvYmJkN6NJ9dlBkzjcidoa1tWK4AuQoIp4jwGxEP2ilUNtwTHwu9dFPQYCMHLJDQbg+dyVkXrxKGLZOT61DauSNWCaBt1mgMo/EpAGzYX8Q2784X37N+7v9Or5oUMdecFEHzjTW69A6LUysOy5TVjtvs2ZUcaECRG4ac9+IIF1QKBgQCaLsQxMF5ijKLAlSdWGw6okQGrkv7UdQhDjBz6sDrO5QtMAK9W/kCgNB3DTtvxDDR2sJSPtY5BNXYH/lUjCSdmPqcjXvaoPVb8sbBLOz/MBxwwTO1AqAascLKmrlMHY5VarvOFHgunQhpkwXM27J7Qh4tyHeg3kqmYEFQ1Jb2kMQKBgQCQP+4mIZ4KB6jFJ8AlPkVKskrgU8fR8TuptwbYFWB+laxRaEXxW4y9TmyCAQqCIJVH5ex3uTOcD7WheYxUtmbD4+UP5vxOPJxhr8bCww6fqRSDPs5sXcy1xyEbj3SNiltbtbs6h1jyGBSSazAMSxG/cwpCOM3qJA2PkcKAkuCHmg==",

        //异步通知地址
        'notify_url' => "http://local.cloud.com/admin/order/notify",

        //同步跳转
        'return_url' => "http://local.cloud.com/admin/order/callback",

        //编码格式
        'charset' => "UTF-8",

        //签名方式
        'sign_type'=>"RSA2",

        //支付宝网关
        'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",

        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9obcOPzWGU7KQcURqamN15ELCTds5sEvfUm3DsqkBt0vwTKAgUUKHp6dGorCYnq9mCaKCVuh/e5pTfD9zNe3HjvcduqmtTTTucyt40n9EW9v082Z8cB7pUZwZx6KoVwsqNwcIKa12Loqv82Kv7OuifQYTK3Xp2YfdDSVlqBhxQ8463UXoRacvQHxVjQmssNEgbufUbiKU2LTAMt4S7keSwueZ5K/Q433lXDCGlDDxXHZwdym3h7K4+iqWzTSQ7kegE426shbDQ8WRWOlm7BIaZRwvN/A+85Y95XSpG7zQG2AtUNK6kRjeZgtlO9jjt4kXcYnwQxjs8OWeQjsUqixNwIDAQAB",
];
