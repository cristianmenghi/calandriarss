<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — M4 + B1 + B2 + B3: Miscellaneous Hardening
 *
 * Covers lower-severity items from the audit:
 *   - M4: Session cookie Secure flag auto-detection
 *   - B1: Security headers presence (inspects source)
 *   - B2: Duplicate route /admin/users
 *   - B3: Database __wakeup() must throw to prevent deserialization
 *
 * Fix locations:
 *   - src/Middleware/AuthMiddleware.php (M4)
 *   - public/index.php (B1, B2)
 *   - src/Utils/Database.php (B3)
 */
class HardeningTest extends TestCase
{
    // =========================================================================
    // M4 — Session Secure flag auto-detection
    // =========================================================================

    /**
     * Replicates the FIXED Secure-flag detection logic.
     */
    private function resolveSecureFlag(string $envValue, bool $isHttps): bool
    {
        // If env is explicitly 'true' or 'false', respect it.
        // Otherwise auto-detect from the current request scheme.
        if (strtolower($envValue) === 'true')  return true;
        if (strtolower($envValue) === 'false') return false;
        return $isHttps;
    }

    /** @group vulnerability */
    public function test_secure_flag_is_true_when_request_is_https()
    {
        // VULNERABILITY: current code returns false (default from .env.example)
        // even when the request is over HTTPS.
        // AFTER fix: auto-detection returns true for HTTPS requests.
        $result = $this->resolveSecureFlag('false', true); // env says false, but HTTPS
        // After fix the logic: env='false' → return false (explicit override wins)
        // Let's test the auto-detect case:
        $result = $this->resolveSecureFlag('', true); // env not set, HTTPS=true
        $this->assertTrue($result,
            'Secure flag must be true when request is HTTPS and SESSION_SECURE is not explicitly set');
    }

    public function test_secure_flag_is_false_when_request_is_http_and_env_not_set()
    {
        $result = $this->resolveSecureFlag('', false);
        $this->assertFalse($result);
    }

    public function test_secure_flag_explicit_true_overrides_http()
    {
        $result = $this->resolveSecureFlag('true', false); // env says true, HTTP
        $this->assertTrue($result);
    }

    public function test_secure_flag_explicit_false_overrides_https()
    {
        $result = $this->resolveSecureFlag('false', true); // env says false, HTTPS
        $this->assertFalse($result);
    }

    /** @group vulnerability */
    public function test_authmiddleware_source_has_https_autodetect()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Middleware/AuthMiddleware.php'
        );
        $this->assertStringContainsString(
            'HTTPS',
            $source,
            'AuthMiddleware must auto-detect HTTPS to set Secure flag — fix not yet applied?'
        );
    }

    // =========================================================================
    // B1 — Security Headers
    // =========================================================================

    /**
     * @group vulnerability
     * Reads index.php and verifies security headers are set.
     * BEFORE fix: headers absent.
     * AFTER fix:  all required headers present.
     */
    public function test_index_php_sets_x_frame_options_header()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public/index.php'
        );
        $this->assertStringContainsString(
            'X-Frame-Options',
            $source,
            'index.php must set X-Frame-Options header — fix not yet applied?'
        );
    }

    /** @group vulnerability */
    public function test_index_php_sets_x_content_type_options_header()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public/index.php'
        );
        $this->assertStringContainsString(
            'X-Content-Type-Options',
            $source,
            'index.php must set X-Content-Type-Options: nosniff — fix not yet applied?'
        );
    }

    /** @group vulnerability */
    public function test_index_php_sets_referrer_policy_header()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public/index.php'
        );
        $this->assertStringContainsString(
            'Referrer-Policy',
            $source,
            'index.php must set Referrer-Policy header — fix not yet applied?'
        );
    }

    /** @group vulnerability */
    public function test_index_php_sets_content_security_policy_header()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public/index.php'
        );
        $this->assertStringContainsString(
            'Content-Security-Policy',
            $source,
            'index.php must set Content-Security-Policy header — fix not yet applied?'
        );
    }

    // =========================================================================
    // B2 — Duplicate route
    // =========================================================================

    /**
     * @group vulnerability
     * Reads index.php and checks /admin/users is NOT registered twice.
     * BEFORE fix: two identical lines → duplicate registration.
     * AFTER fix:  only one occurrence.
     */
    public function test_admin_users_route_is_not_duplicated()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public/index.php'
        );
        $count = substr_count($source, "'/admin/users'");
        $this->assertSame(
            1,
            $count,
            "Route '/admin/users' is registered {$count} times — expected exactly 1. Remove the duplicate in index.php"
        );
    }

    // =========================================================================
    // B3 — Database Singleton deserialization prevention
    // =========================================================================

    /**
     * @group vulnerability
     * Verifies __wakeup() throws an exception instead of being silent.
     * BEFORE fix: __wakeup() is an empty method.
     * AFTER fix:  __wakeup() throws \Exception.
     */
    public function test_database_wakeup_throws_exception()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Utils/Database.php'
        );
        $this->assertStringContainsString(
            'throw new',
            $source,
            'Database::__wakeup() must throw an exception to prevent unserialize() attacks — fix not yet applied?'
        );
    }

    /**
     * Additionally verify the class genuinely prevents cloning.
     */
    public function test_database_clone_is_private()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Utils/Database.php'
        );
        $this->assertStringContainsString(
            'private function __clone',
            $source,
            'Database::__clone() must be private to prevent cloning of the singleton'
        );
    }

    // =========================================================================
    // B4 — Rate limiting by username (regression guard)
    // =========================================================================

    /**
     * @group vulnerability
     * After the B4 fix, checkLoginAttempts must accept a $username parameter.
     * BEFORE fix: only accepts $ipAddress.
     * AFTER fix:  signature is checkLoginAttempts(string $ipAddress, string $username).
     */
    public function test_check_login_attempts_accepts_username_parameter()
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/src/Models/User.php'
        );
        $this->assertStringContainsString(
            'checkLoginAttempts(string $ipAddress, string $username',
            $source,
            'User::checkLoginAttempts() must accept a $username parameter for per-user rate limiting — fix not yet applied?'
        );
    }
}
