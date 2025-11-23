<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test connection
    echo "<p>✅ Database connected successfully</p>";
    
    // Check if api_logs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'api_logs'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p>✅ api_logs table exists</p>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE api_logs");
        $columns = $stmt->fetchAll();
        
        echo "<h2>Table Structure:</h2>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Get row count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM api_logs");
        $count = $stmt->fetch()['count'];
        echo "<p>Total rows: $count</p>";
        
        // Test the problematic query
        echo "<h2>Testing Query:</h2>";
        $limit = 50;
        $offset = 0;
        
        $stmt = $pdo->prepare("SELECT * FROM api_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();
        
        echo "<p>✅ Query executed successfully. Found " . count($logs) . " rows</p>";
        
    } else {
        echo "<p>❌ api_logs table does NOT exist</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
?>
