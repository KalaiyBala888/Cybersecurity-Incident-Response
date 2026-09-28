<?php
$pageTitle = 'User Management - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole('admin');

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();

// Get all users
$stmt = $conn->prepare("SELECT user_id, name, email, role, status, last_login FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

$success = false;
$errors = [];

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    } else {
        $toggle_user_id = intval($_POST['user_id'] ?? 0);
        $new_status = sanitizeInput($_POST['new_status'] ?? '');
        
        if ($toggle_user_id > 0 && in_array($new_status, ['active', 'inactive'])) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND user_id != ?");
            $stmt->bind_param("sii", $new_status, $toggle_user_id, $user['user_id']);
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                header("Refresh:0");
                exit();
            }
            $stmt->close();
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="container">
    <div class="page-header">
        <h1>User Management</h1>
        <a href="/cyber_ir/create_user.php" class="btn btn-primary">+ Create User</a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        ✓ User status updated successfully
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

    <div class="section-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($u['role']); ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $u['status']; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never';  ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $u['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                <button type="submit" class="btn btn-small btn-<?php echo $u['status'] === 'active' ? 'danger' : 'success'; ?>">
                                    <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No users found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
