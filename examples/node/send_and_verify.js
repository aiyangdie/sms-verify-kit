#!/usr/bin/env node
/**
 * SmsVerifyKit Node.js 示例
 * 运行：node examples/node/send_and_verify.js 13800138000
 */
const path = require('path');
const fs = require('fs');
const { AliyunPnvsClient } = require('../../sdk/node/src/index');

function loadEnv() {
  const envPath = path.join(__dirname, '../../.env');
  if (!fs.existsSync(envPath)) {
    console.error('请先运行: bash scripts/setup.sh');
    process.exit(1);
  }
  for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
    const t = line.trim();
    if (!t || t.startsWith('#') || !t.includes('=')) continue;
    const i = t.indexOf('=');
    const k = t.slice(0, i).trim();
    const v = t.slice(i + 1).trim().replace(/^["']|["']$/g, '');
    if (!process.env[k]) process.env[k] = v;
  }
}

loadEnv();

const phone = process.argv[2];
if (!phone) {
  console.log('用法: node examples/node/send_and_verify.js <手机号>');
  process.exit(1);
}

const client = new AliyunPnvsClient({
  accessKeyId: process.env.SMS_ACCESS_KEY_ID,
  accessKeySecret: process.env.SMS_ACCESS_KEY_SECRET,
  signName: process.env.SMS_SIGN_NAME || '速通互联验证码',
});

(async () => {
  console.log('发送验证码到', phone, '...');
  const send = await client.send(phone, 'login');
  console.log(send);
  if (!send.ok) process.exit(1);
  console.log('请在终端输入收到的验证码，然后运行:');
  console.log(`  php bin/sms-verify.php verify ${phone} <验证码>`);
})();
