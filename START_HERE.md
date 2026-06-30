# 👋 新手从这里开始

**第一次用？只看这一页。**

## 三步上手

```bash
git clone https://github.com/aiyangdie/sms-verify-kit.git
cd sms-verify-kit
bash scripts/setup.sh
```

配置完成后：

```bash
php bin/sms-verify.php doctor          # 检查
php bin/sms-verify.php send 138... login   # 测试发码
```

## 详细教程

📖 **[5 分钟新手上手（逐步说明）](docs/zh-CN/beginner-5min.md)**

## 遇到问题？

❓ **[常见问题 FAQ](docs/zh-CN/faq.md)**

## 关键提醒

| ✅ 要做 | ❌ 不要做 |
|---------|-----------|
| AccessKey 放服务端 `.env` | 把 Key 写进前端或 Git |
| 验码通过后再登录/绑手机 | 没验码就信任用户 |
| 设置阿里云费用预警 | 以为短信是免费的 |

**费用**：工具免费；阿里云短信约 **¥0.06/条** → [详细说明](docs/zh-CN/pricing.md)

---

官网：https://smsverify.aike.ink
