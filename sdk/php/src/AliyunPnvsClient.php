<?php
declare(strict_types=1);

namespace SmsVerifyKit;

/**
 * 阿里云「号码认证 · 短信认证（API 版）」客户端。
 * 发送与校验验证码，适用于登录 / 注册 / 绑手机等场景。
 *
 * @see https://help.aliyun.com/zh/pnvs/user-guide/sms-authentication-service
 */
final class AliyunPnvsClient
{
    private const API_VERSION = '2017-05-25';

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array{
     *   access_key_id: string,
     *   access_key_secret: string,
     *   sign_name: string,
     *   endpoint?: string,
     *   country_code?: string,
     *   code_length?: int,
     *   valid_seconds?: int,
     *   interval_seconds?: int,
     *   templates?: array<string, string>
     * } $config
     */
    public function __construct(array $config)
    {
        $this->config = array_merge([
            'endpoint'         => 'https://dypnsapi.aliyuncs.com',
            'country_code'     => '86',
            'code_length'      => 4,
            'valid_seconds'    => 300,
            'interval_seconds' => 60,
            'templates'        => [
                'login'          => '100001',
                'register'       => '100001',
                'bind_phone'     => '100004',
                'change_phone'   => '100002',
                'reset_password' => '100001',
            ],
        ], $config);
    }

    /**
     * @return array{ok:bool, message:string, biz_id?:string, code?:string}
     */
    public function send(string $phone, string $scene = 'login'): array
    {
        $phone = self::normalizePhone($phone);
        if ($phone === '') {
            return ['ok' => false, 'message' => '手机号格式无效'];
        }
        $tpl = $this->templateForScene($scene);
        $min = (string) max(1, (int) ceil($this->config['valid_seconds'] / 60));
        $resp = $this->call('SendSmsVerifyCode', [
            'PhoneNumber'   => $phone,
            'SignName'      => (string) $this->config['sign_name'],
            'TemplateCode'  => $tpl,
            'TemplateParam' => json_encode(['code' => '##code##', 'min' => $min], JSON_UNESCAPED_UNICODE),
            'CodeType'      => '1',
            'CodeLength'    => (string) $this->config['code_length'],
            'ValidTime'     => (string) $this->config['valid_seconds'],
            'Interval'      => (string) $this->config['interval_seconds'],
            'CountryCode'   => (string) $this->config['country_code'],
        ]);
        if (($resp['Code'] ?? '') === 'OK' && !empty($resp['Success'])) {
            return [
                'ok'      => true,
                'message' => '验证码已发送',
                'biz_id'  => (string) ($resp['Model']['BizId'] ?? ''),
            ];
        }
        return [
            'ok'      => false,
            'message' => self::humanError($resp),
            'code'    => (string) ($resp['Code'] ?? ''),
        ];
    }

    /**
     * @return array{ok:bool, message:string, pass?:bool}
     */
    public function verify(string $phone, string $code): array
    {
        $phone = self::normalizePhone($phone);
        $code = preg_replace('/\s+/', '', trim($code)) ?? '';
        if ($phone === '' || $code === '') {
            return ['ok' => false, 'message' => '手机号或验证码不能为空'];
        }
        $resp = $this->call('CheckSmsVerifyCode', [
            'PhoneNumber'    => $phone,
            'VerifyCode'     => $code,
            'CountryCode'    => (string) $this->config['country_code'],
            'CaseAuthPolicy' => '1',
        ]);
        $pass = ($resp['Model']['VerifyResult'] ?? '') === 'PASS';
        if (($resp['Code'] ?? '') === 'OK' && $pass) {
            return ['ok' => true, 'message' => '验证成功', 'pass' => true];
        }
        return [
            'ok'      => false,
            'message' => $pass ? self::humanError($resp) : '验证码错误或已过期',
            'pass'    => false,
        ];
    }

    public function templateForScene(string $scene): string
    {
        $map = $this->config['templates'];
        return (string) ($map[$scene] ?? $map['login'] ?? '100001');
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', trim($phone)) ?? '';
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return $digits;
        }
        return '';
    }

    /** @param array<string, string> $extra */
    private function call(string $action, array $extra): array
    {
        $params = array_merge([
            'Action'           => $action,
            'Format'           => 'json',
            'Version'          => self::API_VERSION,
            'SignatureMethod'  => 'HMAC-SHA1',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion' => '1.0',
            'SignatureNonce'   => bin2hex(random_bytes(8)),
            'AccessKeyId'      => (string) $this->config['access_key_id'],
        ], $extra);
        $params['Signature'] = self::sign($params, (string) $this->config['access_key_secret']);
        $url = rtrim((string) $this->config['endpoint'], '/') . '?' . http_build_query($params);
        $ctx = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 15, 'header' => "Accept: application/json\r\n"],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['Code' => 'NETWORK', 'Message' => '无法连接短信服务', 'Success' => false];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ['Code' => 'PARSE', 'Message' => '响应解析失败', 'Success' => false];
    }

    /** @param array<string, string> $params */
    private static function sign(array $params, string $secret): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            if ($k === 'Signature' || $v === null) {
                continue;
            }
            $pairs[] = self::percentEncode($k) . '=' . self::percentEncode((string) $v);
        }
        $stringToSign = 'GET&' . self::percentEncode('/') . '&' . self::percentEncode(implode('&', $pairs));
        return base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    private static function percentEncode(string $s): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($s));
    }

    /** @param array<string, mixed> $resp */
    private static function humanError(array $resp): string
    {
        $code = (string) ($resp['Code'] ?? '');
        $msg = trim((string) ($resp['Message'] ?? ''));
        $map = [
            'OFFER_NOT_ORDER_STATUS'   => '请先在阿里云控制台开通「短信认证」',
            'biz.FREQUENCY'              => '发送太频繁，请稍后再试',
            'FREQUENCY_FAIL'             => '发送太频繁，请稍后再试',
            'MOBILE_NUMBER_ILLEGAL'      => '手机号格式不正确',
            'BUSINESS_LIMIT_CONTROL'     => '该号码今日发送次数已达上限',
            'InvalidSignName'            => '签名无效，请检查 SignName 配置',
            'isv.SMS_TEMPLATE_ILLEGAL'   => '模板无效，请检查 TemplateCode',
            'NETWORK'                    => '网络异常，请稍后重试',
        ];
        return $map[$code] ?? ($msg !== '' && $msg !== 'UNKNOWN' ? $msg : '短信服务异常（' . ($code ?: '未知') . '）');
    }
}
