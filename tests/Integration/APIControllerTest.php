<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\APIController;
use App\Models\Article;
use App\Utils\Database;

class APIControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Re-use schema setup (Ideal functionality would be a Trait)
         $db = Database::getInstance()->getConnection();
        
        $db->exec("CREATE TABLE IF NOT EXISTS sources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            logo_url VARCHAR(255),
            category VARCHAR(50)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source_id INTEGER,
            title VARCHAR(255) NOT NULL,
            url VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            content TEXT,
            image_url VARCHAR(255),
            author VARCHAR(100),
            guid VARCHAR(255),
            hash VARCHAR(32) UNIQUE,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_visible BOOLEAN DEFAULT 1,
            FOREIGN KEY (source_id) REFERENCES sources(id)
        )");
        
        $db->exec("INSERT INTO sources (name, url, category) VALUES ('API Test Source', 'http://apitest.com', 'Tech')");
        
        // Create an article
        Article::create([
            'source_id' => 1,
            'title' => 'API Article',
            'url' => 'http://apitest.com/1',
            'description' => 'Desc',
            'content' => 'Content',
            'published_at' => date('Y-m-d H:i:s'),
            'hash' => md5('api_hash'),
             'image_url' => 'img.jpg',
            'author' => 'Me',
            'guid' => '123'
        ]);
        
        // Mock $_GET
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $db = Database::getInstance()->getConnection();
        $db->exec("DROP TABLE IF EXISTS articles");
        $db->exec("DROP TABLE IF EXISTS sources");
    }

    public function test_getArticles_returns_json()
    {
        $controller = new APIController();
        
        ob_start();
        $controller->getArticles();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('API Article', $data['data'][0]['title']);
    }
}
