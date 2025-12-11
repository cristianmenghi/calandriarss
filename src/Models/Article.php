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
        
        if (!empty($filters['category'])) {
            // Requires joining sources or having category in articles. 
            // Simplified: assuming category filter checks sources.
            // For now, let's just allow simple string match if we denormalized or join
            // But let's stick to what we have. If we want to filter by category, we need a JOIN.
             $where[] = "source_id IN (SELECT id FROM sources WHERE category = :category)";
             $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
             $where[] = "(MATCH(title, description) AGAINST(:search IN NATURAL LANGUAGE MODE))";
             $params[':search'] = $filters['search'];
        }

        $whereSql = implode(' AND ', $where);
        
        $sql = "SELECT articles.*, sources.name as source_name, sources.logo_url as source_logo 
                FROM articles 
                JOIN sources ON articles.source_id = sources.id 
                WHERE $whereSql 
                ORDER BY published_at DESC 
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
        $sql = "INSERT INTO articles (source_id, title, url, description, content, author, published_at, image_url, guid, hash) 
                VALUES (:source_id, :title, :url, :description, :content, :author, :published_at, :image_url, :guid, :hash)
                ON DUPLICATE KEY UPDATE updated_at = NOW()"; // Simple upsert handling
        
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
}
