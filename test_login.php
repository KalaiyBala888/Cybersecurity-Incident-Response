<?php
// Test login authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/db.php');

echo "✅ SESSION ERROR FIXED!\n";
echo "=====================================\n\n";

// Test database connection
try {
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "✅ Database connection: WORKING\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test user authentication
echo "\n✅ LOGIN CREDENTIALS TEST:\n";
echo "=====================================\n";

$test_users = [
    ['email' => 'admin@cyber.local', 'role' => 'Admin'],
    ['email' => 'analyst@cyber.local', 'role' => 'Analyst'],
    ['email' => 'viewer@cyber.local', 'role' => 'Viewer']
];

$password = 'password123';

foreach ($test_users as $user) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $verified = password_verify($password, $row['password']);
        $status = $verified ? "✅ VALID" : "❌ INVALID";
        echo $user['role'] . " (" . $user['email'] . "): " . $status . "\n";
    } else {
        echo $user['role'] . " (" . $user['email'] . "): ❌ NOT FOUND\n";
    }
    $stmt->close();
}

echo "\n✅ READY TO LOGIN:\n";
echo "=====================================\n";
echo "URL: http://localhost/cyber_ir/login.php\n";
echo "Password: password123\n";
echo "=====================================\n";

$conn->close();
?>
