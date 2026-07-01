<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HelpersRequestBodyTest extends TestCase
{
    public function testEmptyInputReturnsEmptyArray(): void
    {
        // php://input is empty in CLI/PHPUnit context → json_decode returns null → []
        $result = request_body();
        $this->assertSame([], $result);
    }

    // Note: the valid-JSON path (json_decode returns an array) is not unit-testable
    // without a custom stream wrapper for php://input. It is exercised by the API
    // endpoints in integration (AdminCheckinTest mirrors the handler logic).
}
