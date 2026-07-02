<?php
declare(strict_types=1);

$config  = require __DIR__ . '/../config.php';
$iconUrl = $config['icon_url'] ?? '/assets/icon.svg';

$supportedLangs = ['fr', 'en'];
preg_match('/^([a-z]{2})/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', $m);
$lang = in_array(strtolower($m[1] ?? 'fr'), $supportedLangs, true) ? strtolower($m[1]) : 'fr';
if (isset($_COOKIE['jrv_lang']) && in_array($_COOKIE['jrv_lang'], $supportedLangs, true)) {
    $lang = $_COOKIE['jrv_lang'];
}
$t = require __DIR__ . '/../lang/' . $lang . '.php';

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

echo json_encode([
    'name'             => $config['association_name'],
    'short_name'       => $config['association_name'],
    'description'      => str_replace('{name}', $config['association_name'], $t['manifest_description']),
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
