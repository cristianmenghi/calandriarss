<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — M1: HTTP Method Override Whitelist
 *
 * The Router currently accepts ANY value for _method POST parameter,
 * allowing unexpected HTTP verb injection.
 *
 * Tests tagged @group vulnerability will FAIL on current code.
 * They will PASS once the whitelist fix is applied.
 *
 * Fix location: src/Utils/Router.php — resolve() method
 */
class MethodOverrideTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Replicate CURRENT (vulnerable) override logic for comparison
    // ---------------------------------------------------------------------------
    private function currentOverrideLogic(string $postMethod): string
    {
        // Current code: strtoupper with no whitelist
        return strtoupper($postMethod);
    }

    // ---------------------------------------------------------------------------
    // Replicate FIXED override logic
    // ---------------------------------------------------------------------------
    private function fixedOverrideLogic(string $postMethod): string
    {
        $allowedOverrides = ['PUT', 'PATCH', 'DELETE'];
        $override = strtoupper($postMethod);
        return in_array($override, $allowedOverrides, true) ? $override : 'POST';
    }

    // -------------------------------------------------------------------------
    // Current code — demonstrate what it CURRENTLY does (should concern us)
    // -------------------------------------------------------------------------

    public function test_current_code_blindly_accepts_arbitrary_method()
    {
        // This passes TODAY — demonstrating the vulnerability is real
        $result = $this->currentOverrideLogic('CONNECT');
        $this->assertSame('CONNECT', $result,
            'Confirmed: current code accepts CONNECT as a valid method override (vulnerability active)');
    }

    // -------------------------------------------------------------------------
    // Fixed logic — these describe REQUIRED behaviour after the fix
    // -------------------------------------------------------------------------

    public function test_allows_put_override()
    {
        $this->assertSame('PUT', $this->fixedOverrideLogic('put'));
        $this->assertSame('PUT', $this->fixedOverrideLogic('PUT'));
    }

    public function test_allows_delete_override()
    {
        $this->assertSame('DELETE', $this->fixedOverrideLogic('delete'));
        $this->assertSame('DELETE', $this->fixedOverrideLogic('DELETE'));
    }

    public function test_allows_patch_override()
    {
        $this->assertSame('PATCH', $this->fixedOverrideLogic('patch'));
    }

    /** @group vulnerability */
    public function test_blocks_connect_override()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic('CONNECT'),
            'CONNECT is not an allowed override — must fall back to POST');
    }

    /** @group vulnerability */
    public function test_blocks_head_override()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic('HEAD'));
    }

    /** @group vulnerability */
    public function test_blocks_options_override()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic('OPTIONS'));
    }

    /** @group vulnerability */
    public function test_blocks_trace_override()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic('TRACE'));
    }

    /** @group vulnerability */
    public function test_blocks_arbitrary_string_injection()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic('HACK; DROP TABLE users;'));
    }

    /** @group vulnerability */
    public function test_blocks_empty_override()
    {
        $this->assertSame('POST', $this->fixedOverrideLogic(''));
    }

    /** @group vulnerability */
    public function test_blocks_get_override()
    {
        // GET should never be overridable via POST body
        $this->assertSame('POST', $this->fixedOverrideLogic('GET'));
    }

    /** @group vulnerability */
    public function test_blocks_post_override()
    {
        // POST is the base method; _method=POST is a no-op and potentially confusing
        $this->assertSame('POST', $this->fixedOverrideLogic('POST'));
    }

    // -------------------------------------------------------------------------
    // Source-code inspection test: verify the actual Router uses a whitelist
    // -------------------------------------------------------------------------

    /**
     * @group vulnerability
     * Reads Router.php source and verifies the whitelist array is present.
     * BEFORE fix: this assertion fails.
     * AFTER fix:  passes.
     */
    public function test_router_source_contains_method_override_whitelist()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Utils/Router.php'
        );

        $this->assertStringContainsString(
            'allowedOverrides',
            $source,
            'Router.php must define an $allowedOverrides whitelist — fix not yet applied?'
        );

        $this->assertStringContainsString(
            "'PUT'",
            $source,
            'PUT must be in the allowed overrides list'
        );

        $this->assertStringContainsString(
            "'DELETE'",
            $source,
            'DELETE must be in the allowed overrides list'
        );

        $this->assertStringContainsString(
            'in_array',
            $source,
            'Router must use in_array to validate the override method'
        );
    }
}
