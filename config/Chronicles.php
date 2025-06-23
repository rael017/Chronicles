<?php

// Usaremos uma função auxiliar para carregar variáveis de ambiente de forma segura.
// Em um projeto real, uma biblioteca como vlucas/phpdotenv seria usada no bootstrap.
// Para manter o projeto autocontido, vamos definir a função aqui ou em um helper.
if (!function_exists('chronicles_env')) {
    function chronicles_env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        if (defined($value)) {
            $value = constant($value);
        }
        return $value;
    }
}


return [
    'enabled' => chronicles_env('CHRONICLES_ENABLED', true),
    'queue_driver' => chronicles_env('CHRONICLES_QUEUE_DRIVER', 'redis'),
    'storage_driver' => chronicles_env('CHRONICLES_STORAGE_DRIVER', 'mysql'),
    'log_level' => chronicles_env('CHRONICLES_LOG_LEVEL', 'INFO'),

    'connections' => [
        'mysql' => [
            'host' => chronicles_env('DB_HOST', '127.0.0.1'),
            'port' => chronicles_env('DB_PORT', '3306'),
            'database' => chronicles_env('DB_DATABASE', 'chronicles_db'),
            'username' => chronicles_env('DB_USERNAME', 'root'),
            'password' => chronicles_env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ],
        'redis' => [
            'host' => chronicles_env('REDIS_HOST', '127.0.0.1'),
            'port' => chronicles_env('REDIS_PORT', 6379),
            'password' => chronicles_env('REDIS_PASSWORD', null),
            'database' => chronicles_env('REDIS_DATABASE', 0),
            'timeout' => 1.0,
            'read_timeout' => 1.0,
            'persistent' => true,
        ],
        'file' => [
            'events_path' => __DIR__ . '/../storage/logs/chronicles_events.log',
            'system_log_path' => __DIR__ . '/../storage/logs/chronicles_system.log',
        ],
    ],

    'queue' => [
        'redis' => [
            'queue_name' => 'chronicles:queue',
            'dlq_name' => 'chronicles:dlq',
        ],
    ],
    
    'watchers' => [
        'http' => true,
        'sql' => true,
        'exceptions' => true,
        'custom' => true,
    ],

    'sanitizer' => [
        'mask' => [
            'password', 'password_confirmation', 'token', 'secret',
            'authorization', 'x-api-key', 'x-secret-key', 'credit_card', 'cvv',
        ],
    ],

    'payload_limiter' => [
        'max_kb_size' => 64,
    ],

    'factories' => [
        'event'   => Horus\Chronicles\Factories\EventFactory::class,
        'queue'   => Horus\Chronicles\Factories\QueueFactory::class,
        'storage' => Horus\Chronicles\Factories\StorageFactory::class,
    ],
];