<?php

namespace App\Models;

use App\Utils\Database;
use PDO;

class Category
{
    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }
}
