<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private function migrate(PDO $pdo): void
    {
        $m = new ReflectionMethod(Database::class, 'migrate');
        $m->setAccessible(true);
        $m->invoke(null, $pdo);
    }

    private function pdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys=ON');
        return $pdo;
    }

    public function testInitialMigrationCreatesTablesAtVersion1(): void
    {
        $pdo = $this->pdo();
        $this->migrate($pdo);

        $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
        $this->assertSame(1, $version);

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('attendees', $tables);
        $this->assertContains('checkins', $tables);
    }

    public function testMigrationIsIdempotent(): void
    {
        $pdo = $this->pdo();
        $this->migrate($pdo);
        $this->migrate($pdo); // second call must not throw

        $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
        $this->assertSame(1, $version);
    }

    public function testMigrationSkipsAlreadyAppliedBlocks(): void
    {
        $pdo = $this->pdo();
        $this->migrate($pdo);

        // Simulate a database that already has data — a re-run must not wipe it
        $pdo->exec("INSERT INTO attendees (id, nickname) VALUES ('abc', 'Alice')");
        $this->migrate($pdo);

        $count = (int) $pdo->query("SELECT COUNT(*) FROM attendees")->fetchColumn();
        $this->assertSame(1, $count);
    }
}
