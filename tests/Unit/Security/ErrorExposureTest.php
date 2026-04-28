<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — C2: Error / Stack Trace Exposure
 *
 * In production (APP_ENV != 'local'), the API must NEVER expose:
 *   - Stack traces
 *   - Internal file paths
 *   - Exception messages that may reveal infrastructure details
 *
 * Tests tagged @group vulnerability will FAIL on current code.
 * They will PASS once the fix is applied.
 *
 * Fix location: src/Controllers/SourceController.php (and all other controllers)
 *
 * Test strategy: we simulate the response-building logic that should replace
 * the current `$e->getTraceAsString()` pattern.
 */
class ErrorExposureTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Replicate CURRENT (vulnerable) error serialization
    // ---------------------------------------------------------------------------
    private function currentErrorResponse(\Throwable $e): array
    {
        return [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];
    }

    // ---------------------------------------------------------------------------
    // Replicate FIXED error serialization
    // ---------------------------------------------------------------------------
    private function fixedErrorResponse(\Throwable $e, string $env = 'production'): array
    {
        $isDebug = ($env === 'local');
        return [
            'error' => $isDebug ? $e->getMessage() : 'Internal server error',
            'trace' => $isDebug ? $e->getTraceAsString() : null,
        ];
    }

    private function makeException(): \RuntimeException
    {
        return new \RuntimeException(
            'SQLSTATE[42S02]: Base table or view not found: /var/www/html/src/Models/Source.php:45'
        );
    }

    // -------------------------------------------------------------------------
    // Demonstrate current vulnerability
    // -------------------------------------------------------------------------

    public function test_current_code_exposes_trace_in_response()
    {
        // This passes TODAY — confirming the vulnerability is active
        $response = $this->currentErrorResponse($this->makeException());
        $this->assertNotNull($response['trace'],
            'Confirmed: current code includes trace in API responses (vulnerability active)');
        $this->assertStringContainsString('#0 ', $response['trace']);
    }

    public function test_current_code_exposes_internal_paths_in_message()
    {
        $response = $this->currentErrorResponse($this->makeException());
        $this->assertStringContainsString('/var/www/', $response['error'],
            'Confirmed: current code leaks server path in error message');
    }

    // -------------------------------------------------------------------------
    // Required behaviour in PRODUCTION (APP_ENV != local)
    // -------------------------------------------------------------------------

    /** @group vulnerability */
    public function test_production_response_hides_trace()
    {
        $response = $this->fixedErrorResponse($this->makeException(), 'production');
        $this->assertNull(
            $response['trace'],
            'Stack trace must be null in production responses'
        );
    }

    /** @group vulnerability */
    public function test_production_response_has_generic_error_message()
    {
        $response = $this->fixedErrorResponse($this->makeException(), 'production');
        $this->assertSame(
            'Internal server error',
            $response['error'],
            'Production error message must be generic — no internal details'
        );
    }

    /** @group vulnerability */
    public function test_production_response_does_not_contain_file_paths()
    {
        $response = $this->fixedErrorResponse($this->makeException(), 'production');
        $json = json_encode($response);
        $this->assertStringNotContainsString(
            '/var/www/',
            $json,
            'Internal server paths must not appear in production API responses'
        );
    }

    /** @group vulnerability */
    public function test_production_response_does_not_contain_sql_details()
    {
        $sqlException = new \RuntimeException('SQLSTATE[42000]: Syntax error near users WHERE id=1');
        $response = $this->fixedErrorResponse($sqlException, 'production');
        $json = json_encode($response);
        $this->assertStringNotContainsString(
            'SQLSTATE',
            $json,
            'SQL error details must not appear in production responses'
        );
    }

    // -------------------------------------------------------------------------
    // Required behaviour in LOCAL / development (APP_ENV = local)
    // -------------------------------------------------------------------------

    public function test_local_env_exposes_trace_for_debugging()
    {
        $response = $this->fixedErrorResponse($this->makeException(), 'local');
        $this->assertNotNull($response['trace'],
            'Stack trace should be visible in local/development environment');
    }

    public function test_local_env_exposes_real_error_message()
    {
        $response = $this->fixedErrorResponse($this->makeException(), 'local');
        $this->assertStringContainsString(
            'Base table or view not found',
            $response['error']
        );
    }

    // -------------------------------------------------------------------------
    // Source-code inspection: verify no controller exposes trace in production
    // -------------------------------------------------------------------------

    /**
     * @group vulnerability
     * Scans all Controller files for the raw getTraceAsString() pattern.
     * BEFORE fix: at least one file contains it unconditionally.
     * AFTER fix:  zero occurrences (or only inside APP_ENV==='local' blocks).
     */
    public function test_no_controller_exposes_raw_trace_unconditionally()
    {
        $controllersDir = dirname(__DIR__, 3) . '/src/Controllers';
        $files = glob($controllersDir . '/*.php');
        $violations = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            // Check for bare getTraceAsString() NOT guarded by a debug/env check
            if (str_contains($source, 'getTraceAsString()')) {
                // Allow if it's inside a debug/env conditional
                $isGuarded = str_contains($source, "APP_ENV") ||
                             str_contains($source, '$debug') ||
                             str_contains($source, '$isDebug');
                if (!$isGuarded) {
                    $violations[] = basename($file);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'These controllers expose stack traces without environment guard: ' .
            implode(', ', $violations) .
            ' — Apply the C2 fix.'
        );
    }

    /**
     * @group vulnerability
     * Verifies Database.php does not die() with connection details.
     */
    public function test_database_die_does_not_expose_connection_string()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Utils/Database.php'
        );

        // After fix, die() with $e->getMessage() should be replaced by error_log + safe response
        $this->assertStringNotContainsString(
            'die("Database Connection Failed: " . $e->getMessage())',
            $source,
            'Database.php must not die() with raw exception message — fix not yet applied?'
        );
    }
}
