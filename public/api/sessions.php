<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Calendar.php';

$config   = require __DIR__ . '/../../config.php';
$calendar = new Calendar($config['calendar_url'], $config['cache_path']);

try {
    json_ok([
        'association_name' => $config['association_name'],
        'sessions'         => $calendar->getSessions(),
    ]);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 503);
}
