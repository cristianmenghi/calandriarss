#!/usr/bin/env php
<?php

/**
 * Calandria RSS - Setup Script
 * 
 * This script automates the initial setup of the application:
 * - Checks PHP version and extensions
 * - Creates .env file from .env.example
 * - Creates database and runs migrations
 * - Creates default admin user
 */

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                                                           ║\n";
echo "║   Calandria RSS - Setup Script                           ║\n";
echo "║   Terminal-style News Aggregator                         ║\n";
echo "║                                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check PHP version
echo "> Checking PHP version... ";
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌\n";
    echo "  ERROR: PHP 8.0 or higher is required. You have " . PHP_VERSION . "\n";
    exit(1);
}
echo "✓ " . PHP_VERSION . "\n";

// Check required extensions
echo "> Checking required PHP extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'simplexml', 'mbstring', 'json'];
$missing = [];

foreach ($required_extensions as $ext) {
    echo "  - $ext... ";
    if (extension_loaded($ext)) {
        echo "✓\n";
    } else {
        echo "❌\n";
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    echo "\n  ERROR: Missing required extensions: " . implode(', ', $missing) . "\n";
    exit(1);
}

// Check if .env exists
echo "\n> Checking environment configuration...\n";
if (!file_exists(__DIR__ . '/.env')) {
    echo "  .env file not found. Creating from .env.example... ";
    if (copy(__DIR__ . '/.env.example', __DIR__ . '/.env')) {
        echo "✓\n";
        echo "  ⚠️  Please edit .env file with your database credentials\n";
    } else {
        echo "❌\n";
        echo "  ERROR: Could not create .env file\n";
        exit(1);
    }
} else {
    echo "  .env file exists ✓\n";
}

// Load environment
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database setup
echo "\n> Database Setup\n";
echo "  Database: " . $_ENV['DB_DATABASE'] . "\n";
echo "  Host: " . $_ENV['DB_HOST'] . ":" . $_ENV['DB_PORT'] . "\n";

echo "\n  Do you want to create the database and run migrations? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) === 'y') {
    try {
        // Connect to MySQL without database
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database
        echo "\n  Creating database... ";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_ENV['DB_DATABASE']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓\n";

        // Connect to the new database
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Run schema
        echo "  Running schema.sql... ";
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schema);
        echo "✓\n";

        // Run migrations
        echo "  Running migrations.sql... ";
        $migrations = file_get_contents(__DIR__ . '/database/migrations.sql');
        $pdo->exec($migrations);
        echo "✓\n";

        echo "\n  ✅ Database setup completed successfully!\n";

    } catch (PDOException $e) {
        echo "❌\n";
        echo "  ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "  Skipping database setup.\n";
}

// Check if admin user exists
echo "\n> Checking admin user...\n";
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "  Admin user exists ✓\n";
        echo "  Default credentials: admin / admin123\n";
        echo "  ⚠️  Please change the password after first login!\n";
    } else {
        echo "  No admin user found. Creating default admin... ";
        
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@calandria.local', $passwordHash, 'admin', 1]);
        
        echo "✓\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "  ⚠️  Please change the password after first login!\n";
    }
} catch (PDOException $e) {
    echo "  ⚠️  Could not check admin user: " . $e->getMessage() . "\n";
}

// Final instructions
echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                                                           ║\n";
echo "║   Setup Complete! 🎉                                     ║\n";
echo "║                                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Next steps:\n";
echo "\n";
echo "1. Start the development server:\n";
echo "   php -S localhost:8000 -t public/\n";
echo "\n";
echo "2. Access the application:\n";
echo "   Frontend: http://localhost:8000\n";
echo "   Admin:    http://localhost:8000/admin\n";
echo "\n";
echo "3. Set up cron job for RSS fetching:\n";
echo "   */15 * * * * cd " . __DIR__ . " && php cron/fetch-feeds.php\n";
echo "\n";
echo "4. Add some RSS sources via admin panel\n";
echo "\n";
echo "For more information, see README.md\n";
echo "\n";
