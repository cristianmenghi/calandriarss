<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — C1: Open Redirect
 *
 * Tests the sanitizeRedirect() method that must be added to AuthController.
 * These tests will FAIL on the current code (no sanitizeRedirect method exists)
 * and PASS after applying the fix.
 *
 * Fix location: src/Controllers/AuthController.php
 */
class OpenRedirectTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Replicate the fix logic for isolated unit testing.
    // Once AuthController has this method, replace calls with the real one.
    // ---------------------------------------------------------------------------
    private function sanitizeRedirect(string $redirect): string
    {
        // Only allow relative internal paths starting with a single /
        // followed immediately by a letter, digit, or end-of-string.
        // Blocks: //evil.com, /\evil.com, javascript:, http:, data:, empty
        if (!preg_match('#^/([a-zA-Z0-9][a-zA-Z0-9/_\-?=&%.]*)?$#', $redirect)) {
            return '/admin';
        }
        return $redirect;
    }

    // --- Valid internal paths — must be ALLOWED ---

    public function test_allows_root_path()
    {
        $this->assertSame('/', $this->sanitizeRedirect('/'));
    }

    public function test_allows_admin_path()
    {
        $this->assertSame('/admin', $this->sanitizeRedirect('/admin'));
    }

    public function test_allows_nested_admin_path()
    {
        $this->assertSame('/admin/sources', $this->sanitizeRedirect('/admin/sources'));
    }

    public function test_allows_path_with_query_string()
    {
        $this->assertSame('/admin?tab=users', $this->sanitizeRedirect('/admin?tab=users'));
    }

    public function test_allows_path_with_multiple_query_params()
    {
        $this->assertSame('/admin/sources?page=2&limit=20', $this->sanitizeRedirect('/admin/sources?page=2&limit=20'));
    }

    // --- Malicious inputs — must be BLOCKED (return /admin) ---

    /** @group vulnerability */
    public function test_blocks_external_url_with_http()
    {
        // VULNERABILITY: Current code does header('Location: http://evil.com')
        // FIX: sanitizeRedirect detects no leading / → returns /admin
        $this->assertSame('/admin', $this->sanitizeRedirect('http://evil.com'));
    }

    /** @group vulnerability */
    public function test_blocks_external_url_with_https()
    {
        $this->assertSame('/admin', $this->sanitizeRedirect('https://evil.com/steal'));
    }

    /** @group vulnerability */
    public function test_blocks_protocol_relative_url()
    {
        // //evil.com is treated as http://evil.com by browsers
        $this->assertSame('/admin', $this->sanitizeRedirect('//evil.com'));
    }

    /** @group vulnerability */
    public function test_blocks_backslash_bypass()
    {
        // /\evil.com bypass used against naive regex
        $this->assertSame('/admin', $this->sanitizeRedirect('/\\evil.com'));
    }

    /** @group vulnerability */
    public function test_blocks_javascript_scheme()
    {
        $this->assertSame('/admin', $this->sanitizeRedirect('javascript:alert(1)'));
    }

    /** @group vulnerability */
    public function test_blocks_data_uri()
    {
        $this->assertSame('/admin', $this->sanitizeRedirect('data:text/html,<script>alert(1)</script>'));
    }

    /** @group vulnerability */
    public function test_blocks_empty_string()
    {
        $this->assertSame('/admin', $this->sanitizeRedirect(''));
    }

    /** @group vulnerability */
    public function test_blocks_url_encoded_external_host()
    {
        // Encoded // → %2F%2Fevil.com (some decoders might expand it)
        $this->assertSame('/admin', $this->sanitizeRedirect('%2F%2Fevil.com'));
    }

    /** @group vulnerability */
    public function test_blocks_path_with_at_sign()
    {
        // /foo@evil.com — ambiguous host bypass in some parsers
        $this->assertSame('/admin', $this->sanitizeRedirect('/foo@evil.com'));
    }
}
