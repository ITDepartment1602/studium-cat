<?php
/**
 * Environment-aware Database Configuration
 * Automatically switches between local and production (Hostinger) environments
 */

// Detect environment based on server hostname
$isProduction = (
    isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false ||
        strpos($_SERVER['HTTP_HOST'], 'studium-cat.com') !== false ||
        strpos($_SERVER['HTTP_HOST'], 'yourdomain.com') !== false ||
        !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'])
    )
) || (
    isset($_SERVER['SERVER_NAME']) && !in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])
);

// Also check for environment variable (set via .htaccess or server config)
if (getenv('APP_ENV') === 'production') {
    $isProduction = true;
}

if ($isProduction) {
    // Production (Hostinger) Configuration
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'u436962267_studium');
    define('DB_PASS', 'Nclexamplified2023');
    define('DB_NAME', 'u436962267_studium');
    // Quiz database for production
    define('DB_QUIZ_HOST', '127.0.0.1');
    define('DB_QUIZ_USER', 'u940051167_quiz');
    define('DB_QUIZ_PASS', 'Nclexamplified2023');
    define('DB_QUIZ_NAME', 'u940051167_quiz');
} else {
    // Local Development Configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'u436962267_studium');
    // Quiz database for local
    define('DB_QUIZ_HOST', 'localhost');
    define('DB_QUIZ_USER', 'root');
    define('DB_QUIZ_PASS', '');
    define('DB_QUIZ_NAME', 'quiz');
}

// Create main mysqli connection
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die('connection failed: ' . mysqli_connect_error());

// Create PDO connection for files that need it
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Create quiz PDO connection
try {
    $connQuiz = new PDO("mysql:host=" . DB_QUIZ_HOST . ";dbname=" . DB_QUIZ_NAME, DB_QUIZ_USER, DB_QUIZ_PASS);
    $connQuiz->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Quiz PDO Connection failed: " . $e->getMessage());
}

// Helper function to get quiz database mysqli connection
function getQuizConnection() {
    return mysqli_connect(DB_QUIZ_HOST, DB_QUIZ_USER, DB_QUIZ_PASS, DB_QUIZ_NAME) or die('Quiz DB connection failed: ' . mysqli_connect_error());
}

// Helper function for mysqli with quiz database
function connectQuizDB() {
    global $con;
    mysqli_select_db($con, DB_QUIZ_NAME);
    return $con;
}

?>