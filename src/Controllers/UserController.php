<?php

namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;

class UserController
{
    public function index()
    {
        AuthMiddleware::requireRole('admin');
        
        $users = User::all();
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $users]);
    }

    public function show($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $user]);
    }

    public function create()
    {
        AuthMiddleware::requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username, email, and password are required']);
            return;
        }
        
        if (User::exists($data['username'], $data['email'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            return;
        }
        
        $currentUser = AuthMiddleware::getCurrentUser();
        $data['created_by'] = $currentUser['id'];
        
        $userId = User::create($data);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $userId,
            'user' => User::findById($userId)
        ]);
    }

    public function update($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Handle password update if provided
        if (!empty($data['password'])) {
            User::updatePassword($id, $data['password']);
        }
        unset($data['password']);
        
        $success = User::update($id, $data);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'user' => User::findById($id)
        ]);
    }

    public function delete($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $currentUser = AuthMiddleware::getCurrentUser();
        
        // Prevent self-deletion
        if ($currentUser['id'] == $id) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            return;
        }
        
        $success = User::delete($id);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    public function changePassword($id)
    {
        AuthMiddleware::handle();
        
        $currentUser = AuthMiddleware::getCurrentUser();
        
        // Only admins or the user themselves can change password
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['new_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'New password is required']);
            return;
        }
        
        $success = User::updatePassword($id, $data['new_password']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
