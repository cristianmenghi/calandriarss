<?php

namespace App\Models;

use App\Utils\Database;
use PDO;

class Article
{
    public static function paginate($page = 1, $limit = 20, $filters = [])
    {
        $db = Database::getInstance()->getConnection();
        $offset = ($page - 1) * $limit;
        
        $where = ["is_visible = 1"];
        $params = [];
        
        if (!empty($filters['source_id'])) {
            $where[] = "source_id = :source_id";
            $params[':source_id'] = $filters['source_id'];
        }
        
        if (!empty($filters['category_id'])) {
            $where[] = "source_id IN (SELECT id FROM sources WHERE category = (SELECT slug FROM categories WHERE id = :category_id))";
            $params[':category_id'] = $filters['category_id'];
        } else if (!empty($filters['category'])) {
            $where[] = "source_id IN (SELECT id FROM sources WHERE category = :category)";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
             if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                 $where[] = "(title LIKE :search OR description LIKE :search)";
                 $params[':search'] = '%' . $filters['search'] . '%';
             } else {
                 $where[] = "(MATCH(title, description) AGAINST(:search IN NATURAL LANGUAGE MODE))";
                 $params[':search'] = $filters['search'];
             }
        }

        $whereSql = implode(' AND ', $where);
        
        $sql = "SELECT articles.*, sources.name as source_name, sources.logo_url as source_logo 
                FROM articles 
                JOIN sources ON articles.source_id = sources.id 
                WHERE $whereSql 
                ORDER BY published_at DESC, created_at DESC, id DESC 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
             $sql = "INSERT INTO articles (source_id, title, url, description, content, author, published_at, image_url, guid, hash) 
                VALUES (:source_id, :title, :url, :description, :content, :author, :published_at, :image_url, :guid, :hash)
                ON CONFLICT(hash) DO UPDATE SET updated_at = DATETIME('now')";
        } else {
             $sql = "INSERT INTO articles (source_id, title, url, description, content, author, published_at, image_url, guid, hash) 
                VALUES (:source_id, :title, :url, :description, :content, :author, :published_at, :image_url, :guid, :hash)
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':source_id' => $data['source_id'],
            ':title' => $data['title'],
            ':url' => $data['url'],
            ':description' => $data['description'] ?? null,
            ':content' => $data['content'] ?? null,
            ':author' => $data['author'] ?? null,
            ':published_at' => $data['published_at'],
            ':image_url' => $data['image_url'] ?? null,
            ':guid' => $data['guid'] ?? null,
            ':hash' => $data['hash']
        ]);
        
        return $db->lastInsertId();
    }
    
    public static function exists($hash) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM articles WHERE hash = :hash");
        $stmt->execute([':hash' => $hash]);
        return $stmt->fetchColumn();
    }
    
    public static function getCount()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM articles WHERE is_visible = 1");
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public static function getRecent($limit = 10)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT a.*, s.name as source_name 
                FROM articles a 
                JOIN sources s ON a.source_id = s.id 
                WHERE a.is_visible = 1 
                ORDER BY a.published_at DESC 
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public static function getTimeSeries($days = 30)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM articles
                WHERE created_at > DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public static function getTrending($limit = 10)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT a.*, s.name as source_name, COUNT(av.id) as view_count
                FROM articles a
                JOIN sources s ON a.source_id = s.id
                LEFT JOIN article_views av ON a.id = av.article_id
                WHERE a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY a.id
                ORDER BY view_count DESC
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
