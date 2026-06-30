#!/usr/bin/env python3
"""SmsVerifyKit Python 示例 — python3 examples/python/send_and_verify.py 13800138000"""
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "sdk" / "python"))

# 加载 .env
env_file = ROOT / ".env"
if not env_file.exists():
    print("请先运行: bash scripts/setup.sh")
    sys.exit(1)
for line in env_file.read_text().splitlines():
    line = line.strip()
    if not line or line.startswith("#") or "=" not in line:
        continue
    k, _, v = line.partition("=")
    import os
    os.environ.setdefault(k.strip(), v.strip().strip('"').strip("'"))

from sms_verify_kit.client import AliyunPnvsClient

phone = sys.argv[1] if len(sys.argv) > 1 else None
if not phone:
    print("用法: python3 examples/python/send_and_verify.py <手机号>")
    sys.exit(1)

import os
client = AliyunPnvsClient(
    access_key_id=os.environ.get("SMS_ACCESS_KEY_ID", ""),
    access_key_secret=os.environ.get("SMS_ACCESS_KEY_SECRET", ""),
    sign_name=os.environ.get("SMS_SIGN_NAME", "速通互联验证码"),
)

print("发送验证码到", phone, "...")
result = client.send(phone, "login")
print(result)
if result.get("ok"):
    print(f"验码: php bin/sms-verify.php verify {phone} <验证码>")
