<?php

$envs = [
    'APP_ENV'      => 'testing',
    'DB_DATABASE'  => 'alfahome_test',
    'SESSION_DRIVER' => 'array',
    'CACHE_STORE'  => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER'  => 'array',
    'BCRYPT_ROUNDS' => '4',
];

foreach ($envs as $k => $v) {
    putenv("{$k}={$v}");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}

require __DIR__ . '/../vendor/autoload.php';
