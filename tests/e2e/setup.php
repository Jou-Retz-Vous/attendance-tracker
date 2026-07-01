<?php
declare(strict_types=1);

$rootDir  = realpath(__DIR__ . '/../../');
$distDir  = rtrim(realpath($argv[1] ?? "$rootDir/dist"), '/');
$icsPath  = realpath(__DIR__ . '/../fixtures/demo.ics');
$dbPath   = "$distDir/data/demo.db";
$cachePath = "$distDir/cache/demo.ics.cache";

foreach (['data', 'cache'] as $dir) {
    is_dir("$distDir/$dir") || mkdir("$distDir/$dir", 0755, true);
}

// Pre-populate the ICS cache so Calendar never makes a network call
copy($icsPath, $cachePath);

// Propagate optional keys from the real config
$rootConfig  = is_file("$rootDir/config.php") ? (require "$rootDir/config.php") : [];
$optionalKeys = ['icon_url', 'custom_css_url', 'site_url', 'nav_links'];
$extraLines  = '';
foreach ($optionalKeys as $key) {
    if (!array_key_exists($key, $rootConfig)) continue;
    $value = $rootConfig[$key];
    // Copy local assets to dist/www/assets/
    if ($key === 'icon_url' && is_string($value) && str_starts_with($value, '/')) {
        $src = "$rootDir/public$value";
        if (is_file($src)) copy($src, "$distDir/www/assets/" . basename($value));
    }
    $extraLines .= "\n    " . var_export($key, true) . ' => ' . var_export($value, true) . ',';
}

$configContent = <<<PHP
<?php
return [
    'association_name'     => 'Association Démo',
    'db_dsn'               => 'sqlite:$dbPath',
    'cache_path'           => '$cachePath',
    'calendar_url'         => 'file://$icsPath',
    'session_label_format' => '{date:EEEE d MMMM yyyy}',
    'show_location'        => false,$extraLines
];
PHP;
file_put_contents("$distDir/config.php", $configContent);

// Bootstrap classes without going through Database::get() (which reads config.php from src/../)
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Database.php';

$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys=ON');

$migrate = new ReflectionMethod(Database::class, 'migrate');
$migrate->invoke(null, $pdo);

// Find the two most recent Mondays and Wednesdays within the last 30 days
$now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$days = [];
for ($i = 0; $i <= 30; $i++) {
    $d = $now->modify("-$i days");
    $dow = (int) $d->format('N');
    if ($dow === 1 || $dow === 3) {
        $days[] = $d;
    }
    if (count($days) >= 4) break;
}

$sessions = array_map(
    fn($d) => 'sps-demo-weekly_' . $d->format('Ymd'),
    $days
);

// Clear previous demo data to ensure fresh, predictable state on every run
$pdo->exec('DELETE FROM checkins');
$pdo->exec('DELETE FROM attendees');

// Demo attendees
$attendees = ['Alice Martin', 'Bob Dupont', 'Claire Bernard', 'David Petit', 'Emma Leroy'];
foreach ($attendees as $name) {
    $pdo->prepare('INSERT OR IGNORE INTO attendees (id, nickname) VALUES (?, ?)')->execute([uuid4(), $name]);
}
$rows = $pdo->query('SELECT id FROM attendees')->fetchAll(PDO::FETCH_COLUMN);

// Populate the two most recent sessions with checkins.
// created_at reflects when the check-in was recorded: most members check in on the
// session day, but the admin may add someone retroactively (J+1 or J+2).
$sessionData = [
    // session 0: 3 attendees, 2 on the day + 1 added the next day by admin
    [
        'count'   => 3,
        'offsets' => [0, 0, 1],
        'times'   => ['19:03', '19:19', '11:08'],
    ],
    // session 1 (the one shown by default in admin): 5 attendees, 3 on the day + 2 added later
    [
        'count'   => 5,
        'offsets' => [0, 0, 0, 1, 2],   // days after session date
        'times'   => ['19:04', '19:11', '19:27', '10:15', '09:42'],
    ],
];

foreach (array_slice($sessions, 0, 2) as $i => $sessionUid) {
    $ymd  = substr($sessionUid, -8);
    $base = new DateTimeImmutable(
        substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2)
    );
    $data = $sessionData[$i];
    foreach (array_slice($rows, 0, $data['count']) as $j => $attendeeId) {
        $checkinDate = $base->modify("+{$data['offsets'][$j]} days")->format('Y-m-d');
        try {
            $pdo->prepare("INSERT INTO checkins (id, session_uid, attendee_id, created_at) VALUES (?, ?, ?, ?)")
                ->execute([uuid4(), $sessionUid, $attendeeId, "$checkinDate {$data['times'][$j]}:00"]);
        } catch (PDOException) {}
    }
}

echo "Demo environment ready in $distDir\n";
