# SmsVerifyKit

**开源短信验证码工具包** — 让个人开发者 5 分钟接入手机号登录 / 注册 / 绑手机。

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Website](https://img.shields.io/badge/docs-smsverify.aike.ink-green)](https://smsverify.aike.ink)
[![GitHub](https://img.shields.io/github/stars/aiyangdie/sms-verify-kit?style=social)](https://github.com/aiyangdie/sms-verify-kit)

> 代码免费 · 短信走阿里云（按量付费）· 多语言 SDK · 一键配置脚本

---

## 🚀 新手？从这里开始

```bash
git clone https://github.com/aiyangdie/sms-verify-kit.git
cd sms-verify-kit
bash scripts/setup.sh          # 交互式填 AccessKey
php bin/sms-verify.php doctor  # 检查配置
php bin/sms-verify.php send 13800138000 login   # 测试发码
```

📖 **完整图文教程：[START_HERE.md](START_HERE.md) → [5 分钟上手](docs/zh-CN/beginner-5min.md)**  
❓ **卡住？[常见问题 FAQ](docs/zh-CN/faq.md)**

---

## 10 秒看懂费用

| 项目 | 费用 |
|------|------|
| SmsVerifyKit | **免费（MIT）** |
| 阿里云短信认证 | **约 ¥0.06/条起**（[说明](docs/zh-CN/pricing.md)） |

---

## 三种用法（选一种即可）

| 方式 | 适合谁 | 命令/代码 |
|------|--------|-----------|
| **CLI 测试** | 第一次验证配置 | `php bin/sms-verify.php send ...` |
| **PHP SDK** | PHP 项目 | `AliyunPnvsClient::fromEnv()` |
| **HTTP 网关** | Java/C#/任意语言 | `php -S :8080 -t gateway/public` 或 `docker compose up` |

### PHP 接入（3 行）

```php
$client = SmsVerifyKit\AliyunPnvsClient::fromEnv('/path/to/sms-verify-kit');
$client->send('13800138000', 'login');
$client->verify('13800138000', '1234');
```

### HTTP 网关

```bash
curl -X POST http://127.0.0.1:8080/v1/send \
  -H "Content-Type: application/json" \
  -d '{"phone":"13800138000","scene":"login"}'
```

---

## 特性

- **一键配置** `scripts/setup.sh` + `.env` + `bin/sms-verify.php` 诊断
- **多语言 SDK**：PHP · Node.js · Python · Go
- **Docker 一键启动** Gateway
- **场景预设**：login / register / bind_phone / change_phone / reset_password
- **系统模板**：开通阿里云短信认证即用，无需自建签名

---

## 文档

| 文档 | 说明 |
|------|------|
| [START_HERE.md](START_HERE.md) | **新手入口** |
| [beginner-5min.md](docs/zh-CN/beginner-5min.md) | 5 分钟逐步教程 |
| [faq.md](docs/zh-CN/faq.md) | 常见问题 |
| [aliyun-setup.md](docs/zh-CN/aliyun-setup.md) | 阿里云开通 |
| [pricing.md](docs/zh-CN/pricing.md) | 费用说明 |
| [examples/](examples/) | 各语言示例 |

---

## 仓库结构

```
sms-verify-kit/
├── START_HERE.md       ← 新手先看
├── .env.example        ← 配置模板
├── bin/sms-verify.php  ← CLI 测试工具
├── scripts/setup.sh    ← 一键配置向导
├── sdk/                ← 多语言 SDK
├── gateway/            ← HTTP 网关
├── examples/           ← 可复制示例
└── docs/zh-CN/         ← 中文文档
```

---

## 作者

**aiyangdie** · aike1015@qq.com · [smsverify.aike.ink](https://smsverify.aike.ink)

MIT © 2026 aiyangdie
