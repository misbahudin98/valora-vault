<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://valora-vault.test'),
        env('APP_URL'),
        'http://valora-vault.test',
        'https://valora-vault.test',
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ])),
    'allowed_origins_patterns' => ['*.valora-vault.test', '*.test'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
