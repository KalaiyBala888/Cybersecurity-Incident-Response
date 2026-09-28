<?php
// Fix password hashes in database
require_once(__DIR__ . '/config/db.php');

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Updating user passwords with correct hash...\n";
echo "Hash: " . $hash . "\n\n";

// Update all users with the new hash
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email IN (?, ?, ?)");

$users = [
    'admin@cyber.local',
    'analyst@cyber.local',
    'viewer@cyber.local'
];

$stmt->bind_param("ssss", $hash, $users[0], $users[1], $users[2]);

if ($stmt->execute()) {
    echo "✅ Passwords updated successfully!\n\n";
    
    // Verify the update
    $verify_stmt = $conn->prepare("SELECT email, password FROM users WHERE email IN (?, ?, ?)");
    $verify_stmt->bind_param("sss", $users[0], $users[1], $users[2]);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    echo "Verifying passwords:\n";
    echo "====================\n";
    
    while ($row = $result->fetch_assoc()) {
        $test = password_verify($password, $row['password']);
        $status = $test ? "✅ VALID" : "❌ INVALID";
        echo $row['email'] . ": " . $status . "\n";
    }
    
    echo "\n✅ All passwords have been fixed!\n";
    echo "You can now login with password: password123\n";
    
    $verify_stmt->close();
} else {
    echo "❌ Error updating passwords: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
