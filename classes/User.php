<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function register($username, $email, $password) {
        if ($this->userExists($username, $email)) {
            return false;
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $params = [$username, $email, $passwordHash];
        
        try {
            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            return false;
        }
    }
    
    public function login($usernameOrEmail, $password) {
        $sql = "SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?";
        $user = $this->db->fetch($sql, [$usernameOrEmail, $usernameOrEmail]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $sessionId = $this->createSession($user['id']);
            if ($sessionId) {
                return [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'session_id' => $sessionId
                ];
            }
        }
        
        return false;
    }
    
    public function logout($sessionId) {
        $sql = "DELETE FROM user_sessions WHERE id = ?";
        return $this->db->query($sql, [$sessionId]);
    }
    
    public function validateSession($sessionId) {
        $sql = "SELECT u.id, u.username, u.email, u.role 
                FROM users u 
                JOIN user_sessions s ON u.id = s.user_id 
                WHERE s.id = ? AND s.expires_at > NOW()";
        
        $user = $this->db->fetch($sql, [$sessionId]);
        
        if ($user) {
            $this->extendSession($sessionId);
            return $user;
        }
        
        return false;
    }
    
    public function getUserById($userId) {
        $sql = "SELECT id, username, email, role, created_at FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$userId]);
    }
    
    public function isAdmin($userId) {
        try {
            $sql = "SELECT role FROM users WHERE id = ?";
            $user = $this->db->fetch($sql, [$userId]);
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            // Si erreur (colonne n'existe pas), pas d'admin
            return false;
        }
    }
    
    public function isUserAdmin($username) {
        try {
            $sql = "SELECT role FROM users WHERE username = ?";
            $user = $this->db->fetch($sql, [$username]);
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            // Si erreur (colonne n'existe pas), pas d'admin
            return false;
        }
    }
    
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $user = $this->db->fetch($sql, [$username, $email]);
        return $user !== false;
    }
    
    private function createSession($userId) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql = "INSERT INTO user_sessions (id, user_id, expires_at) VALUES (?, ?, ?)";
        $params = [$sessionId, $userId, $expiresAt];
        
        try {
            $this->db->query($sql, $params);
            return $sessionId;
        } catch (PDOException $e) {
            error_log("Session creation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function extendSession($sessionId) {
        $newExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $sql = "UPDATE user_sessions SET expires_at = ? WHERE id = ?";
        $this->db->query($sql, [$newExpiresAt, $sessionId]);
    }
    
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        return $this->db->query($sql);
    }
}
?>