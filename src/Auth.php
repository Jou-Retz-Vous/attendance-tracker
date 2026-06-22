<?php
declare(strict_types=1);

class Auth
{
    public static function require(): void
    {
        $config = require __DIR__ . '/../config.php';
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            json_error('Unauthorized', 401);
        }

        $token = substr($header, 7);
        if (!hash_equals($config['admin_token'], $token)) {
            json_error('Unauthorized', 401);
        }
    }
}
