<?php

namespace App\Tests\Application;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class Helpers
{
    /**
     * Helper to validate the JSON response body against an expected schema.
     * Used to be sure that the API returns no extra keys.
     */
    public static function validateBody(array $body, array $expectedSchema): void
    {
        $keys = array_keys($body);
        $expectedKeys = array_values($expectedSchema);
        sort($keys);
        sort($expectedKeys);

        WebTestCase::assertSame($expectedKeys, $keys);
    }

    public static function expectViolationsCount(array $body, int $expectedViolations): void
    {
        WebTestCase::assertArrayHasKey('violations', $body);
        WebTestCase::assertCount($expectedViolations, $body['violations']);
    }

    public static function expectViolationMessage(array $body, string $expectedViolationMessage): void
    {
        WebTestCase::assertArrayHasKey('detail', $body);
        WebTestCase::assertContains($expectedViolationMessage, preg_split('/\n/', $body['detail']));
    }
}
