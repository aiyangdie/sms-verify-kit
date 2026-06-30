# HTTP 网关 — 新手本地启动

```bash
# 1. 确保已配置 .env
bash ../scripts/setup.sh

# 2. 启动（默认 http://127.0.0.1:8080）
php -S 127.0.0.1:8080 -t public

# 3. 浏览器打开 API 说明
# http://127.0.0.1:8080/docs

# 4. 健康检查
curl http://127.0.0.1:8080/health
```

## Docker 启动

在项目根目录：

```bash
cp .env.example .env   # 填好密钥
docker compose up -d
curl http://127.0.0.1:8080/health
```

## 生产部署

- Web 根目录指向 `gateway/public`
- 确保 PHP 7.4+ 可读项目根目录 `.env`
- 建议设置 `SMS_GATEWAY_API_KEY` 并在请求头携带 `X-Api-Key`
