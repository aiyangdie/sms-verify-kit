<?php
declare(strict_types=1);

/**
 * SmsVerifyKit HTTP Gateway
 * 任意语言通过 REST 调用短信验证，无需自己实现阿里云签名。
 *
 * POST /v1/send   { "phone": "13800138000", "scene": "login" }
 * POST /v1/verify { "phone": "13800138000", "code": "1234" }
 */

require dirname(__DIR__) . '/sdk/php/src/AliyunPnvsClient.php';

$configFile = __DIR__ . '/../config.local.php';
if (!is_file($configFile)) {
    $configFile = __DIR__ . '/../config.example.php';
}
/** @var array<string, mixed> $cfg */
$cfg = require $configFile;

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonOut(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function clientIp(): string
{
    return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0])
        ?: ($_SERVER['REMOTE_ADDR'] ?? '');
}

function requireAuth(array $cfg): void
{
    $apiKey = trim((string) ($cfg['api_key'] ?? ''));
    $allowed = $cfg['allowed_ips'] ?? [];
    if ($apiKey !== '') {
        $got = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
        if (!hash_equals($apiKey, $got)) {
            jsonOut(['ok' => false, 'message' => 'Invalid API key'], 401);
        }
        return;
    }
    if ($allowed !== [] && !in_array(clientIp(), $allowed, true)) {
        jsonOut(['ok' => false, 'message' => 'Forbidden'], 403);
    }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/' || $path === '/health') {
    jsonOut([
        'ok'      => true,
        'service' => 'SmsVerifyKit Gateway',
        'version' => '1.0.0',
        'docs'    => 'https://smsverify.aike.ink',
    ]);
}

if (!str_starts_with($path, '/v1/')) {
    jsonOut(['ok' => false, 'message' => 'Not found'], 404);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'message' => 'Method not allowed'], 405);
}

requireAuth($cfg);

if (trim((string) ($cfg['access_key_id'] ?? '')) === ''
    || trim((string) ($cfg['access_key_secret'] ?? '')) === ''
    || trim((string) ($cfg['sign_name'] ?? '')) === '') {
    jsonOut(['ok' => false, 'message' => 'Gateway 未配置 AccessKey / SignName'], 503);
}

$client = new SmsVerifyKit\AliyunPnvsClient([
    'access_key_id'     => (string) $cfg['access_key_id'],
    'access_key_secret' => (string) $cfg['access_key_secret'],
    'sign_name'         => (string) $cfg['sign_name'],
    'endpoint'          => (string) ($cfg['endpoint'] ?? 'https://dypnsapi.aliyuncs.com'),
    'country_code'      => (string) ($cfg['country_code'] ?? '86'),
    'code_length'       => (int) ($cfg['code_length'] ?? 4),
    'valid_seconds'     => (int) ($cfg['valid_seconds'] ?? 300),
    'interval_seconds'  => (int) ($cfg['interval_seconds'] ?? 60),
    'templates'         => (array) ($cfg['templates'] ?? []),
]);

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

if ($path === '/v1/send') {
    $phone = trim((string) ($body['phone'] ?? ''));
    $scene = trim((string) ($body['scene'] ?? 'login'));
    $result = $client->send($phone, $scene);
    jsonOut($result, $result['ok'] ? 200 : 400);
}

if ($path === '/v1/verify') {
    $phone = trim((string) ($body['phone'] ?? ''));
    $code = trim((string) ($body['code'] ?? ''));
    $result = $client->verify($phone, $code);
    jsonOut($result, $result['ok'] ? 200 : 400);
}

jsonOut(['ok' => false, 'message' => 'Unknown endpoint'], 404);
