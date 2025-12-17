<?php

namespace App\Models;

use App\Utils\Database;
use PDO;

class User
{
    public static function authenticate($username, $password)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = :username OR email = :email) AND is_active = 1");
        $stmt->execute([
            ':username' => $username,
            ':email' => $username
        ]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        return $user;
    }

    public static function create($data)
    {
        $db = Database::getInstance()->getConnection();
        
        $cost = $_ENV['BCRYPT_COST'] ?? 12;
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => $cost]);
        
        $sql = "INSERT INTO users (username, email, password_hash, role, is_active, created_by) 
                VALUES (:username, :email, :password_hash, :role, :is_active, :created_by)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $passwordHash,
            ':role' => $data['role'] ?? 'moderator',
            ':is_active' => $data['is_active'] ?? true,
            ':created_by' => $data['created_by'] ?? null
        ]);
        
        return $db->lastInsertId();
    }

    public static function all()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, username, email, role, is_active, last_login_at, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, email, role, is_active, last_login_at, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['username'])) {
            $fields[] = "username = :username";
            $params[':username'] = $data['username'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = "is_active = :is_active";
            $params[':is_active'] = $data['is_active'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function updatePassword($id, $newPassword)
    {
        $db = Database::getInstance()->getConnection();
        
        $cost = $_ENV['BCRYPT_COST'] ?? 12;
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $cost]);
        
        $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id
        ]);
    }

    public static function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function updateLastLogin($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function hasRole($userId, $role)
    {
        $user = self::findById($userId);
        return $user && ($user['role'] === $role || $user['role'] === 'admin');
    }

    public static function checkLoginAttempts($ipAddress)
    {
        $db = Database::getInstance()->getConnection();
        
        $maxAttempts = $_ENV['LOGIN_MAX_ATTEMPTS'] ?? 5;
        $lockoutTime = $_ENV['LOGIN_LOCKOUT_TIME'] ?? 900; // 15 minutes
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = :ip 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL :lockout SECOND)
        ");
        
        $stmt->execute([
            ':ip' => $ipAddress,
            ':lockout' => $lockoutTime
        ]);
        
        $result = $stmt->fetch();
        return $result['attempts'] < $maxAttempts;
    }

    public static function logLoginAttempt($ipAddress, $username, $success)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (:ip, :username, :success)");
        $stmt->execute([
            ':ip' => $ipAddress,
            ':username' => $username,
            ':success' => $success ? 1 : 0
        ]);
    }

    public static function exists($username, $email)
    {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    public static function getCount()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        return $result['count'];
    }
}
