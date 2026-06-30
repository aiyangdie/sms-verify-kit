#!/usr/bin/env bash
# SmsVerifyKit 一键配置向导 — 新手运行此脚本即可
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   SmsVerifyKit 新手配置向导              ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo "本工具帮你生成 .env 配置文件。"
echo -e "${YELLOW}提醒：短信由阿里云按量收费（约 ¥0.06/条），测试也会扣费。${NC}"
echo ""

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "✓ 已创建 .env（从 .env.example 复制）"
else
  echo "✓ 检测到已有 .env，将更新你填写的项"
fi

read -rp "阿里云 AccessKey ID: " AK_ID
read -rsp "阿里云 AccessKey Secret: " AK_SECRET
echo ""
read -rp "短信签名 SignName [速通互联验证码]: " SIGN
SIGN="${SIGN:-速通互联验证码}"

# 写入 .env（简单替换）
write_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" .env 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

write_env SMS_ACCESS_KEY_ID "$AK_ID"
write_env SMS_ACCESS_KEY_SECRET "$AK_SECRET"
write_env SMS_SIGN_NAME "$SIGN"

# 同步 gateway 配置
mkdir -p gateway
cat > gateway/config.local.php <<PHPEOF
<?php
declare(strict_types=1);
/** 由 scripts/setup.sh 自动生成，也可手动编辑 */
require dirname(__DIR__) . '/sdk/php/src/EnvLoader.php';
SmsVerifyKit\EnvLoader::loadFile(dirname(__DIR__) . '/.env');
\$c = SmsVerifyKit\EnvLoader::clientConfig();
return [
    'access_key_id'     => \$c['access_key_id'],
    'access_key_secret' => \$c['access_key_secret'],
    'sign_name'         => \$c['sign_name'],
    'endpoint'          => \$c['endpoint'],
    'country_code'      => \$c['country_code'],
    'code_length'       => \$c['code_length'],
    'valid_seconds'     => \$c['valid_seconds'],
    'interval_seconds'  => \$c['interval_seconds'],
    'api_key'           => getenv('SMS_GATEWAY_API_KEY') ?: '',
    'templates' => [
        'login'          => getenv('SMS_TPL_LOGIN') ?: '100001',
        'register'       => getenv('SMS_TPL_REGISTER') ?: '100001',
        'bind_phone'     => getenv('SMS_TPL_BIND') ?: '100004',
        'change_phone'   => getenv('SMS_TPL_CHANGE') ?: '100002',
        'reset_password' => getenv('SMS_TPL_LOGIN') ?: '100001',
    ],
];
PHPEOF

echo ""
echo -e "${GREEN}✓ 配置已保存到 .env 和 gateway/config.local.php${NC}"
echo ""

# 健康检查
echo "正在检查配置..."
php bin/sms-verify.php doctor
echo ""

read -rp "是否用真实手机号测试发送？(y/N): " DO_TEST
if [[ "${DO_TEST,,}" == "y" ]]; then
  read -rp "手机号（11位）: " PHONE
  php bin/sms-verify.php send "$PHONE" login
  echo ""
  read -rp "收到验证码后，输入验证码: " CODE
  php bin/sms-verify.php verify "$PHONE" "$CODE"
fi

echo ""
echo -e "${GREEN}完成！下一步：${NC}"
echo "  1. 看新手文档：docs/zh-CN/beginner-5min.md"
echo "  2. PHP 代码：\$client = AliyunPnvsClient::fromEnv();"
echo "  3. 启动网关：cd gateway && php -S 0.0.0.0:8080 -t public"
echo "  4. GitHub：https://github.com/aiyangdie/sms-verify-kit"
echo ""
