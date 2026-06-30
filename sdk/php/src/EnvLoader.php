<?php
declare(strict_types=1);

namespace SmsVerifyKit;

/**
 * 从 .env 或系统环境变量加载配置，新手只需填 .env 即可。
 */
final class EnvLoader
{
    /** @var array<string, string|null> */
    private static array $cache = [];

    public static function loadFile(string $path): bool
    {
        if (!is_readable($path)) {
            return false;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            self::$cache[$key] = $value;
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        return true;
    }

    /** 自动查找项目根目录下的 .env */
    public static function loadProjectEnv(?string $startDir = null): bool
    {
        $dir = $startDir ?: getcwd() ?: '.';
        for ($i = 0; $i < 6; $i++) {
            $file = rtrim($dir, '/') . '/.env';
            if (is_readable($file)) {
                return self::loadFile($file);
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }
        return false;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }

    /** @return array<string, mixed> */
    public static function clientConfig(): array
    {
        return [
            'access_key_id'     => (string) (self::get('SMS_ACCESS_KEY_ID') ?? ''),
            'access_key_secret' => (string) (self::get('SMS_ACCESS_KEY_SECRET') ?? ''),
            'sign_name'         => (string) (self::get('SMS_SIGN_NAME') ?? '速通互联验证码'),
            'endpoint'          => (string) (self::get('SMS_ENDPOINT') ?? 'https://dypnsapi.aliyuncs.com'),
            'country_code'      => (string) (self::get('SMS_COUNTRY_CODE') ?? '86'),
            'code_length'       => (int) (self::get('SMS_CODE_LENGTH') ?? '4'),
            'valid_seconds'     => (int) (self::get('SMS_VALID_SECONDS') ?? '300'),
            'interval_seconds'  => (int) (self::get('SMS_INTERVAL_SECONDS') ?? '60'),
        ];
    }

    /** @return array{ok:bool, missing:list<string>, hints:list<string>} */
    public static function doctor(): array
    {
        self::loadProjectEnv();
        $missing = [];
        $hints = [];
        if (trim((string) self::get('SMS_ACCESS_KEY_ID')) === '') {
            $missing[] = 'SMS_ACCESS_KEY_ID';
        }
        if (trim((string) self::get('SMS_ACCESS_KEY_SECRET')) === '') {
            $missing[] = 'SMS_ACCESS_KEY_SECRET';
        }
        if (trim((string) self::get('SMS_SIGN_NAME')) === '') {
            $missing[] = 'SMS_SIGN_NAME';
            $hints[] = 'SignName 在阿里云号码认证控制台 → 短信认证 页面查看，常见值：速通互联验证码';
        }
        if ($missing !== []) {
            $hints[] = '复制 .env.example 为 .env 并填写，或运行：bash scripts/setup.sh';
        }
        return ['ok' => $missing === [], 'missing' => $missing, 'hints' => $hints];
    }
}
