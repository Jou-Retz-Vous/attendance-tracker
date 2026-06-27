<?php
declare(strict_types=1);

$config  = require __DIR__ . '/../config.php';
$iconUrl = $config['icon_url'] ?? '/assets/icon.svg';

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

echo json_encode([
    'name'             => $config['association_name'],
    'short_name'       => $config['association_name'],
    'description'      => 'Système de pointage pour ' . $config['association_name'],
    'start_url'        => '/',
    'display'          => 'standalone',
    'background_color' => '#f8f9fa',
    'theme_color'      => '#ffffff',
    'icons'            => [
        ['src' => $iconUrl, 'sizes' => 'any', 'type' => mime_content_type_from_url($iconUrl)],
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

function mime_content_type_from_url(string $url): string
{
    return str_ends_with($url, '.png') ? 'image/png'
        : (str_ends_with($url, '.jpg') || str_ends_with($url, '.jpeg') ? 'image/jpeg'
        : (str_ends_with($url, '.webp') ? 'image/webp'
        : 'image/svg+xml'));
}
