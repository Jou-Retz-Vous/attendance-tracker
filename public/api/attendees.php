<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Database.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    json_ok(['attendees' => []]);
}

$stmt = Database::get()->prepare(
    "SELECT id, nickname FROM attendees WHERE nickname LIKE ? ORDER BY nickname LIMIT 10"
);
$stmt->execute(['%' . $q . '%']);

json_ok(['attendees' => $stmt->fetchAll()]);
