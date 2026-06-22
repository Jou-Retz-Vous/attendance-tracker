<?php
declare(strict_types=1);

class Calendar
{
    public function __construct(
        private string $url,
        private string $cachePath,
        private int    $cacheTtl = 900
    ) {}

    public function getSessions(): array
    {
        $ics    = $this->fetchIcs();
        $events = $this->parseIcs($ics);
        $now    = new DateTimeImmutable();

        $sessions = array_filter($events, fn($e) =>
            $e['start'] >= $now->modify('-30 days') &&
            $e['start'] <= $now->modify('+90 days')
        );

        usort($sessions, fn($a, $b) => $b['start'] <=> $a['start']);

        return array_values(array_map(fn($e) => [
            'uid'        => $e['uid'],
            'title'      => $e['title'],
            'start'      => $e['start']->format('c'),
            'end'        => $e['end']->format('c'),
            'is_current' => $now >= $e['start'] && $now <= $e['end'],
        ], $sessions));
    }

    private function fetchIcs(): string
    {
        $cacheValid = file_exists($this->cachePath)
            && time() - filemtime($this->cachePath) < $this->cacheTtl;

        if ($cacheValid) {
            return file_get_contents($this->cachePath);
        }

        $ics = @file_get_contents($this->url);

        if ($ics !== false) {
            file_put_contents($this->cachePath, $ics);
            return $ics;
        }

        if (file_exists($this->cachePath)) {
            return file_get_contents($this->cachePath);
        }

        throw new RuntimeException('Calendar unavailable and no cache found.');
    }

    private function parseIcs(string $ics): array
    {
        // Unfold multi-line values (RFC 5545 §3.1)
        $ics   = preg_replace('/\r?\n[ \t]/', '', $ics);
        $lines = preg_split('/\r?\n/', $ics);

        $events  = [];
        $current = null;

        foreach ($lines as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }
            if ($line === 'END:VEVENT') {
                if (isset($current['uid'], $current['title'], $current['start'], $current['end'])) {
                    $events[] = $current;
                }
                $current = null;
                continue;
            }
            if ($current === null) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) continue;

            $rawKey = strtoupper(substr($line, 0, $colonPos));
            $key    = explode(';', $rawKey)[0];

            match ($key) {
                'UID'     => $current['uid']   = trim(substr($line, $colonPos + 1)),
                'SUMMARY' => $current['title'] = trim(substr($line, $colonPos + 1)),
                'DTSTART' => $current['start'] = $this->parseDate($line),
                'DTEND'   => $current['end']   = $this->parseDate($line),
                default   => null,
            };
        }

        return $events;
    }

    private function parseDate(string $line): DateTimeImmutable
    {
        $colonPos = strrpos($line, ':');
        $params   = substr($line, 0, $colonPos);
        $value    = trim(substr($line, $colonPos + 1));

        $tz = new DateTimeZone('UTC');
        if (preg_match('/TZID=([^;:]+)/', $params, $m)) {
            try {
                $tz = new DateTimeZone($m[1]);
            } catch (\Exception) {}
        }

        if (strlen($value) === 8) {
            return DateTimeImmutable::createFromFormat('Ymd', $value, $tz)->setTime(0, 0);
        }

        if (str_ends_with($value, 'Z')) {
            return DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
        }

        return DateTimeImmutable::createFromFormat('Ymd\THis', $value, $tz);
    }
}
