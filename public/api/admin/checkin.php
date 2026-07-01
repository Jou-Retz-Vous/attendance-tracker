<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/helpers.php';
require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/CheckinService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body       = request_body();
$sessionUid = trim($body['session_uid'] ?? '');
$nickname   = trim($body['nickname']    ?? '');

if (!$sessionUid || !$nickname) {
    json_error('session_uid and nickname are required');
}

try {
    (new CheckinService(Database::get()))->checkin($sessionUid, $nickname);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), $e->getCode() ?: 500);
}

$stmt = Database::get()->prepare('
    SELECT c.id, c.created_at, a.nickname
    FROM checkins c
    JOIN attendees a ON a.id = c.attendee_id
    WHERE c.session_uid = ? AND a.nickname = ?
');
$stmt->execute([$sessionUid, $nickname]);

json_ok(['checkin' => $stmt->fetch()]);
