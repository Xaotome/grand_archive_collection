<?php
class Database {
    private $host = '127.0.0.1';
    private $database = 'u658240102_grand_archive';
    private $username = 'u658240102_grand_archive';
    private $password = 'Xaotome$123';
    private $charset = 'utf8mb4';
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // En cas d'erreur de connexion, essayer de créer la base de données
            try {
                $dsn_no_db = "mysql:host={$this->host};charset={$this->charset}";
                $pdo_temp = new PDO($dsn_no_db, $this->username, $this->password, $options);
                $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e2) {
                error_log("Database connection error: " . $e2->getMessage());
                throw new PDOException("Impossible de se connecter à la base de données", (int)$e2->getCode());
            }
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}
?>