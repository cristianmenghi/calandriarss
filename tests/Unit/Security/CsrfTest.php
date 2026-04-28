<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthMiddleware;

/**
 * Security Test — A1: CSRF Token Validation
 *
 * Covers:
 *   - A1: CSRF bypass on logout (logout currently excluded from check)
 *   - M3: CSRF token rotation / TTL
 *
 * Tests tagged @group vulnerability will FAIL on current code.
 * They will PASS once the fixes are applied.
 *
 * Fix location: src/Middleware/AuthMiddleware.php
 */
class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Start a clean session for each test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // -------------------------------------------------------------------------
    // Token generation
    // -------------------------------------------------------------------------

    public function test_generates_csrf_token_with_sufficient_entropy()
    {
        $token = AuthMiddleware::generateCsrfToken();
        // 32 random bytes → 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_same_token_returned_in_same_session()
    {
        $token1 = AuthMiddleware::generateCsrfToken();
        $token2 = AuthMiddleware::generateCsrfToken();
        $this->assertSame($token1, $token2);
    }

    public function test_token_stored_in_session()
    {
        $token = AuthMiddleware::generateCsrfToken();
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertSame($token, $_SESSION['csrf_token']);
    }

    // -------------------------------------------------------------------------
    // Token TTL rotation (M3 fix)
    // -------------------------------------------------------------------------

    /**
     * @group vulnerability
     * After the M3 fix, a token older than TTL must be regenerated.
     * BEFORE fix: token never expires → same token returned indefinitely.
     * AFTER fix:  expired token → new token generated.
     */
    public function test_token_is_rotated_after_ttl_expires()
    {
        // Simulate a token generated 2 hours ago
        $_SESSION['csrf_token'] = 'old_token_value_aaaa';
        $_SESSION['csrf_token_ts'] = time() - 7201; // > 1 hour TTL

        $newToken = AuthMiddleware::generateCsrfToken();

        $this->assertNotSame('old_token_value_aaaa', $newToken,
            'CSRF token should be rotated after TTL expires — fix not yet applied?');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $newToken);
    }

    public function test_token_is_not_rotated_before_ttl_expires()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_ts'] = time() - 100; // well within TTL

        $sameToken = AuthMiddleware::generateCsrfToken();
        $this->assertSame($_SESSION['csrf_token'], $sameToken);
    }

    // -------------------------------------------------------------------------
    // Token validation logic (isolated)
    // -------------------------------------------------------------------------

    /**
     * Replicates the validateCsrfToken check logic for unit testing
     * without needing a real HTTP request.
     */
    private function isValidCsrfToken(string $sessionToken, ?string $submitted): bool
    {
        if (!$submitted || !$sessionToken) {
            return false;
        }
        return hash_equals($sessionToken, $submitted);
    }

    public function test_valid_token_passes_check()
    {
        $token = bin2hex(random_bytes(32));
        $this->assertTrue($this->isValidCsrfToken($token, $token));
    }

    public function test_wrong_token_fails_check()
    {
        $real  = bin2hex(random_bytes(32));
        $wrong = bin2hex(random_bytes(32));
        $this->assertFalse($this->isValidCsrfToken($real, $wrong));
    }

    public function test_null_submitted_token_fails()
    {
        $real = bin2hex(random_bytes(32));
        $this->assertFalse($this->isValidCsrfToken($real, null));
    }

    public function test_empty_submitted_token_fails()
    {
        $real = bin2hex(random_bytes(32));
        $this->assertFalse($this->isValidCsrfToken($real, ''));
    }

    // -------------------------------------------------------------------------
    // A1: Logout route CSRF protection (AuthMiddleware routing logic)
    // -------------------------------------------------------------------------

    /**
     * Verifies that the list of paths excluded from CSRF validation
     * does NOT include /logout after the fix.
     *
     * @group vulnerability
     * BEFORE fix: /logout is in the exclusion list → CSRF bypass possible.
     * AFTER fix:  /logout requires a valid CSRF token.
     */
    public function test_logout_route_is_not_excluded_from_csrf()
    {
        // We inspect the AuthMiddleware source to confirm /logout is not
        // bypassed. This is a static-analysis-style test.
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Middleware/AuthMiddleware.php'
        );

        // After the fix, the condition should NOT contain "!== '/logout'"
        $this->assertStringNotContainsString(
            "\$currentPath !== '/logout'",
            $source,
            'VULNERABILITY ACTIVE: /logout is still excluded from CSRF check. Apply fix in AuthMiddleware.php:47'
        );
    }

    /**
     * Positive confirmation: /login should still be excluded (it has no session yet).
     */
    public function test_login_route_is_still_excluded_from_csrf()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Middleware/AuthMiddleware.php'
        );
        $this->assertStringContainsString(
            "\$currentPath !== '/login'",
            $source,
            '/login must remain excluded from CSRF check (no session exists yet)'
        );
    }
}
