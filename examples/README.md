# 示例代码

每个示例都假设你已在项目根目录运行过 `bash scripts/setup.sh`，`.env` 已配置好。

## 最快验证

```bash
php bin/sms-verify.php doctor
php bin/sms-verify.php send 13800138000 login
php bin/sms-verify.php verify 13800138000 验证码
```

## 目录

| 目录 | 语言 | 说明 |
|------|------|------|
| [php/](php/) | PHP | 最简 send + verify |
| [node/](node/) | Node.js | 需 Node 14+ |
| [python/](python/) | Python | 需 Python 3.8+ |
| [curl/](curl/) | 任意 | 通过 Gateway HTTP 调用 |

## 启动 Gateway 再测 curl

```bash
php -S 127.0.0.1:8080 -t gateway/public
bash examples/curl/test.sh 13800138000
```
