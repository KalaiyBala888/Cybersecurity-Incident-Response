<?php
// Test database connectivity
require_once(__DIR__ . '/config/db.php');

// Check if connection is successful
if ($conn->connect_error) {
    die("❌ Database Connection Failed: " . $conn->connect_error);
}

// Test query
$result = $conn->query("SELECT 1");

if ($result) {
    echo "✅ Database Connection Successful!<br>";
    echo "Connected to: " . $conn->server_info . "<br>";
    echo "Database: cyber_ir<br>";
    
    // Check if tables exist
    $tables = ['users', 'incidents', 'assignments', 'response_logs', 'evidence', 'resolutions'];
    echo "<br><strong>Checking tables:</strong><br>";
    
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT FOUND<br>";
        }
    }
    
    echo "<br><strong>System ready to use!</strong><br>";
    echo "Navigate to: <a href='/cyber_ir/login.php'>http://localhost/cyber_ir/login.php</a>";
} else {
    die("❌ Query failed: " . $conn->error);
}

$conn->close();
?>
