# 业务场景接入示例

## 登录 / 注册（手机号 + 验证码）

```
用户输入手机号
    → POST /api/sms/send  { phone, scene: "login" }
    → 用户收到短信
    → POST /api/sms/verify { phone, code }
    → 验证通过
    → 查 users 表：有则登录，无则注册
    → 返回 JWT / Set-Cookie
```

**注意**：同一套模板 `100001` 可用于登录和注册，业务上靠「用户是否存在」区分。

---

## 绑定手机号（已有账号）

```
用户已登录
    → 输入新手机号
    → send(phone, "bind_phone")
    → verify(phone, code)
    → UPDATE users SET phone=?, verified_at=NOW()
```

换绑使用 `change_phone` 场景（模板 `100002`）。

---

## 找回密码

```
用户输入注册手机号
    → send(phone, "reset_password")
    → verify(phone, code)
    → 允许设置新密码（仍需校验用户存在）
```

**不要**在验码前暴露「该手机号是否已注册」（防枚举）。可统一提示「若号码存在将收到短信」。

---

## 限流建议

| 维度 | 建议值 |
|------|--------|
| 同手机号 | 60 秒内 1 次（SDK 已传 Interval 给阿里云） |
| 同 IP | 每小时 ≤ 15 次 |
| 验码失败 | 15 分钟内 ≤ 8 次 |

---

## 与 aike.ink 的关系

[aike.ink](https://aike.ink) 商城使用了同一套阿里云短信认证能力。SmsVerifyKit 是从生产实践抽离的**独立开源版**，不依赖 aike 业务代码，任何人可单独使用。
