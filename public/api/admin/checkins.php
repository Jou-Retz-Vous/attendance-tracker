<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';
require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/Auth.php';

Auth::require();

$sessionUid = trim($_GET['session_uid'] ?? '');

if (!$sessionUid) {
    json_error('session_uid is required');
}

$stmt = Database::get()->prepare("
    SELECT
        c.id,
        c.created_at,
        a.nickname,
        p.nickname AS checked_in_by
    FROM checkins c
    JOIN attendees a ON a.id = c.attendee_id
    LEFT JOIN attendees p ON p.id = c.checked_in_by
    WHERE c.session_uid = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$sessionUid]);

json_ok(['checkins' => $stmt->fetchAll()]);
