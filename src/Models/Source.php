<?php

namespace App\Models;

use App\Utils\Database;
use PDO;

class Source
{
    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM sources WHERE is_active = 1");
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        
        $category = $data['category'] ?? null;
        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $cat = Category::findById($data['category_id']);
            if ($cat) {
                $category = $cat['slug'];
            }
        }

        $sql = "INSERT INTO sources (name, website_url, rss_feed_url, category, logo_url, description) VALUES (:name, :website_url, :rss_feed_url, :category, :logo_url, :description)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':website_url' => $data['website_url'] ?? null,
            ':rss_feed_url' => $data['rss_feed_url'],
            ':category' => $category,
            ':logo_url' => $data['logo_url'] ?? null,
            ':description' => $data['description'] ?? null
        ]);
        return $db->lastInsertId();
    }
    
    public static function getDueForUpdate() {
        $db = Database::getInstance()->getConnection();
        // Fetch sources where last_fetched_at is null OR (now - last_fetched_at) > fetch_interval
        $sql = "SELECT * FROM sources WHERE is_active = 1 AND (last_fetched_at IS NULL OR TIMESTAMPDIFF(SECOND, last_fetched_at, NOW()) > fetch_interval)";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
    
    public static function updateLastFetched($id) {
         $db = Database::getInstance()->getConnection();
         $stmt = $db->prepare("UPDATE sources SET last_fetched_at = NOW() WHERE id = :id");
         $stmt->execute([':id' => $id]);
    }
    
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sources WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public static function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['website_url'])) {
            $fields[] = "website_url = :website_url";
            $params[':website_url'] = $data['website_url'];
        }
        
        if (isset($data['rss_feed_url'])) {
            $fields[] = "rss_feed_url = :rss_feed_url";
            $params[':rss_feed_url'] = $data['rss_feed_url'];
        }
        
        if (isset($data['category'])) {
            $fields[] = "category = :category";
            $params[':category'] = $data['category'];
        }

        if (isset($data['category_id'])) {
            $cat = Category::findById($data['category_id']);
            if ($cat) {
                 $fields[] = "category = :category_resolved";
                 $params[':category_resolved'] = $cat['slug'];
            }
        }
        
        if (isset($data['logo_url'])) {
            $fields[] = "logo_url = :logo_url";
            $params[':logo_url'] = $data['logo_url'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = "is_active = :is_active";
            $params[':is_active'] = $data['is_active'];
        }

        if (isset($data['fetch_interval'])) {
            $fields[] = "fetch_interval = :fetch_interval";
            $params[':fetch_interval'] = $data['fetch_interval'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE sources SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public static function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM sources WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public static function getCount($search = null)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM sources";
        $params = [];

        if ($search) {
            $sql .= " WHERE name LIKE :search1 OR rss_feed_url LIKE :search2 OR description LIKE :search3";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public static function paginate($page = 1, $limit = 20, $search = null)
    {
        $db = Database::getInstance()->getConnection();
        $offset = ($page - 1) * $limit;
        
        $whereSql = "";
        $params = [];

        if ($search) {
            $whereSql = " WHERE s.name LIKE :search1 OR s.rss_feed_url LIKE :search2 OR s.description LIKE :search3";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $sql = "SELECT s.*, COUNT(a.id) as article_count 
                FROM sources s 
                LEFT JOIN articles a ON s.id = a.source_id 
                $whereSql
                GROUP BY s.id 
                ORDER BY s.created_at DESC 
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
    
    public static function getStats($id)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(a.id) as total_articles,
                COUNT(CASE WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as articles_last_7d,
                COUNT(CASE WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as articles_last_30d,
                MAX(a.created_at) as last_article_date
            FROM articles a
            WHERE a.source_id = :id
        ");
        
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public static function getTopSources($limit = 5)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT s.*, COUNT(a.id) as article_count 
                FROM sources s 
                LEFT JOIN articles a ON s.id = a.source_id 
                WHERE a.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY s.id 
                ORDER BY article_count DESC 
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
