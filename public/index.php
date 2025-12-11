<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\Router;
use App\Controllers\APIController;

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

// API Routes
$router->get('/api/articles', [$api, 'getArticles']);
$router->get('/api/sources', [$api, 'getSources']);
$router->get('/api/categories', [$api, 'getCategories']);

// Web Routes
$router->get('/', function() {
    view('home');
});

$router->resolve();
