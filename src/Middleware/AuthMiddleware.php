<?php

namespace App\Middleware;

use App\Models\User;

class AuthMiddleware
{
    private static $publicRoutes = [
        '/login',
        '/api/articles',
        '/api/sources',
        '/api/categories',
        '/',
        '/offline.html',
        '/manifest.json',
        '/sw.js'
    ];

    public static function handle()
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Allow public routes
        if (self::isPublicRoute($currentPath)) {
            return true;
        }

        // Check if user is authenticated
        if (!self::isAuthenticated()) {
            if (self::isApiRoute($currentPath)) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            } else {
                header('Location: /login?redirect=' . urlencode($currentPath));
                exit;
            }
        }

        // Check CSRF token for POST/PUT/DELETE requests (except login)
        // A1 FIX: /logout is no longer excluded — it requires a valid CSRF token
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) && $currentPath !== '/login') {
            self::validateCsrfToken();
        }

        // Update session activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    public static function requireRole($role)
    {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        return true;
    }

    public static function isAuthenticated()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout (2 hours default)
        $timeout = $_ENV['SESSION_LIFETIME'] ?? 7200;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
            return false;
        }

        return true;
    }

    public static function getCurrentUser()
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['user_role']
        ];
    }

    public static function login($user)
    {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        // Update last login
        User::updateLastLogin($user['id']);
    }

    public static function logout()
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    public static function generateCsrfToken(): string
    {
        // M3 FIX: rotate token every hour to limit exposure window
        $ttl = 3600;
        $now = time();
        if (!isset($_SESSION['csrf_token']) || ($now - ($_SESSION['csrf_token_ts'] ?? 0)) > $ttl) {
            $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_ts'] = $now;
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken()
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }

    private static function startSecureSession()
    {
        // M4 FIX: auto-detect HTTPS when SESSION_SECURE is not explicitly set
        $envSecure = $_ENV['SESSION_SECURE'] ?? '';
        if (strtolower((string)$envSecure) === 'true') {
            $secure = true;
        } elseif (strtolower((string)$envSecure) === 'false') {
            $secure = false;
        } else {
            // Auto-detect from current request
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        }
        $httponly = filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Strict'
        ]);

        session_name('CALANDRIA_SESSION');
        session_start();
    }

    private static function isPublicRoute($path)
    {
        // Exact match for specific public routes
        $exactRoutes = ['/login', '/', '/offline.html', '/manifest.json', '/sw.js'];
        if (in_array($path, $exactRoutes)) {
            return true;
        }
        
        // Prefix match for API routes
        $prefixRoutes = ['/api/articles', '/api/sources', '/api/categories'];
        foreach ($prefixRoutes as $route) {
            if (strpos($path, $route) === 0) {
                return true;
            }
        }
        
        // Allow static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/', $path)) {
            return true;
        }
        
        return false;
    }

    private static function isApiRoute($path)
    {
        return strpos($path, '/api/') === 0;
    }
}
