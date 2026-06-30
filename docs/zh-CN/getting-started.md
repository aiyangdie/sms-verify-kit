# 快速开始

> **零基础？请直接看 [新手 5 分钟上手](./beginner-5min.md)**，含复制粘贴命令和 FAQ 链接。

## 最快路径

```bash
git clone https://github.com/aiyangdie/sms-verify-kit.git
cd sms-verify-kit
bash scripts/setup.sh
php bin/sms-verify.php doctor
```

---

## 方式 A：直接用 SDK（推荐）

在服务端安装对应语言 SDK，填入你的阿里云 AccessKey 和 SignName。

### PHP

```bash
cd sdk/php && composer install
```

```php
<?php
require 'path/to/sdk/php/src/EnvLoader.php';
require 'path/to/sdk/php/src/AliyunPnvsClient.php';

// 新手推荐：自动读 .env
$client = SmsVerifyKit\AliyunPnvsClient::fromEnv('/path/to/sms-verify-kit');

// 1. 发送
$send = $client->send('13800138000', 'login');
if (!$send['ok']) {
    exit($send['message']);
}

// 2. 用户提交验证码后校验
$verify = $client->verify('13800138000', $_POST['code'] ?? '');
if ($verify['ok']) {
    // 验码成功 → 创建 session / JWT
}
```

### Node.js

```javascript
const { AliyunPnvsClient } = require('@aiyangdie/sms-verify-kit');

const client = new AliyunPnvsClient({
  accessKeyId: process.env.SMS_ACCESS_KEY_ID,
  accessKeySecret: process.env.SMS_ACCESS_KEY_SECRET,
  signName: '速通互联验证码',
});

const send = await client.send('13800138000', 'register');
const verify = await client.verify('13800138000', '1234');
```

### Python

```python
from sms_verify_kit.client import AliyunPnvsClient

client = AliyunPnvsClient(
    access_key_id="...",
    access_key_secret="...",
    sign_name="速通互联验证码",
)
client.send("13800138000", scene="bind_phone")
client.verify("13800138000", "1234")
```

### Go

```go
client := smsverifykit.NewClient(smsverifykit.Config{
    AccessKeyID:     "...",
    AccessKeySecret: "...",
    SignName:        "速通互联验证码",
})
client.Send("13800138000", "login")
client.Verify("13800138000", "1234")
```

---

## 方式 B：HTTP 网关（任意语言）

适合 PHP 以外栈、或不想集成 SDK 的团队。

```bash
bash scripts/setup.sh
# 或手动：cp gateway/config.example.php gateway/config.local.php
php -S 127.0.0.1:8080 -t gateway/public
# 浏览器打开 http://127.0.0.1:8080/docs
```

```bash
# 发送
curl -X POST https://your-domain/v1/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-gateway-key" \
  -d '{"phone":"13800138000","scene":"login"}'

# 校验
curl -X POST https://your-domain/v1/verify \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-gateway-key" \
  -d '{"phone":"13800138000","code":"1234"}'
```

Java、Rust、C# 等任意语言用 HTTP 客户端调用即可。

---

## 接入检查清单

- [ ] 阿里云已开通短信认证
- [ ] AccessKey 已配置且仅存在于服务端
- [ ] SignName 与控制台一致
- [ ] 发码接口已加限流
- [ ] 验码通过后才写入用户手机
- [ ] 已阅读 [费用说明](./pricing.md)
- [ ] 已阅读 [产品规则](./rules.md)
