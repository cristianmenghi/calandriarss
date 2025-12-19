<?php

namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        try {
            if (($config['driver'] ?? 'mysql') === 'sqlite') {
                $dsn = "sqlite:{$config['database']}";
            } else {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            }
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        try {
            // Check if connection is still alive
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            // If connection is lost, try to reconnect
            $this->reconnect();
        }
        return $this->pdo;
    }

    public function reconnect()
    {
        $config = require __DIR__ . '/../../config/database.php';

        try {
            if (($config['driver'] ?? 'mysql') === 'sqlite') {
                $dsn = "sqlite:{$config['database']}";
            } else {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            }
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            die("Database Reconnection Failed: " . $e->getMessage());
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup() {}
}
