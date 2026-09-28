<?php
$pageTitle = 'Create User - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole('admin');

require_once(__DIR__ . '/includes/header.php');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Valid email is required';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    $valid_roles = ['admin', 'analyst', 'viewer'];
    if (!in_array($role, $valid_roles)) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email already exists';
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/users.php"; }, 2000);</script>';
            } else {
                throw new Exception('Failed to create user');
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="container">
    <div class="page-header">
        <h1>Create New User</h1>
    </div>

    <div class="section-card form-card" style="max-width: 600px;">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ User created successfully! Redirecting...
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="name" class="required">Full Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    required 
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="role" class="required">Role</label>
                <select id="role" name="role" required class="form-control">
                    <option value="">Select Role</option>
                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="analyst" <?php echo (isset($_POST['role']) && $_POST['role'] === 'analyst') ? 'selected' : ''; ?>>Analyst</option>
                    <option value="viewer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password" class="required">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="At least 6 characters"
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password" class="required">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    class="form-control"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="/cyber_ir/users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
