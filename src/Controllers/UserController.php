<?php

namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;

class UserController
{
    private const ALLOWED_ROLES = ['admin', 'moderator', 'viewer'];
    private const MIN_PASSWORD_LENGTH = 12;

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
        
        // Required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username, email, and password are required']);
            return;
        }

        // M2 FIX: validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // M2 FIX: validate role against whitelist
        if (isset($data['role']) && !in_array($data['role'], self::ALLOWED_ROLES, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role. Allowed: ' . implode(', ', self::ALLOWED_ROLES)]);
            return;
        }

        // M2 FIX: enforce minimum password length
        if (strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters']);
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
            'id'      => $userId,
            'user'    => User::findById($userId)
        ]);
    }

    public function update($id)
    {
        AuthMiddleware::requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);

        // M2 FIX: validate email if provided
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // M2 FIX: validate role if provided
        if (isset($data['role']) && !in_array($data['role'], self::ALLOWED_ROLES, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role. Allowed: ' . implode(', ', self::ALLOWED_ROLES)]);
            return;
        }
        
        // Handle password update if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters']);
                return;
            }
            User::updatePassword($id, $data['password']);
        }
        unset($data['password']);
        
        $success = User::update($id, $data);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'user'    => User::findById($id)
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

        // M2 FIX: enforce minimum password length
        if (strlen($data['new_password']) < self::MIN_PASSWORD_LENGTH) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters']);
            return;
        }
        
        $success = User::updatePassword($id, $data['new_password']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
}
