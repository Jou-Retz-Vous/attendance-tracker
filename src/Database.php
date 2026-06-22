<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config.php';
            $pdo = new PDO('sqlite:' . $config['db_path']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
            self::migrate($pdo);
            self::$instance = $pdo;
        }
        return self::$instance;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendees (
                id       TEXT PRIMARY KEY,
                nickname TEXT NOT NULL UNIQUE
            );

            CREATE TABLE IF NOT EXISTS checkins (
                id             TEXT PRIMARY KEY,
                session_uid    TEXT NOT NULL,
                attendee_id    TEXT NOT NULL REFERENCES attendees(id),
                checked_in_by  TEXT REFERENCES attendees(id),
                created_at     TIMESTAMP NOT NULL
            );

            CREATE UNIQUE INDEX IF NOT EXISTS uq_checkin
                ON checkins (session_uid, attendee_id);
        ");
    }
}
