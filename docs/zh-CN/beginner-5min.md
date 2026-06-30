# 新手 5 分钟上手（复制粘贴即可）

> 零基础也能完成。只需：Git、PHP 7.4+（测 CLI/网关）、阿里云账号。

---

## 第 0 步：你需要准备什么？

| 准备项 | 说明 |
|--------|------|
| 阿里云账号 | 需实名认证 |
| 开通短信认证 | [控制台一键开通](https://dypns.console.aliyun.com/) |
| AccessKey | RAM 子账号创建，权限 `AliyunDypnsFullAccess` |
| SignName | 控制台显示的系统签名，常见：`速通互联验证码` |

**费用**：代码免费；短信约 **¥0.06/条**，测试也计费。

---

## 第 1 步：下载项目

```bash
git clone https://github.com/aiyangdie/sms-verify-kit.git
cd sms-verify-kit
```

---

## 第 2 步：一键配置（推荐）

```bash
bash scripts/setup.sh
```

按提示输入 AccessKey 和 SignName，脚本会：

1. 生成 `.env`
2. 生成 `gateway/config.local.php`
3. 运行 `doctor` 检查配置
4. （可选）用真实手机号测发一条短信

---

## 第 3 步：命令行测试

```bash
# 检查配置是否完整
php bin/sms-verify.php doctor

# 发送验证码（会真实发短信、会扣费）
php bin/sms-verify.php send 13800138000 login

# 收到短信后校验
php bin/sms-verify.php verify 13800138000 1234
```

看到 `✓ 验证成功` 就说明接通了。

---

## 第 4 步：接到你的项目里

### 方式 A：PHP 项目（3 行代码）

```php
<?php
require 'path/to/sms-verify-kit/sdk/php/src/EnvLoader.php';
require 'path/to/sms-verify-kit/sdk/php/src/AliyunPnvsClient.php';

$client = SmsVerifyKit\AliyunPnvsClient::fromEnv('/path/to/sms-verify-kit');

// 你的 API：发码
$result = $client->send($_POST['phone'], 'login');

// 你的 API：验码 → 通过后发 JWT / Session
$check = $client->verify($_POST['phone'], $_POST['code']);
if ($check['ok']) {
    // 登录成功逻辑
}
```

### 方式 B：任意语言 — 启动 HTTP 网关

```bash
cd sms-verify-kit
php -S 127.0.0.1:8080 -t gateway/public
```

另开终端：

```bash
# 发码
curl -X POST http://127.0.0.1:8080/v1/send \
  -H "Content-Type: application/json" \
  -d '{"phone":"13800138000","scene":"login"}'

# 验码
curl -X POST http://127.0.0.1:8080/v1/verify \
  -H "Content-Type: application/json" \
  -d '{"phone":"13800138000","code":"1234"}'
```

浏览器打开 `http://127.0.0.1:8080/docs` 可看 API 说明。

### 方式 C：Docker（有 Docker 的新手）

```bash
cp .env.example .env
# 编辑 .env 填入密钥
docker compose up -d
curl http://127.0.0.1:8080/health
```

---

## 第 5 步：业务里怎么用？

```
用户填手机号 → 你后端调 send → 用户收短信
用户填验证码 → 你后端调 verify → 成功后再登录/注册/绑手机
```

**千万不要**：在前端/小程序里放 AccessKey。  
**千万不要**：没验码就绑定手机号。

场景对照表见 [scenes.md](./scenes.md)。

---

## 还是不行？

看 [常见问题 FAQ](./faq.md)，或 GitHub 提 Issue。

---

## 下一步

- [阿里云开通详细截图说明](./aliyun-setup.md)
- [费用说明](./pricing.md)
- [examples/](../examples/) 各语言完整示例
