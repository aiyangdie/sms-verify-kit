#!/usr/bin/env bash
# 通过 Gateway 测试 — 需先启动: php -S 127.0.0.1:8080 -t gateway/public
set -euo pipefail
BASE="${SMS_GATEWAY_URL:-http://127.0.0.1:8080}"
PHONE="${1:-}"
if [[ -z "$PHONE" ]]; then
  echo "用法: bash examples/curl/test.sh <手机号>"
  exit 1
fi
API_KEY="${SMS_GATEWAY_API_KEY:-}"
HDR=()
[[ -n "$API_KEY" ]] && HDR=(-H "X-Api-Key: $API_KEY")

echo "==> GET /health"
curl -sS "$BASE/health" | python3 -m json.tool
echo ""
echo "==> POST /v1/send"
curl -sS -X POST "$BASE/v1/send" \
  -H "Content-Type: application/json" \
  "${HDR[@]}" \
  -d "{\"phone\":\"$PHONE\",\"scene\":\"login\"}" | python3 -m json.tool
echo ""
echo "收到验证码后运行:"
echo "  curl -X POST $BASE/v1/verify -H 'Content-Type: application/json' -d '{\"phone\":\"$PHONE\",\"code\":\"验证码\"}'"
