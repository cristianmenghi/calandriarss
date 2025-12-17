<?php

namespace App\Models;

use App\Utils\Database;
use PDO;

class Category
{
    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }
    
    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO categories (name, slug, description, icon, color, sort_order) 
                VALUES (:name, :slug, :description, :icon, :color, :sort_order)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'] ?? strtolower(str_replace(' ', '-', $data['name'])),
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':color' => $data['color'] ?? '#3b82f6',
            ':sort_order' => $data['sort_order'] ?? 0
        ]);
        
        return $db->lastInsertId();
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
        
        if (isset($data['slug'])) {
            $fields[] = "slug = :slug";
            $params[':slug'] = $data['slug'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['icon'])) {
            $fields[] = "icon = :icon";
            $params[':icon'] = $data['icon'];
        }
        
        if (isset($data['color'])) {
            $fields[] = "color = :color";
            $params[':color'] = $data['color'];
        }
        
        if (isset($data['sort_order'])) {
            $fields[] = "sort_order = :sort_order";
            $params[':sort_order'] = $data['sort_order'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public static function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public static function getCount()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public static function getArticleCount($id)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT COUNT(a.id) as count
            FROM articles a
            JOIN sources s ON a.source_id = s.id
            WHERE s.category = (SELECT slug FROM categories WHERE id = :id)
        ");
        
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
