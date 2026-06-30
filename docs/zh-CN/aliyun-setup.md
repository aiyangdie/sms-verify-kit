# 阿里云开通指南

SmsVerifyKit 使用阿里云 **号码认证 · 短信认证（API 版）**。你需要自己的阿里云账号，本工具包**不提供**短信通道，也**不代收**任何费用。

## 一、开通步骤（约 10 分钟）

1. 登录 [阿里云号码认证控制台](https://dypns.console.aliyun.com/)
2. 左侧进入 **短信认证** → 点击 **立即开通**
3. 开通后会分配 **系统签名**（常见名称：`速通互联验证码`）和 **系统模板**
4. 创建 RAM 子账号（推荐）：
   - [RAM 控制台](https://ram.console.aliyun.com/) → 用户 → 创建用户
   - 勾选「OpenAPI 调用访问」→ 保存 **AccessKey ID / Secret**
   - 授权策略：`AliyunDypnsFullAccess`（或自定义仅 PNVS 权限）
5. 把 AccessKey、SignName 填入 SmsVerifyKit 配置即可

## 二、系统模板 CODE（开通后可直接用）

| 场景 | 默认 TemplateCode | 说明 |
|------|-------------------|------|
| 登录 / 注册 / 找回密码 | `100001` | 验证码登录、注册、重置密码 |
| 绑定手机号 | `100004` | 首次绑定手机 |
| 更换手机号 | `100002` | 换绑新号码 |

> 以上为阿里云提供的**系统模板**，无需自己申请短信签名和模板，开通短信认证即可使用。

## 三、配置项说明

| 配置项 | 必填 | 说明 |
|--------|------|------|
| `access_key_id` | 是 | RAM 用户的 AccessKey ID |
| `access_key_secret` | 是 | 对应 Secret，**只存服务端** |
| `sign_name` | 是 | 控制台显示的签名，如 `速通互联验证码` |
| `endpoint` | 否 | 默认 `https://dypnsapi.aliyuncs.com` |
| `code_length` | 否 | 验证码位数 4–8，默认 4 |
| `valid_seconds` | 否 | 有效期秒数，默认 300（5 分钟） |
| `interval_seconds` | 否 | 同号重发间隔，默认 60 秒 |

## 四、测试发送

控制台或本项目的 Gateway 健康检查后，用真实手机号测试：

```bash
curl -X POST https://你的网关/v1/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: 你的密钥" \
  -d '{"phone":"13800138000","scene":"login"}'
```

**测试短信同样计费**，请控制测试次数。

## 五、常见问题

| 错误 | 原因 | 处理 |
|------|------|------|
| `OFFER_NOT_ORDER_STATUS` | 未开通短信认证 | 控制台开通 |
| `InvalidSignName` | SignName 填错 | 对照控制台系统签名 |
| `biz.FREQUENCY` | 发送太频繁 | 等待间隔后再发 |
| `BUSINESS_LIMIT_CONTROL` | 单号日限额 | 次日再试或联系阿里云 |

官方文档：[短信认证接入说明](https://help.aliyun.com/zh/pnvs/user-guide/sms-authentication-service)
