<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class GeocoderTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../src/Geocoder.php';
        $this->cacheFile = sys_get_temp_dir() . '/geocode_test_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testEmptyAddressReturnsNullCoords(): void
    {
        $result = geocode('', $this->cacheFile);
        $this->assertSame(['lat' => null, 'lon' => null], $result);
    }

    public function testWhitespaceOnlyAddressReturnsNullCoords(): void
    {
        // iCal address made only of whitespace lines after unescaping
        $result = geocode('\\n   \\n', $this->cacheFile);
        $this->assertSame(['lat' => null, 'lon' => null], $result);
    }

    public function testCacheHitReturnsCachedCoords(): void
    {
        $address  = '1 rue de la Paix, Paris';
        $expected = ['lat' => 48.8698, 'lon' => 2.3316];
        file_put_contents($this->cacheFile, json_encode([$address => $expected]));

        $result = geocode($address, $this->cacheFile);
        $this->assertSame($expected, $result);
    }

    public function testCacheKeyIsRawAddress(): void
    {
        // Cache is keyed by the original raw iCal string, not the parsed version.
        $raw      = 'Salle des fêtes\\n12\\, rue de la Paix\\nParis';
        $expected = ['lat' => 48.8698, 'lon' => 2.3316];
        file_put_contents($this->cacheFile, json_encode([$raw => $expected]));

        $result = geocode($raw, $this->cacheFile);
        $this->assertSame($expected, $result);
    }

    public function testIcalCommaEscapeIsUnescaped(): void
    {
        // \, in iCal becomes a literal comma; cache stores the parsed candidate
        // so a cache written with the raw key is returned without HTTP.
        $raw = 'Gymnase Saint-Exupéry\\n12\\, avenue du Général\\nLyon';
        $expected = ['lat' => 45.75, 'lon' => 4.85];
        file_put_contents($this->cacheFile, json_encode([$raw => $expected]));

        $this->assertSame($expected, geocode($raw, $this->cacheFile));
    }

    public function testCacheMissWritesResultToFile(): void
    {
        // An address that won't be geocoded (no network in CI) — result is null coords,
        // but it must be persisted so the next call hits the cache.
        $address = '__nonexistent_address_xyz__';
        geocode($address, $this->cacheFile);

        $this->assertFileExists($this->cacheFile);
        $cache = json_decode(file_get_contents($this->cacheFile), true);
        $this->assertArrayHasKey($address, $cache);
        $this->assertArrayHasKey('lat', $cache[$address]);
        $this->assertArrayHasKey('lon', $cache[$address]);
    }
}
