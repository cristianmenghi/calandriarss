<?php

namespace App\Controllers;

use App\Models\Category;
use App\Middleware\AuthMiddleware;

class CategoryController
{
    public function index()
    {
        AuthMiddleware::handle();
        
        $categories = Category::all();
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $categories]);
    }

    public function show($id)
    {
        AuthMiddleware::handle();
        
        $category = Category::findById($id);
        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $category]);
    }

    public function create()
    {
        AuthMiddleware::requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            return;
        }
        
        $categoryId = Category::create($data);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $categoryId,
            'category' => Category::findById($categoryId)
        ]);
    }

    public function update($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $success = Category::update($id, $data);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'category' => Category::findById($id)
        ]);
    }

    public function delete($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $success = Category::delete($id);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
