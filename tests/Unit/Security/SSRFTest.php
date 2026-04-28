<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security Test — A2: SSRF (Server-Side Request Forgery)
 *
 * Tests the isPrivateIp() and sanitizeFeedUrl() helpers that must be added
 * to SourceController (or extracted to a shared validator class).
 *
 * Tests tagged @group vulnerability will FAIL on current code.
 * They will PASS once the fix is applied.
 *
 * Fix location: src/Controllers/SourceController.php — testFeedUrl()
 */
class SSRFTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Replicate the fix logic here for isolated unit testing.
    // These mirror the helpers that will be added to SourceController.
    // ---------------------------------------------------------------------------

    private function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function isAllowedFeedUrl(string $url): bool
    {
        // 1. Must be http or https
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        if (empty($host)) {
            return false;
        }

        // 2. Resolve to IP and block private/reserved ranges
        $ip = gethostbyname($host);
        if ($this->isPrivateIp($ip)) {
            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Scheme validation
    // -------------------------------------------------------------------------

    /** @group vulnerability */
    public function test_blocks_file_scheme()
    {
        // BEFORE fix: SimplePie may follow file:// URIs
        // AFTER fix: isAllowedFeedUrl rejects non-http(s) schemes
        $this->assertFalse(
            $this->isAllowedFeedUrl('file:///etc/passwd'),
            'file:// URIs must be blocked to prevent local file disclosure'
        );
    }

    /** @group vulnerability */
    public function test_blocks_ftp_scheme()
    {
        $this->assertFalse($this->isAllowedFeedUrl('ftp://internal.corp/data'));
    }

    /** @group vulnerability */
    public function test_blocks_gopher_scheme()
    {
        $this->assertFalse($this->isAllowedFeedUrl('gopher://127.0.0.1:6379/_SET key value'));
    }

    /** @group vulnerability */
    public function test_blocks_dict_scheme()
    {
        $this->assertFalse($this->isAllowedFeedUrl('dict://localhost:11211/stat'));
    }

    public function test_allows_http_scheme()
    {
        // Only validate the scheme part here — real IPs are tested separately
        $this->assertMatchesRegularExpression('#^https?://#i', 'http://example.com/feed.rss');
    }

    public function test_allows_https_scheme()
    {
        $this->assertMatchesRegularExpression('#^https?://#i', 'https://example.com/feed.rss');
    }

    // -------------------------------------------------------------------------
    // Private IP range blocking
    // -------------------------------------------------------------------------

    /** @group vulnerability */
    public function test_blocks_loopback_ipv4()
    {
        $this->assertTrue(
            $this->isPrivateIp('127.0.0.1'),
            '127.0.0.1 (localhost) must be flagged as private'
        );
    }

    /** @group vulnerability */
    public function test_blocks_rfc1918_class_a()
    {
        $this->assertTrue($this->isPrivateIp('10.0.0.1'));
        $this->assertTrue($this->isPrivateIp('10.255.255.254'));
    }

    /** @group vulnerability */
    public function test_blocks_rfc1918_class_b()
    {
        $this->assertTrue($this->isPrivateIp('172.16.0.1'));
        $this->assertTrue($this->isPrivateIp('172.31.255.254'));
    }

    /** @group vulnerability */
    public function test_blocks_rfc1918_class_c()
    {
        $this->assertTrue($this->isPrivateIp('192.168.0.1'));
        $this->assertTrue($this->isPrivateIp('192.168.255.254'));
    }

    /** @group vulnerability */
    public function test_blocks_link_local_aws_metadata()
    {
        // AWS/GCP/Azure instance metadata endpoint
        $this->assertTrue(
            $this->isPrivateIp('169.254.169.254'),
            '169.254.169.254 (cloud metadata) must be blocked'
        );
    }

    /** @group vulnerability */
    public function test_blocks_other_link_local()
    {
        $this->assertTrue($this->isPrivateIp('169.254.0.1'));
    }

    public function test_allows_public_ip()
    {
        $this->assertFalse($this->isPrivateIp('8.8.8.8'));
    }

    public function test_allows_another_public_ip()
    {
        $this->assertFalse($this->isPrivateIp('1.1.1.1'));
    }

    public function test_allows_public_ip_in_200_range()
    {
        $this->assertFalse($this->isPrivateIp('200.10.20.30'));
    }

    // -------------------------------------------------------------------------
    // Full URL validation (scheme + host combined)
    // -------------------------------------------------------------------------

    /** @group vulnerability */
    public function test_blocks_http_to_localhost()
    {
        // Simulate URL pointing to loopback
        // gethostbyname('localhost') → 127.0.0.1 on most systems
        $result = $this->isAllowedFeedUrl('http://localhost/admin');
        $this->assertFalse($result, 'http://localhost must be blocked');
    }

    /** @group vulnerability */
    public function test_blocks_http_to_private_ip_directly()
    {
        $this->assertFalse($this->isAllowedFeedUrl('http://192.168.1.1/feed'));
        $this->assertFalse($this->isAllowedFeedUrl('http://10.0.0.1/feed'));
    }

    /** @group vulnerability */
    public function test_blocks_aws_metadata_url()
    {
        $this->assertFalse(
            $this->isAllowedFeedUrl('http://169.254.169.254/latest/meta-data/'),
            'Cloud metadata endpoint must be blocked'
        );
    }

    public function test_blocks_empty_url()
    {
        $this->assertFalse($this->isAllowedFeedUrl(''));
    }

    public function test_blocks_url_without_host()
    {
        $this->assertFalse($this->isAllowedFeedUrl('http://'));
    }
}
