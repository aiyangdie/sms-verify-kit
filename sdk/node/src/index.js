/**
 * Aliyun PNVS SMS verification — Node.js SDK
 * @see https://smsverify.aike.ink
 */

const crypto = require('crypto');
const https = require('https');

const DEFAULT_TEMPLATES = {
  login: '100001',
  register: '100001',
  bind_phone: '100004',
  change_phone: '100002',
  reset_password: '100001',
};

const ERROR_MAP = {
  OFFER_NOT_ORDER_STATUS: '请先在阿里云控制台开通「短信认证」',
  'biz.FREQUENCY': '发送太频繁，请稍后再试',
  FREQUENCY_FAIL: '发送太频繁，请稍后再试',
  MOBILE_NUMBER_ILLEGAL: '手机号格式不正确',
  BUSINESS_LIMIT_CONTROL: '该号码今日发送次数已达上限',
  InvalidSignName: '签名无效，请检查 SignName 配置',
  'isv.SMS_TEMPLATE_ILLEGAL': '模板无效，请检查 TemplateCode',
  NETWORK: '网络异常，请稍后重试',
};

function normalizePhone(phone) {
  const digits = String(phone).replace(/\D/g, '');
  return digits.length === 11 && digits[0] === '1' ? digits : '';
}

function percentEncode(s) {
  return encodeURIComponent(s)
    .replace(/\+/g, '%20')
    .replace(/\*/g, '%2A')
    .replace(/%7E/g, '~');
}

function sign(params, secret) {
  const sorted = Object.keys(params)
    .filter((k) => k !== 'Signature' && params[k] != null)
    .sort()
    .map((k) => `${percentEncode(k)}=${percentEncode(String(params[k]))}`)
    .join('&');
  const stringToSign = `GET&${percentEncode('/')}&${percentEncode(sorted)}`;
  return crypto.createHmac('sha1', `${secret}&`).update(stringToSign).digest('base64');
}

function callApi(endpoint, params, secret) {
  const signed = { ...params, Signature: sign(params, secret) };
  const url = `${endpoint.replace(/\/$/, '')}/?${new URLSearchParams(signed).toString()}`;
  return new Promise((resolve) => {
    https
      .get(url, { timeout: 15000 }, (res) => {
        let raw = '';
        res.on('data', (c) => (raw += c));
        res.on('end', () => {
          try {
            resolve(JSON.parse(raw));
          } catch {
            resolve({ Code: 'PARSE', Message: '响应解析失败', Success: false });
          }
        });
      })
      .on('error', () => resolve({ Code: 'NETWORK', Message: '无法连接短信服务', Success: false }));
  });
}

function humanError(resp) {
  const code = String(resp.Code || '');
  const msg = String(resp.Message || '').trim();
  if (ERROR_MAP[code]) return ERROR_MAP[code];
  if (msg && msg !== 'UNKNOWN') return msg;
  return `短信服务异常（${code || '未知'}）`;
}

class AliyunPnvsClient {
  constructor(config) {
    this.accessKeyId = config.accessKeyId;
    this.accessKeySecret = config.accessKeySecret;
    this.signName = config.signName;
    this.endpoint = config.endpoint || 'https://dypnsapi.aliyuncs.com';
    this.countryCode = config.countryCode || '86';
    this.codeLength = config.codeLength ?? 4;
    this.validSeconds = config.validSeconds ?? 300;
    this.intervalSeconds = config.intervalSeconds ?? 60;
    this.templates = { ...DEFAULT_TEMPLATES, ...(config.templates || {}) };
  }

  templateForScene(scene) {
    return this.templates[scene] || this.templates.login || '100001';
  }

  baseParams(action) {
    return {
      Action: action,
      Format: 'json',
      Version: '2017-05-25',
      SignatureMethod: 'HMAC-SHA1',
      Timestamp: new Date().toISOString().replace(/\.\d{3}Z$/, 'Z'),
      SignatureVersion: '1.0',
      SignatureNonce: crypto.randomBytes(8).toString('hex'),
      AccessKeyId: this.accessKeyId,
    };
  }

  async send(phone, scene = 'login') {
    const normalized = normalizePhone(phone);
    if (!normalized) return { ok: false, message: '手机号格式无效' };
    const minutes = Math.max(1, Math.ceil(this.validSeconds / 60));
    const resp = await callApi(
      this.endpoint,
      {
        ...this.baseParams('SendSmsVerifyCode'),
        PhoneNumber: normalized,
        SignName: this.signName,
        TemplateCode: this.templateForScene(scene),
        TemplateParam: JSON.stringify({ code: '##code##', min: String(minutes) }),
        CodeType: '1',
        CodeLength: String(this.codeLength),
        ValidTime: String(this.validSeconds),
        Interval: String(this.intervalSeconds),
        CountryCode: this.countryCode,
      },
      this.accessKeySecret
    );
    if (resp.Code === 'OK' && resp.Success) {
      return { ok: true, message: '验证码已发送', biz_id: String(resp.Model?.BizId || '') };
    }
    return { ok: false, message: humanError(resp), code: String(resp.Code || '') };
  }

  async verify(phone, code) {
    const normalized = normalizePhone(phone);
    const cleaned = String(code).replace(/\s+/g, '').trim();
    if (!normalized || !cleaned) return { ok: false, message: '手机号或验证码不能为空' };
    const resp = await callApi(
      this.endpoint,
      {
        ...this.baseParams('CheckSmsVerifyCode'),
        PhoneNumber: normalized,
        VerifyCode: cleaned,
        CountryCode: this.countryCode,
        CaseAuthPolicy: '1',
      },
      this.accessKeySecret
    );
    const pass = resp.Model?.VerifyResult === 'PASS';
    if (resp.Code === 'OK' && pass) return { ok: true, message: '验证成功', pass: true };
    return { ok: false, message: pass ? humanError(resp) : '验证码错误或已过期', pass: false };
  }
}

module.exports = { AliyunPnvsClient, normalizePhone };
