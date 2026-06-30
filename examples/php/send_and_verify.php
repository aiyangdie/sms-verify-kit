<?php
/**
 * SmsVerifyKit PHP 示例 — 勿将 AccessKey 提交到 Git
 */
require __DIR__ . '/../../sdk/php/src/EnvLoader.php';
require __DIR__ . '/../../sdk/php/src/AliyunPnvsClient.php';

$client = SmsVerifyKit\AliyunPnvsClient::fromEnv(dirname(__DIR__, 2));

$result = $client->send('13800138000', 'login');
var_export($result);
