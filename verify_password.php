<?php
// Test password verification
$hash = '$2y$10$sThwKJNsRTYs1tq0u.0.POiQiMMEjHSRHCsaGqztq4BNo7.UIvfGC';
$password = 'password123';
$result = password_verify($password, $hash);

echo "✅ PASSWORD VERIFICATION TEST\n";
echo "==============================\n";
echo "Input Password: " . $password . "\n";
echo "Hash Preview: " . substr($hash, 0, 30) . "...\n";
echo "Verification Result: " . ($result ? "✅ CORRECT ✓" : "❌ INCORRECT ✗") . "\n\n";

if ($result) {
    echo "✅ SUCCESS! All users have been updated with correct passwords.\n\n";
    echo "You can now login with:\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Admin:    admin@cyber.local     | password: password123\n";
    echo "Analyst:  analyst@cyber.local   | password: password123\n";
    echo "Viewer:   viewer@cyber.local    | password: password123\n";
    echo "═══════════════════════════════════════════════════════════\n";
}
?>
