<?php
declare(strict_types=1);

function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40); // version 4
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80); // variant 1 (RFC 4122)
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function json_ok(mixed $data = []): never
{
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, ...(array) $data]);
    exit;
}

function json_error(string $message, int $status = 400): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function request_body(): array
{
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : []; // invalid or empty JSON → treat as empty payload
}
