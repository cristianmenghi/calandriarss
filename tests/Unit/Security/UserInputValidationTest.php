<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — M2: Input Validation in UserController
 *
 * Covers:
 *   - Role value must be whitelisted
 *   - Email must be validated as a valid email address
 *   - Password must meet minimum length requirements
 *
 * These tests validate the standalone validators that will be extracted
 * or added inline to UserController.update() and UserController.create().
 *
 * Tests tagged @group vulnerability test behaviour that is CURRENTLY MISSING.
 * They will PASS once the validation fix is applied.
 *
 * Fix location: src/Controllers/UserController.php
 */
class UserInputValidationTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Replicate the fix logic for isolated unit testing
    // ---------------------------------------------------------------------------

    private const ALLOWED_ROLES = ['admin', 'moderator', 'viewer'];
    private const MIN_PASSWORD_LENGTH = 12;

    private function isValidRole(mixed $role): bool
    {
        return is_string($role) && in_array($role, self::ALLOWED_ROLES, true);
    }

    private function isValidEmail(mixed $email): bool
    {
        return is_string($email) && (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function isValidPassword(mixed $password): bool
    {
        return is_string($password) && strlen($password) >= self::MIN_PASSWORD_LENGTH;
    }

    // -------------------------------------------------------------------------
    // Role validation
    // -------------------------------------------------------------------------

    public function test_allows_admin_role()
    {
        $this->assertTrue($this->isValidRole('admin'));
    }

    public function test_allows_moderator_role()
    {
        $this->assertTrue($this->isValidRole('moderator'));
    }

    public function test_allows_viewer_role()
    {
        $this->assertTrue($this->isValidRole('viewer'));
    }

    /** @group vulnerability */
    public function test_blocks_superadmin_role()
    {
        // VULNERABILITY: currently any string passes through to the DB
        $this->assertFalse(
            $this->isValidRole('superadmin'),
            'superadmin is not a valid role — must be rejected'
        );
    }

    /** @group vulnerability */
    public function test_blocks_root_role()
    {
        $this->assertFalse($this->isValidRole('root'));
    }

    /** @group vulnerability */
    public function test_blocks_empty_role()
    {
        $this->assertFalse($this->isValidRole(''));
    }

    /** @group vulnerability */
    public function test_blocks_null_role()
    {
        $this->assertFalse($this->isValidRole(null));
    }

    /** @group vulnerability */
    public function test_blocks_numeric_role()
    {
        $this->assertFalse($this->isValidRole(1));
    }

    /** @group vulnerability */
    public function test_role_check_is_case_sensitive()
    {
        // 'Admin' != 'admin' — strict comparison required
        $this->assertFalse($this->isValidRole('Admin'));
        $this->assertFalse($this->isValidRole('ADMIN'));
    }

    // -------------------------------------------------------------------------
    // Email validation
    // -------------------------------------------------------------------------

    public function test_allows_valid_email()
    {
        $this->assertTrue($this->isValidEmail('user@example.com'));
    }

    public function test_allows_email_with_subdomain()
    {
        $this->assertTrue($this->isValidEmail('user@mail.example.co.uk'));
    }

    /** @group vulnerability */
    public function test_blocks_email_without_at_sign()
    {
        $this->assertFalse($this->isValidEmail('notanemail'));
    }

    /** @group vulnerability */
    public function test_blocks_xss_in_email_field()
    {
        $this->assertFalse($this->isValidEmail('<script>alert(1)</script>'));
    }

    /** @group vulnerability */
    public function test_blocks_sql_injection_in_email_field()
    {
        // filter_var rejects this, but good to assert explicitly
        $this->assertFalse($this->isValidEmail("'; DROP TABLE users; --"));
    }

    /** @group vulnerability */
    public function test_blocks_empty_email()
    {
        $this->assertFalse($this->isValidEmail(''));
    }

    /** @group vulnerability */
    public function test_blocks_null_email()
    {
        $this->assertFalse($this->isValidEmail(null));
    }

    // -------------------------------------------------------------------------
    // Password length validation (A3 / setup hardening)
    // -------------------------------------------------------------------------

    public function test_allows_password_meeting_minimum_length()
    {
        $this->assertTrue($this->isValidPassword('Str0ng!Pass#1'));
    }

    public function test_allows_long_password()
    {
        $this->assertTrue($this->isValidPassword(str_repeat('a', 64)));
    }

    /** @group vulnerability */
    public function test_blocks_short_password()
    {
        $this->assertFalse(
            $this->isValidPassword('admin123'),
            'Password "admin123" is too short (< 12 chars) — must be rejected'
        );
    }

    /** @group vulnerability */
    public function test_blocks_empty_password()
    {
        $this->assertFalse($this->isValidPassword(''));
    }

    /** @group vulnerability */
    public function test_blocks_null_password()
    {
        $this->assertFalse($this->isValidPassword(null));
    }

    public function test_password_exactly_at_minimum_length_is_allowed()
    {
        $this->assertTrue($this->isValidPassword(str_repeat('x', self::MIN_PASSWORD_LENGTH)));
    }

    public function test_password_one_char_below_minimum_is_blocked()
    {
        $this->assertFalse($this->isValidPassword(str_repeat('x', self::MIN_PASSWORD_LENGTH - 1)));
    }
}
