<?php
$pageTitle = 'Add Response Log - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole(['admin', 'analyst']);

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();
$user_id = $user['user_id'];
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($incident_id <= 0) {
    die("Invalid incident ID");
}

// Check if user can edit this incident
if (!canEditIncident($user_id, $incident_id, $conn)) {
    die("You do not have permission to add logs to this incident");
}

// Get incident
$stmt = $conn->prepare("SELECT title FROM incidents WHERE incident_id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$incident) {
    die("Incident not found");
}

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $action = sanitizeInput($_POST['action'] ?? '');
    
    if (empty($action)) {
        $errors[] = 'Action description is required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO response_logs (incident_id, analyst_id, action_taken, log_time)
                VALUES (?, ?, ?, NOW())
            ");
            
            if (!$stmt) {
                throw new Exception('Database preparation failed');
            }
            
            $stmt->bind_param("iss", $incident_id, $user_id, $action);
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
            } else {
                throw new Exception('Failed to add log entry');
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
        <h1>Add Response Log</h1>
        <p><?php echo htmlspecialchars($incident['title']); ?></p>
    </div>

    <div class="section-card form-card" style="max-width: 800px;">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Log entry added successfully! Redirecting...
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
            <input type="hidden" name="incident_id" value="<?php echo $incident_id; ?>">

            <div class="form-group">
                <label for="action" class="required">Actionstaken / Case Notes</label>
                <textarea 
                    id="action" 
                    name="action" 
                    required 
                    rows="8" 
                    placeholder="Document your investigation actions, findings, and next steps"
                    class="form-control"
                ><?php echo isset($_POST['action']) ? htmlspecialchars($_POST['action']) : ''; ?></textarea>
                <small class="text-muted">Be specific and clear about what was done and what was found</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Log Entry</button>
                <a href="/cyber_ir/incident_details.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
