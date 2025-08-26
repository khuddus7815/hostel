<?php
// db.php - Local SQLite Database for Development

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Use SQLite for local development
$database_path = __DIR__ . '/database.sqlite';

try {
    // Create PDO connection to SQLite
    $pdo = new PDO("sqlite:$database_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT,
            password TEXT NOT NULL,
            type TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student (
            student_id INTEGER PRIMARY KEY,
            block_id INTEGER,
            usn TEXT,
            room TEXT,
            FOREIGN KEY (student_id) REFERENCES users(user_id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS warden (
            warden_id INTEGER PRIMARY KEY,
            block_id INTEGER,
            FOREIGN KEY (warden_id) REFERENCES users(user_id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS complaints (
            complaint_id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");
    
    // Create a test user if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (full_name, email, phone, password, type) 
            VALUES ('Test Student', 'student@test.com', '1234567890', '$hashedPassword', 'student')
        ");
        $studentId = $pdo->lastInsertId();
        $pdo->exec("
            INSERT INTO student (student_id, block_id, usn, room) 
            VALUES ($studentId, 1, 'USN001', '101')
        ");
        
        $hashedPassword2 = password_hash('warden123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (full_name, email, phone, password, type) 
            VALUES ('Test Warden', 'warden@test.com', '0987654321', '$hashedPassword2', 'warden')
        ");
        $wardenId = $pdo->lastInsertId();
        $pdo->exec("
            INSERT INTO warden (warden_id, block_id) 
            VALUES ($wardenId, 1)
        ");
    }
    
    // For backward compatibility with mysqli code, create a wrapper
    class PDOWrapper {
        private $pdo;
        public $insert_id;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function prepare($sql) {
            return new PDOStatementWrapper($this->pdo->prepare($sql), $this);
        }
        
        public function query($sql) {
            $result = $this->pdo->query($sql);
            return new PDOResultWrapper($result);
        }
        
        public function close() {
            $this->pdo = null;
        }
        
        public function lastInsertId() {
            return $this->pdo->lastInsertId();
        }
    }
    
    class PDOStatementWrapper {
        private $stmt;
        private $wrapper;
        
        public function __construct($stmt, $wrapper) {
            $this->stmt = $stmt;
            $this->wrapper = $wrapper;
        }
        
        public function bind_param($types, ...$params) {
            for ($i = 0; $i < count($params); $i++) {
                $this->stmt->bindValue($i + 1, $params[$i]);
            }
        }
        
        public function execute() {
            $result = $this->stmt->execute();
            $this->wrapper->insert_id = $this->wrapper->pdo->lastInsertId();
            return $result;
        }
        
        public function get_result() {
            return new PDOResultWrapper($this->stmt);
        }
        
        public function affected_rows() {
            return $this->stmt->rowCount();
        }
    }
    
    class PDOResultWrapper {
        private $stmt;
        private $results;
        private $index = 0;
        
        public function __construct($stmt) {
            $this->stmt = $stmt;
            $this->results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        public function num_rows() {
            return count($this->results);
        }
        
        public function fetch_assoc() {
            if ($this->index < count($this->results)) {
                return $this->results[$this->index++];
            }
            return null;
        }
    }
    
    $conn = new PDOWrapper($pdo);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed",
        "message" => $e->getMessage()
    ]);
    exit();
}
?>
