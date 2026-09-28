<?php
$pageTitle = 'Access Denied';
require_once(__DIR__ . '/includes/auth.php');
require_once(__DIR__ . '/includes/header.php');

requireLogin();
?>

<div class="container">
    <div class="error-page">
        <h1>🔒 Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <p>Your current role: <strong><?php echo htmlspecialchars($_SESSION['user']['role']); ?></strong></p>
        <a href="/cyber_ir/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
