<?php
/**
 * Studium-CAT Database Class
 * 
 * Provides a clean, singleton-pattern database interface using configurations
 * from config.php.  This is the unified DB handler for the entire project.
 * 
 * USAGE:
 *   $db = db(); // uses the helper function at the bottom
 *   $row = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $quizConnection = null;
    
    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct() {
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
     * Connect to the main database using constants from config.php
     */
    private function connect(): void {
        if (!defined('DB_HOST')) {
            throw new Exception("Database constants not defined. Make sure config.php is included.");
        }
        
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            error_log("Database connection failed: " . $this->connection->connect_error);
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        $this->connection->set_charset("utf8mb4");
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
            if (!defined('QUIZ_DB_HOST')) {
                throw new Exception("Quiz database constants not defined.");
            }
            
            // If quiz DB is same as main, reuse connection
            if (QUIZ_DB_HOST === DB_HOST && QUIZ_DB_NAME === DB_NAME && QUIZ_DB_USER === DB_USER) {
                $this->quizConnection = $this->connection;
            } else {
                $this->quizConnection = new mysqli(QUIZ_DB_HOST, QUIZ_DB_USER, QUIZ_DB_PASS, QUIZ_DB_NAME);
                
                if ($this->quizConnection->connect_error) {
                    error_log("Quiz connection failed: " . $this->quizConnection->connect_error);
                    throw new Exception("Quiz database connection failed.");
                }
                
                $this->quizConnection->set_charset("utf8mb4");
            }
        }
        
        return $this->quizConnection;
    }
    
    /**
     * Execute a prepared query with parameters
     */
    public function query(string $sql, array $params = [], bool $useQuiz = false) {
        $conn = $useQuiz ? $this->getQuizConnection() : $this->connection;
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Query preparation failed: " . $conn->error . " | SQL: " . $sql);
            return false;
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Query execution failed: " . $stmt->error);
            return false;
        }
        
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
            if ($result instanceof mysqli_result) $result->free();
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
        if ($result && $result instanceof mysqli_result) {
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
     * Escape string (legacy support)
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
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            elseif (is_string($param)) $types .= 's';
            else $types .= 'b';
        }
        return $types;
    }
    
    private function __clone() {}
    public function __destruct() {
        if ($this->connection && $this->connection !== $this->quizConnection) {
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
if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
    }
}
?>
