<?php
/**
 * Database Connection Test
 * Run this to verify database connectivity on dev.studium.cat
 */

require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Test - Studium Dev</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔧 Studium Database Diagnostic</h1>
    
    <?php
    echo '<div class="info">';
    echo '<h3>Server Information:</h3>';
    echo '<p>HTTP_HOST: <code>' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . '</code></p>';
    echo '<p>SERVER_NAME: <code>' . ($_SERVER['SERVER_NAME'] ?? 'unknown') . '</code></p>';
    echo '</div>';
    
    try {
        // Test 1: Load Database class
        echo '<div class="info">';
        echo '<h3>Test 1: Loading Database Class</h3>';
        $db = Database::getInstance();
        echo '<p class="success">✅ Database class loaded successfully</p>';
        echo '</div>';
        
        // Test 2: Check environment
        echo '<div class="info">';
        echo '<h3>Test 2: Environment Detection</h3>';
        $env = getEnvironment();
        echo '<p>Environment: <code>' . $env['environment'] . '</code></p>';
        echo '<p>Is Production: <code>' . ($env['is_production'] ? 'Yes' : 'No') . '</code></p>';
        echo '<p>DB Host: <code>' . $env['db_host'] . '</code></p>';
        echo '</div>';
        
        // Test 3: Test connection
        echo '<div class="info">';
        echo '<h3>Test 3: Database Connection</h3>';
        $con = $db->getConnection();
        echo '<p class="success">✅ Database connected successfully!</p>';
        echo '</div>';
        
        // Test 4: Run simple query
        echo '<div class="info">';
        echo '<h3>Test 4: Test Query</h3>';
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM login");
        if ($result) {
            echo '<p class="success">✅ Query executed successfully!</p>';
            echo '<p>Total users in database: <code>' . $result['total'] . '</code></p>';
        } else {
            echo '<p class="error">❌ Query failed</p>';
        }
        echo '</div>';
        
        echo '<div class="success">';
        echo '<h2>✅ All Tests Passed!</h2>';
        echo '<p>Your database configuration is working correctly on dev.studium.cat</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h2>❌ Database Error</h2>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        
        echo '<div class="info">';
        echo '<h3>Troubleshooting:</h3>';
        echo '<ul>';
        echo '<li>Check if database credentials in core/Database.php are correct</li>';
        echo '<li>Verify database server is running (127.0.0.1 for production)</li>';
        echo '<li>Check if database user has proper permissions</li>';
        echo '<li>Verify database name exists on Hostinger</li>';
        echo '</ul>';
        echo '</div>';
    }
    ?>
    
    <hr>
    <p><a href="/">← Back to Home</a></p>
    <p><small>Delete this file after testing for security</small></p>
</body>
</html>
