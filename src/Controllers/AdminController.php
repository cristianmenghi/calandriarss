<?php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use App\Models\User;
use App\Middleware\AuthMiddleware;

class AdminController
{
    public function dashboard()
    {
        AuthMiddleware::handle();
        
        $stats = [
            'total_articles' => Article::getCount(),
            'total_sources' => Source::getCount(),
            'total_categories' => Category::getCount(),
            'total_users' => User::getCount(),
            'recent_articles' => Article::getRecent(10),
            'top_sources' => Source::getTopSources(5),
            'time_series' => Article::getTimeSeries(30)
        ];
        
        view('admin/dashboard', [
            'stats' => $stats,
            'user' => AuthMiddleware::getCurrentUser(),
            'csrf_token' => AuthMiddleware::generateCsrfToken()
        ]);
    }

    public function sources()
    {
        AuthMiddleware::handle();
        
        view('admin/sources', [
            'user' => AuthMiddleware::getCurrentUser(),
            'csrf_token' => AuthMiddleware::generateCsrfToken()
        ]);
    }

    public function categories()
    {
        AuthMiddleware::handle();
        
        view('admin/categories', [
            'user' => AuthMiddleware::getCurrentUser(),
            'csrf_token' => AuthMiddleware::generateCsrfToken()
        ]);
    }

    public function users()
    {
        AuthMiddleware::requireRole('admin');
        
        view('admin/users', [
            'user' => AuthMiddleware::getCurrentUser(),
            'csrf_token' => AuthMiddleware::generateCsrfToken()
        ]);
    }
}
