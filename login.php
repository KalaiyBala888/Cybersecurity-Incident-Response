<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: /cyber_ir/dashboard.php");
    exit();
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        if (empty($email) || empty($password)) {
            $loginError = 'Email and password are required';
        } elseif (!validateEmail($email)) {
            $loginError = 'Invalid email format';
        } else {
            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ? AND status = 'active'");
            
            if (!$stmt) {
                throw new Exception('Database query failed: ' . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password using password_verify()
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user'] = [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    
                    // Update last login
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $updateStmt->bind_param("i", $user['user_id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Redirect based on role
                    header("Location: /cyber_ir/dashboard.php");
                    exit();
                } else {
                    $loginError = 'Invalid email or password';
                }
            } else {
                $loginError = 'Invalid email or password';
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        $loginError = 'An error occurred during login: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberIR - Authentication</title>
    <link rel="stylesheet" href="/cyber_ir/assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>⚔️ CyberIR</h1>
                <p>Security Operations Center</p>
                <h2>Cybersecurity Incident Response</h2>
            </div>

            <form method="POST" class="login-form" id="loginForm">
                <?php if ($loginError): ?>
                <div class="alert alert-error">
                    <strong>Login Failed:</strong> <?php echo htmlspecialchars($loginError); ?>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Login
                </button>
            </form>

            <div class="login-footer">
                <div class="demo-credentials">
                    <p><strong>Demo Credentials:</strong></p>
                    <ul>
                        <li><strong>Admin:</strong> admin@cyber.local / password123</li>
                        <li><strong>Analyst:</strong> analyst@cyber.local / password123</li>
                        <li><strong>Viewer:</strong> viewer@cyber.local / password123</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
