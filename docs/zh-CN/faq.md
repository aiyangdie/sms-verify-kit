# 常见问题 FAQ

## 配置相关

### Q：运行 doctor 提示缺少 SMS_ACCESS_KEY_ID？

```bash
cp .env.example .env
bash scripts/setup.sh
```

或手动编辑 `.env` 填三项：`SMS_ACCESS_KEY_ID`、`SMS_ACCESS_KEY_SECRET`、`SMS_SIGN_NAME`。

---

### Q：SignName 填什么？

登录 [号码认证控制台](https://dypns.console.aliyun.com/) → **短信认证**，页面会显示系统签名。  
国内常见为 **`速通互联验证码`**，以控制台为准，一字不差。

---

### Q：AccessKey 放哪里？

**只放服务端**：`.env`、服务器环境变量、密钥管理服务。  
**禁止**：前端 JS、Android/iOS 安装包、Git 仓库、截图发群。

---

## 发送失败

### Q：提示「请先在阿里云控制台开通短信认证」

去 [dypns.console.aliyun.com](https://dypns.console.aliyun.com/) 开通 **短信认证（API 版）**，不是「普通短信 SMS」产品。

---

### Q：提示「发送太频繁」

同一号码默认 60 秒内只能发 1 次。等一分钟再试，或检查是否被恶意刷接口（需加 IP 限流）。

---

### Q：提示「签名无效 InvalidSignName」

SignName 与控制台不一致。复制控制台显示的完整签名，不要自己编造。

---

### Q：提示「模板无效」

默认模板 CODE（100001/100004/100002）在开通短信认证后可用。若你改过模板，检查 `.env` 里 `SMS_TPL_*` 是否正确。

---

### Q：返回 UNKNOWN 或网络错误

1. 服务器能否访问 `dypnsapi.aliyuncs.com`（出口防火墙）  
2. AccessKey Secret 是否复制完整、无空格  
3. 账户是否欠费停服  

---

## 费用相关

### Q：测试发一条要钱吗？

**要。** 成功发送即按阿里云短信认证单价计费（约 ¥0.06/条起）。

---

### Q：这个项目收费吗？

**SmsVerifyKit 源码永久免费（MIT）。** 只有阿里云短信费由你自己承担。

---

### Q：怎么控制成本？

1. 阿里云设置 [费用预警](https://usercenter2.aliyun.com/home/expense-control)  
2. 发码接口加图形验证码 / 登录失败锁定  
3. 同 IP、同号码限流  
4. 开发环境少测真实号码  

---

## 开发相关

### Q：我是 Java / C# / Go，没有 PHP 怎么办？

用 **HTTP Gateway**：`php -S ... -t gateway/public` 或 Docker，业务语言只调 REST。  
或直接复制 `sdk/go`、`sdk/node` 等到你的项目。

---

### Q：Gateway 启动后 503 未配置？

项目根目录要有 `.env` 或 `gateway/config.local.php`，且三项密钥已填。运行 `php bin/sms-verify.php doctor` 检查。

---

### Q：验码成功后还要做什么？

SDK **只负责验码**。你需要自己：

- 创建/查询用户  
- 签发 Session 或 JWT  
- 写入「手机号已验证」标记  

---

### Q：和 aike.ink 是什么关系？

[aike.ink](https://aike.ink) 使用了相同阿里云能力；SmsVerifyKit 是抽出来的**独立开源工具**，不依赖 aike 代码。

---

## 还有问题？

[GitHub Issues](https://github.com/aiyangdie/sms-verify-kit/issues) · aike1015@qq.com
