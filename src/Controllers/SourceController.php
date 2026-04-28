<?php

namespace App\Controllers;

use App\Models\Source;
use App\Middleware\AuthMiddleware;

class SourceController
{
    public function index()
    {
        AuthMiddleware::handle();
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
            
            $sources = Source::paginate($page, $limit, $search);
            $total = Source::getCount($search);
            
            header('Content-Type: application/json');
            echo json_encode([
                'data' => $sources,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (\Throwable $e) {
            error_log('[CalandriaRSS] SourceController@index: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            $debug = ($_ENV['APP_ENV'] ?? 'production') === 'local';
            echo json_encode([
                'error' => $debug ? $e->getMessage() : 'Internal server error',
                'trace' => $debug ? $e->getTraceAsString() : null,
            ]);
        }
    }

    public function create()
    {
        AuthMiddleware::handle();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validation
            if (empty($data['name']) || empty($data['rss_feed_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and RSS feed URL are required']);
                return;
            }
            
            // Test feed before saving
            if (!$this->testFeedUrl($data['rss_feed_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid RSS feed URL']);
                return;
            }
            
            $sourceId = Source::create($data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $sourceId,
                'source' => Source::findById($sourceId)
            ]);
        } catch (\Throwable $e) {
            error_log('[CalandriaRSS] SourceController@create: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            $debug = ($_ENV['APP_ENV'] ?? 'production') === 'local';
            echo json_encode([
                'error' => $debug ? 'Failed to create source: ' . $e->getMessage() : 'Internal server error',
                'trace' => $debug ? $e->getTraceAsString() : null,
            ]);
        }
    }

    public function show($id)
    {
        AuthMiddleware::handle();
        
        try {
            $source = Source::findById($id);
            if (!$source) {
                http_response_code(404);
                echo json_encode(['error' => 'Source not found']);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['data' => $source]);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        AuthMiddleware::handle();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

            // Test feed if URL changed
            if (isset($data['rss_feed_url']) && !$this->testFeedUrl($data['rss_feed_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid RSS feed URL']);
                return;
            }
            
            $success = Source::update($id, $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'source' => Source::findById($id)
            ]);
        } catch (\Throwable $e) {
            error_log('[CalandriaRSS] SourceController@update: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            $debug = ($_ENV['APP_ENV'] ?? 'production') === 'local';
            echo json_encode([
                'error' => $debug ? 'Failed to update source: ' . $e->getMessage() : 'Internal server error',
                'trace' => $debug ? $e->getTraceAsString() : null,
            ]);
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $success = Source::delete($id);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    public function testFeed()
    {
        AuthMiddleware::handle();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $url = $data['url'] ?? '';
        
        $isValid = $this->testFeedUrl($url);
        
        header('Content-Type: application/json');
        echo json_encode(['valid' => $isValid]);
    }

    /**
     * A2 FIX — SSRF prevention.
     * Only allows http/https URLs resolving to public (non-private) IPs.
     */
    private function testFeedUrl(string $url): bool
    {
        // 1. Only allow http / https schemes
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $parsed = parse_url($url);
        $host   = $parsed['host'] ?? '';
        if (empty($host)) {
            return false;
        }

        // 2. Resolve hostname and block private / reserved IP ranges
        $ip = gethostbyname($host);
        if ($this->isPrivateIp($ip)) {
            return false;
        }

        try {
            $feed = new \SimplePie\SimplePie();
            $feed->set_feed_url($url);
            $feed->enable_cache(false);
            $feed->set_timeout(10);
            $feed->init();

            return !$feed->error();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns true if the IP is in a private / reserved range.
     * Uses PHP's built-in FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE.
     */
    private function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    public function stats($id)
    {
        AuthMiddleware::handle();

        $stats = Source::getStats($id);

        header('Content-Type: application/json');
        echo json_encode($stats);
    }
}
