# SmsVerifyKit

**开源短信验证码工具包** — 让个人开发者 10 分钟接入手机号登录 / 注册 / 绑手机。

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Website](https://img.shields.io/badge/docs-smsverify.aike.ink-green)](https://smsverify.aike.ink)
[![GitHub](https://img.shields.io/github/stars/aiyangdie/sms-verify-kit?style=social)](https://github.com/aiyangdie/sms-verify-kit)

> 代码免费 · 短信走阿里云（按量付费）· 多语言 SDK · 可选 HTTP 网关

[中文文档](docs/zh-CN/getting-started.md) · [官网](https://smsverify.aike.ink) · [费用说明](docs/zh-CN/pricing.md) · [产品规则](docs/zh-CN/rules.md)

---

## 为什么做这个项目？

很多个人开发者想做「手机号验证码登录」，却被这些问题挡住：

- 传统短信要申请签名、模板，周期长
- 各语言对接阿里云签名算法麻烦
- 不清楚**到底花多少钱**
- 网上示例代码零散、不可生产

**SmsVerifyKit** 解决的是：**开通阿里云短信认证 → 填配置 → 调 SDK**，其余文档讲清楚。

---

## 特性

- **多语言 SDK**：PHP · Node.js · Python · Go（同一套 API 设计）
- **HTTP Gateway**：Java / Rust / C# 等任意语言 `curl` 即用
- **场景预设**：`login` · `register` · `bind_phone` · `change_phone` · `reset_password`
- **系统模板**：无需自建签名模板（阿里云开通即用）
- **全配置化**：AccessKey、SignName、模板 CODE 均可改
- **MIT 开源**：可商用

---

## 10 秒看懂费用

| 项目 | 费用 |
|------|------|
| SmsVerifyKit | **免费** |
| 阿里云短信认证 | **约 ¥0.06/条起**（按量，见 [pricing.md](docs/zh-CN/pricing.md)） |

---

## 快速开始

```php
use SmsVerifyKit\AliyunPnvsClient;

$client = new AliyunPnvsClient([
    'access_key_id'     => 'LTAI...',
    'access_key_secret' => '...',
    'sign_name'         => '速通互联验证码',
]);

$client->send('13800138000', 'login');
$client->verify('13800138000', '1234');
```

详见 [getting-started.md](docs/zh-CN/getting-started.md)

---

## 仓库结构

```
sms-verify-kit/
├── sdk/php/          # PHP SDK
├── sdk/node/         # Node.js SDK
├── sdk/python/       # Python SDK
├── sdk/go/           # Go SDK
├── gateway/          # 自托管 REST 网关
├── docs/zh-CN/       # 中文文档
└── website/          # 官网静态页
```

---

## 文档

| 文档 | 说明 |
|------|------|
| [getting-started.md](docs/zh-CN/getting-started.md) | 快速开始 |
| [aliyun-setup.md](docs/zh-CN/aliyun-setup.md) | 阿里云开通 |
| [pricing.md](docs/zh-CN/pricing.md) | 费用说明 |
| [rules.md](docs/zh-CN/rules.md) | 产品规则 |
| [scenes.md](docs/zh-CN/scenes.md) | 业务场景 |

---

## 贡献

Issue / PR 欢迎。请**不要**在 PR 中包含 AccessKey 或真实用户手机号。

---

## 作者

**aiyangdie** · aike1015@qq.com

---

## License

MIT © 2026 aiyangdie
