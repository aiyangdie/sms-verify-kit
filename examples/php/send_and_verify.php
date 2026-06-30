<?php
/**
 * SmsVerifyKit PHP 示例 — 勿将 AccessKey 提交到 Git
 */
require __DIR__ . '/../../sdk/php/src/AliyunPnvsClient.php';

$client = new SmsVerifyKit\AliyunPnvsClient([
    'access_key_id'     => getenv('SMS_ACCESS_KEY_ID') ?: '',
    'access_key_secret' => getenv('SMS_ACCESS_KEY_SECRET') ?: '',
    'sign_name'         => getenv('SMS_SIGN_NAME') ?: '速通互联验证码',
]);

$result = $client->send('13800138000', 'login');
var_export($result);
