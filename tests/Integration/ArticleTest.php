<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Article;
use App\Utils\Database;

class ArticleTest extends TestCase
{
    protected function setUp(): void
    {
        // SQLite in-memory setup
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
        
        // Seed Source
        $db->exec("INSERT INTO sources (name, url, category) VALUES ('Test Source', 'http://test.com', 'General')");
    }

    protected function tearDown(): void
    {
         // Reset ID sequence or drop tables? with in-memory usually connection drop resets it? 
         // Singleton pattern in Database might keep connection alive.
         // Let's drop tables cleanly.
        $db = Database::getInstance()->getConnection();
        $db->exec("DROP TABLE IF EXISTS articles");
        $db->exec("DROP TABLE IF EXISTS sources");
    }

    public function test_it_creates_and_retrieves_articles()
    {
        $data = [
            'source_id' => 1,
            'title' => 'Test Article',
            'url' => 'http://test.com/1',
            'description' => 'Desc',
            'content' => 'Content',
            'published_at' => date('Y-m-d H:i:s'),
            'hash' => md5('unique_hash'),
            'image_url' => 'img.jpg',
            'author' => 'Me',
            'guid' => '123'
        ];

        $id = Article::create($data);
        $this->assertNotEmpty($id);

        $exists = Article::exists($data['hash']);
        $this->assertEquals($id, $exists);

        $articles = Article::paginate(1, 10);
        $this->assertCount(1, $articles);
        $this->assertEquals('Test Article', $articles[0]['title']);
    }
}
