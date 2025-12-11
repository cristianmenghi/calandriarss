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
        $sql = "INSERT INTO sources (name, website_url, rss_feed_url, category, logo_url, description) VALUES (:name, :website_url, :rss_feed_url, :category, :logo_url, :description)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':website_url' => $data['website_url'] ?? null,
            ':rss_feed_url' => $data['rss_feed_url'],
            ':category' => $data['category'] ?? null,
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
}
