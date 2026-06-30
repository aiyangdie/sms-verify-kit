<?php
declare(strict_types=1);

/**
 * 统一加载配置：优先 config.local.php → .env → config.example.php
 *
 * @return array<string, mixed>
 */
function smsverifykit_load_config(string $repoRoot): array
{
    require_once $repoRoot . '/sdk/php/src/EnvLoader.php';

    $local = $repoRoot . '/gateway/config.local.php';
    if (is_file($local)) {
        /** @var array<string, mixed> $cfg */
        $cfg = require $local;
        return $cfg;
    }

    SmsVerifyKit\EnvLoader::loadFile($repoRoot . '/.env');
    $c = SmsVerifyKit\EnvLoader::clientConfig();
    return [
        'access_key_id'     => $c['access_key_id'],
        'access_key_secret' => $c['access_key_secret'],
        'sign_name'         => $c['sign_name'],
        'endpoint'          => $c['endpoint'],
        'country_code'      => $c['country_code'],
        'code_length'       => $c['code_length'],
        'valid_seconds'     => $c['valid_seconds'],
        'interval_seconds'  => $c['interval_seconds'],
        'api_key'           => SmsVerifyKit\EnvLoader::get('SMS_GATEWAY_API_KEY') ?? '',
        'templates'         => [
            'login'          => SmsVerifyKit\EnvLoader::get('SMS_TPL_LOGIN') ?? '100001',
            'register'       => SmsVerifyKit\EnvLoader::get('SMS_TPL_REGISTER') ?? '100001',
            'bind_phone'     => SmsVerifyKit\EnvLoader::get('SMS_TPL_BIND') ?: '100004',
            'change_phone'   => SmsVerifyKit\EnvLoader::get('SMS_TPL_CHANGE') ?: '100002',
            'reset_password' => SmsVerifyKit\EnvLoader::get('SMS_TPL_LOGIN') ?? '100001',
        ],
    ];
}
