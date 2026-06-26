<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CheckinServiceTest extends TestCase
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
        $m->setAccessible(true);
        $m->invoke(null, $this->pdo);

        $this->service = new CheckinService($this->pdo);
    }

    public function testCheckinCreatesAttendeeAndCheckin(): void
    {
        $this->service->checkin('session-1', 'Alice');

        $attendees = $this->pdo->query("SELECT COUNT(*) FROM attendees")->fetchColumn();
        $checkins  = $this->pdo->query("SELECT COUNT(*) FROM checkins")->fetchColumn();

        $this->assertSame('1', (string) $attendees);
        $this->assertSame('1', (string) $checkins);
    }

    public function testCheckinReusesExistingAttendee(): void
    {
        $this->service->checkin('session-1', 'Alice');
        $this->service->checkin('session-2', 'Alice');

        $attendees = $this->pdo->query("SELECT COUNT(*) FROM attendees")->fetchColumn();
        $this->assertSame('1', (string) $attendees);
    }

    public function testDuplicateCheckinThrows409(): void
    {
        $this->service->checkin('session-1', 'Alice');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(409);
        $this->service->checkin('session-1', 'Alice');
    }

    public function testCancelRemovesCheckin(): void
    {
        $this->service->checkin('session-1', 'Alice');
        $this->service->cancel('session-1', 'Alice');

        $checkins = $this->pdo->query("SELECT COUNT(*) FROM checkins")->fetchColumn();
        $this->assertSame('0', (string) $checkins);
    }

    public function testCancelNonExistentCheckinThrows404(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(404);
        $this->service->cancel('session-1', 'Nobody');
    }

    public function testCheckinDifferentSessionsSameAttendee(): void
    {
        $this->service->checkin('session-1', 'Alice');
        $this->service->checkin('session-2', 'Alice');

        $checkins = $this->pdo->query("SELECT COUNT(*) FROM checkins")->fetchColumn();
        $this->assertSame('2', (string) $checkins);
    }
}
