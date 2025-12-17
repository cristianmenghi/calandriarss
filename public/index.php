<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\Router;
use App\Controllers\APIController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\SourceController;
use App\Controllers\CategoryController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Simple Template Engine (using native PHP)
function view($path, $data = []) {
    extract($data);
    require __DIR__ . '/../templates/' . $path . '.php';
}

$router = new Router();
$api = new APIController();
$auth = new AuthController();
$admin = new AdminController();
$sources = new SourceController();
$categories = new CategoryController();
$users = new UserController();

// ============================================================================
// PUBLIC API ROUTES
// ============================================================================
$router->get('/api/articles', [$api, 'getArticles']);
$router->get('/api/sources', [$api, 'getSources']);
$router->get('/api/categories', [$api, 'getCategories']);

// ============================================================================
// AUTH ROUTES
// ============================================================================
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/api/auth/check', [$auth, 'checkAuth']);

// ============================================================================
// ADMIN PANEL ROUTES (Protected)
// ============================================================================
$router->get('/admin', [$admin, 'dashboard']);
$router->get('/admin/sources', [$admin, 'sources']);
$router->get('/admin/categories', [$admin, 'categories']);
$router->get('/admin/users', [$admin, 'users']);
$router->get('/admin/logs', [$admin, 'logs']);
$router->get('/admin/settings', [$admin, 'settings']);

// ============================================================================
// ADMIN API ROUTES - Sources
// ============================================================================
$router->get('/api/admin/sources', [$sources, 'index']);
$router->post('/api/admin/sources', [$sources, 'create']);
$router->put('/api/admin/sources/{id}', [$sources, 'update']);
$router->delete('/api/admin/sources/{id}', [$sources, 'delete']);
$router->post('/api/admin/sources/test', [$sources, 'testFeed']);
$router->get('/api/admin/sources/{id}/stats', [$sources, 'stats']);

// ============================================================================
// ADMIN API ROUTES - Categories
// ============================================================================
$router->get('/api/admin/categories', [$categories, 'index']);
$router->post('/api/admin/categories', [$categories, 'create']);
$router->put('/api/admin/categories/{id}', [$categories, 'update']);
$router->delete('/api/admin/categories/{id}', [$categories, 'delete']);

// ============================================================================
// ADMIN API ROUTES - Users
// ============================================================================
$router->get('/api/admin/users', [$users, 'index']);
$router->post('/api/admin/users', [$users, 'create']);
$router->put('/api/admin/users/{id}', [$users, 'update']);
$router->delete('/api/admin/users/{id}', [$users, 'delete']);
$router->post('/api/admin/users/{id}/password', [$users, 'changePassword']);

// ============================================================================
// WEB ROUTES
// ============================================================================
$router->get('/', function() {
    view('home');
});

// ============================================================================
// EXECUTE MIDDLEWARE AND RESOLVE ROUTE
// ============================================================================
// Execute authentication middleware (checks session, CSRF, etc.)
AuthMiddleware::handle();

// Resolve route
$router->resolve();
