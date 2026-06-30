# 复制为 config.local.php 并填写（勿提交到 Git）

return [
    // 阿里云 AccessKey（建议 RAM 子账号，权限 AliyunDypnsFullAccess）
    'access_key_id'     => getenv('SMS_ACCESS_KEY_ID') ?: '',
    'access_key_secret' => getenv('SMS_ACCESS_KEY_SECRET') ?: '',

    // 开通短信认证后控制台提供的系统签名，常见：速通互联验证码
    'sign_name'         => getenv('SMS_SIGN_NAME') ?: '',

    'endpoint'          => 'https://dypnsapi.aliyuncs.com',
    'country_code'      => '86',
    'code_length'       => 4,
    'valid_seconds'     => 300,
    'interval_seconds'  => 60,

    // 系统模板 CODE（开通后可直接用，一般无需自建模板）
    'templates' => [
        'login'          => '100001',
        'register'       => '100001',
        'bind_phone'     => '100004',
        'change_phone'   => '100002',
        'reset_password' => '100001',
    ],

    // 网关 API Key：调用 /v1/* 时在 Header 携带 X-Api-Key（留空则仅本机可访问）
    'api_key' => getenv('SMS_GATEWAY_API_KEY') ?: '',

    // 允许的来源 IP（留空不限制；生产建议配合 api_key）
    'allowed_ips' => [],
];
