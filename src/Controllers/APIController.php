<?php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Source;
use App\Models\Category;

class APIController
{
    public function getArticles()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'source_id' => $_GET['source_id'] ?? null,
            'category' => $_GET['category'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $articles = Article::paginate($page, 20, $filters);
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $articles]);
    }

    public function getSources()
    {
        $sources = Source::all();
        header('Content-Type: application/json');
        echo json_encode(['data' => $sources]);
    }

    public function getCategories()
    {
        $categories = Category::all();
        header('Content-Type: application/json');
        echo json_encode(['data' => $categories]);
    }
}
