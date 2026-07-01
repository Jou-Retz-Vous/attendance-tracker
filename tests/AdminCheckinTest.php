<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests the admin checkin handler logic: input validation + service integration.
 * The HTTP layer (headers, redirect) is thin infrastructure shared with the delete handler,
 * which is why we test the logic directly rather than via HTTP.
 */
class AdminCheckinTest extends TestCase
{
    private PDO $pdo;
    private CheckinService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys=ON');
        $m = new ReflectionMethod(Database::class, 'migrate');
        $m->invoke(null, $this->pdo);
        $this->service = new CheckinService($this->pdo);
    }

    public function testValidCheckinCreatesEntry(): void
    {
        $result = $this->handleCheckin('sess-1', 'Alice');

        $this->assertNull($result['error']);
        $this->assertStringContainsString('checkin_added=Alice', $result['redirect']);
        $count = $this->pdo->query('SELECT COUNT(*) FROM checkins')->fetchColumn();
        $this->assertSame(1, (int) $count);
    }

    public function testDuplicateCheckinReturnsAlreadyError(): void
    {
        $this->service->checkin('sess-1', 'Alice');

        $result = $this->handleCheckin('sess-1', 'Alice');

        $this->assertSame('already', $result['error']);
        $count = $this->pdo->query('SELECT COUNT(*) FROM checkins')->fetchColumn();
        $this->assertSame(1, (int) $count);
    }

    public function testEmptyNicknameIsRejected(): void
    {
        $result = $this->handleCheckin('sess-1', '');

        $this->assertSame('empty', $result['error']);
        $count = $this->pdo->query('SELECT COUNT(*) FROM checkins')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    public function testEmptySessionUidIsRejected(): void
    {
        $result = $this->handleCheckin('', 'Alice');

        $this->assertSame('empty', $result['error']);
        $count = $this->pdo->query('SELECT COUNT(*) FROM checkins')->fetchColumn();
        $this->assertSame(0, (int) $count);
    }

    /** Mirrors the POST handler logic in public/admin/index.php */
    private function handleCheckin(string $sessionUid, string $nickname): array
    {
        $nickname   = trim($nickname);
        $sessionUid = trim($sessionUid);

        if (!$nickname || !$sessionUid) {
            return ['error' => 'empty', 'redirect' => '/admin/?session_uid=' . urlencode($sessionUid) . '&checkin_error=empty'];
        }

        try {
            $this->service->checkin($sessionUid, $nickname);
            return ['error' => null, 'redirect' => '/admin/?session_uid=' . urlencode($sessionUid) . '&checkin_added=' . urlencode($nickname)];
        } catch (RuntimeException $e) {
            $errKey = $e->getCode() === 409 ? 'already' : 'generic';
            return ['error' => $errKey, 'redirect' => '/admin/?session_uid=' . urlencode($sessionUid) . '&checkin_error=' . $errKey];
        }
    }
}
