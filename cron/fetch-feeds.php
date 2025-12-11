<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\RSSFetcher;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting RSS Fetch...\n";

$fetcher = new RSSFetcher();
$results = $fetcher->fetchAll();

foreach ($results as $source => $result) {
    if ($result['status'] === 'success') {
        echo "[$source] Fetched {$result['fetched']} new articles.\n";
    } else {
        echo "[$source] Error: {$result['message']}\n";
    }
}

echo "Done.\n";
