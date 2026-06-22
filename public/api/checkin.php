<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body       = request_body();
$sessionUid = trim($body['session_uid'] ?? '');
$nickname   = trim($body['nickname'] ?? '');
$byNickname = trim($body['checked_in_by_nickname'] ?? '');

if (!$sessionUid || !$nickname) {
    json_error('session_uid and nickname are required');
}

$db = Database::get();

function findOrCreateAttendee(PDO $db, string $nickname): string
{
    $stmt = $db->prepare('SELECT id FROM attendees WHERE nickname = ?');
    $stmt->execute([$nickname]);
    $row = $stmt->fetch();

    if ($row) {
        return $row['id'];
    }

    $id   = uuid4();
    $stmt = $db->prepare('INSERT INTO attendees (id, nickname) VALUES (?, ?)');
    $stmt->execute([$id, $nickname]);
    return $id;
}

$attendeeId  = findOrCreateAttendee($db, $nickname);
$checkedInBy = $byNickname ? findOrCreateAttendee($db, $byNickname) : null;

try {
    $stmt = $db->prepare("
        INSERT INTO checkins (id, session_uid, attendee_id, checked_in_by, created_at)
        VALUES (?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([uuid4(), $sessionUid, $attendeeId, $checkedInBy]);
} catch (PDOException $e) {
    // UNIQUE constraint on (session_uid, attendee_id)
    if (str_contains($e->getMessage(), 'UNIQUE')) {
        json_error('Already checked in for this session', 409);
    }
    json_error('Database error', 500);
}

json_ok(['nickname' => $nickname]);
