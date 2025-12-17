<?php

namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;

class AuthController
{
    public function showLogin()
    {
        // If already logged in, redirect to admin
        if (AuthMiddleware::isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        view('login', [
            'csrf_token' => AuthMiddleware::generateCsrfToken(),
            'error' => $_SESSION['login_error'] ?? null,
            'redirect' => $_GET['redirect'] ?? '/admin'
        ]);
        
        unset($_SESSION['login_error']);
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $redirect = $_POST['redirect'] ?? '/admin';
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Check rate limiting
        if (!User::checkLoginAttempts($ipAddress)) {
            $_SESSION['login_error'] = 'Too many failed attempts. Please try again later.';
            header('Location: /login');
            exit;
        }

        // Authenticate
        $user = User::authenticate($username, $password);

        if (!$user) {
            User::logLoginAttempt($ipAddress, $username, false);
            $_SESSION['login_error'] = 'Invalid username or password.';
            header('Location: /login');
            exit;
        }

        // Success
        User::logLoginAttempt($ipAddress, $username, true);
        AuthMiddleware::login($user);

        header('Location: ' . $redirect);
        exit;
    }

    public function logout()
    {
        AuthMiddleware::logout();
        header('Location: /login');
        exit;
    }

    public function checkAuth()
    {
        header('Content-Type: application/json');
        
        if (AuthMiddleware::isAuthenticated()) {
            echo json_encode([
                'authenticated' => true,
                'user' => AuthMiddleware::getCurrentUser()
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
    }

    public function register()
    {
        // Only admins can register new users
        AuthMiddleware::requireRole('admin');

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'moderator';

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required']);
            return;
        }

        if (User::exists($username, $email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            return;
        }

        $currentUser = AuthMiddleware::getCurrentUser();
        
        $userId = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'created_by' => $currentUser['id']
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'user_id' => $userId
        ]);
    }
}
