#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * SmsVerifyKit 命令行工具 — 新手测试用
 *
 *   php bin/sms-verify.php doctor          检查配置
 *   php bin/sms-verify.php send 138... login   发送验证码
 *   php bin/sms-verify.php verify 138... 1234  校验验证码
 */

$root = dirname(__DIR__);
require $root . '/sdk/php/src/EnvLoader.php';
require $root . '/sdk/php/src/AliyunPnvsClient.php';

use SmsVerifyKit\AliyunPnvsClient;
use SmsVerifyKit\EnvLoader;

function out(string $msg, string $type = 'info'): void
{
    $colors = ['ok' => "\033[32m", 'err' => "\033[31m", 'warn' => "\033[33m", 'info' => ''];
    $c = $colors[$type] ?? '';
    fwrite(STDOUT, ($c !== '' ? $c : '') . $msg . ($c !== '' ? "\033[0m" : '') . PHP_EOL);
}

function usage(): void
{
    out('SmsVerifyKit CLI — 短信验证测试工具', 'info');
    echo PHP_EOL;
    echo "用法:" . PHP_EOL;
    echo "  php bin/sms-verify.php doctor" . PHP_EOL;
    echo "  php bin/sms-verify.php send <手机号> [场景]" . PHP_EOL;
    echo "  php bin/sms-verify.php verify <手机号> <验证码>" . PHP_EOL;
    echo PHP_EOL;
    echo "场景 scene: login | register | bind_phone | change_phone | reset_password" . PHP_EOL;
    echo PHP_EOL;
    echo "首次使用: cp .env.example .env  然后  bash scripts/setup.sh" . PHP_EOL;
    exit(1);
}

$args = array_slice($argv, 1);
if ($args === []) {
    usage();
}

$cmd = $args[0];

if ($cmd === 'doctor') {
    EnvLoader::loadProjectEnv($root);
    $d = EnvLoader::doctor();
    if ($d['ok']) {
        out('✓ 配置完整，可以发送短信', 'ok');
        $cfg = EnvLoader::clientConfig();
        out('  SignName: ' . $cfg['sign_name'], 'info');
        out('  AccessKey: ' . substr($cfg['access_key_id'], 0, 6) . '...', 'info');
        exit(0);
    }
    out('✗ 配置不完整，缺少: ' . implode(', ', $d['missing']), 'err');
    foreach ($d['hints'] as $h) {
        out('  → ' . $h, 'warn');
    }
    exit(1);
}

if ($cmd === 'send') {
    $phone = $args[1] ?? '';
    $scene = $args[2] ?? 'login';
    if ($phone === '') {
        out('请提供手机号', 'err');
        exit(1);
    }
    $client = AliyunPnvsClient::fromEnv($root);
    out("发送验证码到 {$phone}（场景: {$scene}）...", 'info');
    $r = $client->send($phone, $scene);
    if ($r['ok']) {
        out('✓ ' . $r['message'], 'ok');
        exit(0);
    }
    out('✗ ' . $r['message'] . (isset($r['code']) ? ' [' . $r['code'] . ']' : ''), 'err');
    exit(1);
}

if ($cmd === 'verify') {
    $phone = $args[1] ?? '';
    $code = $args[2] ?? '';
    if ($phone === '' || $code === '') {
        out('用法: verify <手机号> <验证码>', 'err');
        exit(1);
    }
    $client = AliyunPnvsClient::fromEnv($root);
    $r = $client->verify($phone, $code);
    if ($r['ok']) {
        out('✓ 验证成功', 'ok');
        exit(0);
    }
    out('✗ ' . $r['message'], 'err');
    exit(1);
}

usage();
