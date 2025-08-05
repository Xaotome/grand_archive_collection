<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

function checkAuth() {
    $user = new User();
    
    $sessionId = $_SESSION['session_id'] ?? $_COOKIE['session_id'] ?? null;
    
    if ($sessionId) {
        $userData = $user->validateSession($sessionId);
        if ($userData) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['session_id'] = $sessionId;
            // Inclure le rôle dans les données de session si disponible
            if (isset($userData['role'])) {
                $_SESSION['role'] = $userData['role'];
            }
            return $userData;
        }
    }
    
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['session_id']);
    setcookie('session_id', '', time() - 3600, '/');
    
    return false;
}

function requireAuth() {
    if (!checkAuth()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function getCurrentUser() {
    return checkAuth();
}
?>