
<?php
return [
    'name' => env('APP_NAME', 'Lumen'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'log' => env('LOG_CHANNEL', 'single'),
    'log_max_files' => 5,
    'log_level' => env('LOG_LEVEL', 'debug'),
    'log_path' => storage_path('logs/lumen.log'),
    'providers' => [
        // ...existing providers...
    ],
];
