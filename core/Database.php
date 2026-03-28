<?php
/**
 * Studium-CAT Database Class
 * 
 * Provides a clean, singleton-pattern database interface with:
 * - Automatic environment detection (local vs production)
 * - Prepared statement support
 * - Connection pooling
 * - Proper error handling
 * 
 * USAGE:
 *   $db = Database::getInstance();
 *   $result = $db->query("SELECT * FROM users WHERE id = ?", [$id]);
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $quizConnection = null;
    private $isProduction = false;
    
    // Database configuration
    private $config = [
        'local' => [
            'host' => 'localhost',
            'user' => 'root',
            'pass' => '',
            'name' => 'u436962267_studium',
            'quiz_host' => 'localhost',
            'quiz_user' => 'root',
            'quiz_pass' => '',
            'quiz_name' => 'quiz'
        ],
        'production' => [
            'host' => '127.0.0.1',
            'user' => 'u436962267_studium',
            'pass' => 'Nclexamplified2023',
            'name' => 'u436962267_studium',
            'quiz_host' => '127.0.0.1',
            'quiz_user' => 'u940051167_quiz',
            'quiz_pass' => 'Nclexamplified2023',
            'quiz_name' => 'u940051167_quiz'
        ]
    ];
    
    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct() {
        $this->detectEnvironment();
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Detect if we're in production or local environment
     */
    private function detectEnvironment(): void {
        // Check environment variable first (most reliable)
        if (getenv('APP_ENV') === 'production') {
            $this->isProduction = true;
            return;
        }
        
        // Check HTTP_HOST
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (!in_array($host, ['localhost', '127.0.0.1', '::1'])) {
                $this->isProduction = true;
                return;
            }
        }
        
        // Check SERVER_NAME
        if (isset($_SERVER['SERVER_NAME'])) {
            if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
                $this->isProduction = true;
                return;
            }
        }
        
        $this->isProduction = false;
    }
    
    /**
     * Get current environment
     */
    public function isProduction(): bool {
        return $this->isProduction;
    }
    
    /**
     * Get environment name
     */
    public function getEnvironment(): string {
        return $this->isProduction ? 'production' : 'local';
    }
    
    /**
     * Connect to databases
     */
    private function connect(): void {
        $env = $this->isProduction ? 'production' : 'local';
        $cfg = $this->config[$env];
        
        // Main database connection (MySQLi)
        $this->connection = new mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['pass'],
            $cfg['name']
        );
        
        if ($this->connection->connect_error) {
            error_log("Database connection failed: " . $this->connection->connect_error);
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        // Set charset
        $this->connection->set_charset("utf8mb4");
        
        // Set timezone for Philippine time
        $this->connection->query("SET time_zone = '+08:00'");
    }
    
    /**
     * Get main database connection
     */
    public function getConnection(): mysqli {
        return $this->connection;
    }
    
    /**
     * Get quiz database connection (lazy loaded)
     */
    public function getQuizConnection(): mysqli {
        if ($this->quizConnection === null) {
            $env = $this->isProduction ? 'production' : 'local';
            $cfg = $this->config[$env];
            
            $this->quizConnection = new mysqli(
                $cfg['quiz_host'],
                $cfg['quiz_user'],
                $cfg['quiz_pass'],
                $cfg['quiz_name']
            );
            
            if ($this->quizConnection->connect_error) {
                error_log("Quiz database connection failed: " . $this->quizConnection->connect_error);
                throw new Exception("Quiz database connection failed.");
            }
            
            $this->quizConnection->set_charset("utf8mb4");
        }
        
        return $this->quizConnection;
    }
    
    /**
     * Execute a prepared query with parameters (SECURE)
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @param bool $useQuiz Use quiz database instead of main
     * @return mysqli_result|false
     */
    public function query(string $sql, array $params = [], bool $useQuiz = false): mysqli_result|bool {
        $conn = $useQuiz ? $this->getQuizConnection() : $this->connection;
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Query preparation failed: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Execute an INSERT/UPDATE/DELETE query
     */
    public function execute(string $sql, array $params = [], bool $useQuiz = false): bool {
        $conn = $useQuiz ? $this->getQuizConnection() : $this->connection;
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Execute preparation failed: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne(string $sql, array $params = [], bool $useQuiz = false): ?array {
        $result = $this->query($sql, $params, $useQuiz);
        if ($result && $row = $result->fetch_assoc()) {
            $result->free();
            return $row;
        }
        return null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = [], bool $useQuiz = false): array {
        $result = $this->query($sql, $params, $useQuiz);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        return $rows;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId(): int {
        return $this->connection->insert_id;
    }
    
    /**
     * Get affected rows
     */
    public function affectedRows(): int {
        return $this->connection->affected_rows;
    }
    
    /**
     * Escape string (legacy support, prefer prepared statements)
     */
    public function escape(string $string): string {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Determine parameter types for bind_param
     */
    private function getParamTypes(array $params): string {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob
            }
        }
        return $types;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Close connections on destruct
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
        if ($this->quizConnection) {
            $this->quizConnection->close();
        }
    }
}

/**
 * Quick access function for Database instance
 */
function db(): Database {
    return Database::getInstance();
}
?>
