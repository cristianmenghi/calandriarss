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
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function create()
    {
        AuthMiddleware::handle();
        
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
    }

    public function update($id)
    {
        AuthMiddleware::handle();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
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

    private function testFeedUrl($url)
    {
        try {
            $feed = new \SimplePie\SimplePie();
            $feed->set_feed_url($url);
            $feed->enable_cache(false);
            $feed->init();
            
            return !$feed->error();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function stats($id)
    {
        AuthMiddleware::handle();
        
        $stats = Source::getStats($id);
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
}
