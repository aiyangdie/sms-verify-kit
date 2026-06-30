"""Aliyun PNVS SMS verification client — Python SDK."""

from __future__ import annotations

import base64
import hashlib
import hmac
import json
import random
import re
import string
import time
import urllib.parse
import urllib.request
from typing import Any, Dict, Optional


DEFAULT_TEMPLATES = {
    "login": "100001",
    "register": "100001",
    "bind_phone": "100004",
    "change_phone": "100002",
    "reset_password": "100001",
}

ERROR_MAP = {
    "OFFER_NOT_ORDER_STATUS": "请先在阿里云控制台开通「短信认证」",
    "biz.FREQUENCY": "发送太频繁，请稍后再试",
    "FREQUENCY_FAIL": "发送太频繁，请稍后再试",
    "MOBILE_NUMBER_ILLEGAL": "手机号格式不正确",
    "BUSINESS_LIMIT_CONTROL": "该号码今日发送次数已达上限",
    "InvalidSignName": "签名无效，请检查 SignName 配置",
    "isv.SMS_TEMPLATE_ILLEGAL": "模板无效，请检查 TemplateCode",
    "NETWORK": "网络异常，请稍后重试",
}


def normalize_phone(phone: str) -> str:
    digits = re.sub(r"\D", "", phone.strip())
    if len(digits) == 11 and digits.startswith("1"):
        return digits
    return ""


class AliyunPnvsClient:
    API_VERSION = "2017-05-25"

    def __init__(
        self,
        access_key_id: str,
        access_key_secret: str,
        sign_name: str,
        *,
        endpoint: str = "https://dypnsapi.aliyuncs.com",
        country_code: str = "86",
        code_length: int = 4,
        valid_seconds: int = 300,
        interval_seconds: int = 60,
        templates: Optional[Dict[str, str]] = None,
    ) -> None:
        self.access_key_id = access_key_id
        self.access_key_secret = access_key_secret
        self.sign_name = sign_name
        self.endpoint = endpoint.rstrip("/")
        self.country_code = country_code
        self.code_length = code_length
        self.valid_seconds = valid_seconds
        self.interval_seconds = interval_seconds
        self.templates = {**DEFAULT_TEMPLATES, **(templates or {})}

    def template_for_scene(self, scene: str) -> str:
        return self.templates.get(scene, self.templates.get("login", "100001"))

    def send(self, phone: str, scene: str = "login") -> Dict[str, Any]:
        phone = normalize_phone(phone)
        if not phone:
            return {"ok": False, "message": "手机号格式无效"}
        tpl = self.template_for_scene(scene)
        minutes = max(1, (self.valid_seconds + 59) // 60)
        resp = self._call(
            "SendSmsVerifyCode",
            {
                "PhoneNumber": phone,
                "SignName": self.sign_name,
                "TemplateCode": tpl,
                "TemplateParam": json.dumps({"code": "##code##", "min": str(minutes)}, ensure_ascii=False),
                "CodeType": "1",
                "CodeLength": str(self.code_length),
                "ValidTime": str(self.valid_seconds),
                "Interval": str(self.interval_seconds),
                "CountryCode": self.country_code,
            },
        )
        if resp.get("Code") == "OK" and resp.get("Success"):
            return {
                "ok": True,
                "message": "验证码已发送",
                "biz_id": str((resp.get("Model") or {}).get("BizId", "")),
            }
        return {"ok": False, "message": self._human_error(resp), "code": str(resp.get("Code", ""))}

    def verify(self, phone: str, code: str) -> Dict[str, Any]:
        phone = normalize_phone(phone)
        code = re.sub(r"\s+", "", code.strip())
        if not phone or not code:
            return {"ok": False, "message": "手机号或验证码不能为空"}
        resp = self._call(
            "CheckSmsVerifyCode",
            {
                "PhoneNumber": phone,
                "VerifyCode": code,
                "CountryCode": self.country_code,
                "CaseAuthPolicy": "1",
            },
        )
        passed = (resp.get("Model") or {}).get("VerifyResult") == "PASS"
        if resp.get("Code") == "OK" and passed:
            return {"ok": True, "message": "验证成功", "pass": True}
        return {
            "ok": False,
            "message": self._human_error(resp) if passed else "验证码错误或已过期",
            "pass": False,
        }

    def _call(self, action: str, extra: Dict[str, str]) -> Dict[str, Any]:
        params = {
            "Action": action,
            "Format": "json",
            "Version": self.API_VERSION,
            "SignatureMethod": "HMAC-SHA1",
            "Timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
            "SignatureVersion": "1.0",
            "SignatureNonce": "".join(random.choices(string.hexdigits.lower(), k=16)),
            "AccessKeyId": self.access_key_id,
            **extra,
        }
        params["Signature"] = self._sign(params)
        url = self.endpoint + "?" + urllib.parse.urlencode(params)
        try:
            with urllib.request.urlopen(url, timeout=15) as r:
                data = json.loads(r.read().decode())
                return data if isinstance(data, dict) else {}
        except Exception:
            return {"Code": "NETWORK", "Message": "无法连接短信服务", "Success": False}

    def _sign(self, params: Dict[str, str]) -> str:
        sorted_items = sorted((k, v) for k, v in params.items() if k != "Signature" and v is not None)
        canonical = "&".join(f"{self._percent_encode(k)}={self._percent_encode(str(v))}" for k, v in sorted_items)
        string_to_sign = f"GET&{self._percent_encode('/')}&{self._percent_encode(canonical)}"
        digest = hmac.new(
            (self.access_key_secret + "&").encode(),
            string_to_sign.encode(),
            hashlib.sha1,
        ).digest()
        return base64.b64encode(digest).decode()

    @staticmethod
    def _percent_encode(s: str) -> str:
        return urllib.parse.quote(s, safe="~").replace("+", "%20").replace("*", "%2A").replace("%7E", "~")

    @staticmethod
    def _human_error(resp: Dict[str, Any]) -> str:
        code = str(resp.get("Code", ""))
        msg = str(resp.get("Message", "")).strip()
        if code in ERROR_MAP:
            return ERROR_MAP[code]
        if msg and msg != "UNKNOWN":
            return msg
        return f"短信服务异常（{code or '未知'}）"
