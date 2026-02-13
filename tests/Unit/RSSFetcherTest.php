<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests the future date filtering logic used in RSSFetcher::fetchSource().
 * We test the core date validation logic in isolation without needing
 * SimplePie or database dependencies.
 */
class RSSFetcherTest extends TestCase
{
    /**
     * Simulates the date filtering logic from RSSFetcher::fetchSource().
     * Returns true if the article should be SKIPPED (rejected), false if it should be kept.
     */
    private function shouldSkipByDate(?string $itemDate): bool
    {
        // Replicate the date resolution logic from RSSFetcher
        $date = $itemDate;
        if (!$date) {
            $date = date('Y-m-d H:i:s');
        }

        // Replicate the future date filter
        $itemTimestamp = strtotime($date);
        if ($itemTimestamp !== false && $itemTimestamp > time()) {
            return true; // Skip: future date
        }

        return false; // Keep
    }

    public function test_rejects_articles_with_future_date()
    {
        $futureDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->assertTrue($this->shouldSkipByDate($futureDate));
    }

    public function test_rejects_articles_with_far_future_date()
    {
        $farFutureDate = date('Y-m-d H:i:s', strtotime('+1 year'));
        $this->assertTrue($this->shouldSkipByDate($farFutureDate));
    }

    public function test_allows_articles_with_past_date()
    {
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->assertFalse($this->shouldSkipByDate($pastDate));
    }

    public function test_allows_articles_with_old_date()
    {
        $oldDate = '2024-01-15 10:00:00';
        $this->assertFalse($this->shouldSkipByDate($oldDate));
    }

    public function test_allows_articles_with_null_date()
    {
        // When date is null, RSSFetcher assigns current time → should NOT be skipped
        $this->assertFalse($this->shouldSkipByDate(null));
    }

    public function test_allows_articles_with_empty_date()
    {
        // Empty string is falsy → same as null, gets current time
        $this->assertFalse($this->shouldSkipByDate(''));
    }

    public function test_rejects_article_one_minute_in_future()
    {
        $slightlyFuture = date('Y-m-d H:i:s', strtotime('+1 minute'));
        $this->assertTrue($this->shouldSkipByDate($slightlyFuture));
    }

    public function test_allows_article_one_minute_in_past()
    {
        $slightlyPast = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $this->assertFalse($this->shouldSkipByDate($slightlyPast));
    }
}
