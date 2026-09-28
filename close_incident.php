<?php
$pageTitle = 'Close Incident - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole('admin');

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();
$user_id = $user['user_id'];
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($incident_id <= 0) {
    die("Invalid incident ID");
}

// Get incident
$stmt = $conn->prepare("SELECT title, status FROM incidents WHERE incident_id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$incident) {
    die("Incident not found");
}

if ($incident['status'] === 'Closed') {
    die("Incident is already closed");
}

// Check if resolution exists
$stmt = $conn->prepare("SELECT resolution_id FROM resolutions WHERE incident_id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$has_resolution = $stmt->get_result()->num_rows > 0;
$stmt->close();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $root_cause = sanitizeInput($_POST['root_cause'] ?? '');
    $summary = sanitizeInput($_POST['summary'] ?? '');
    
    if (empty($root_cause)) {
        $errors[] = 'Root cause is required';
    }
    if (empty($summary)) {
        $errors[] = 'Resolution summary is required';
    }
    
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->begin_transaction();
            
            // Insert or update resolution
            if ($has_resolution) {
                $stmt = $conn->prepare("
                    UPDATE resolutions SET root_cause = ?, summary = ?, resolved_by = ?, resolved_date = NOW()
                    WHERE incident_id = ?
                ");
                $stmt->bind_param("ssii", $root_cause, $summary, $user_id, $incident_id);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO resolutions (incident_id, root_cause, summary, resolved_by, resolved_date)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("issi", $incident_id, $root_cause, $summary, $user_id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to save resolution');
            }
            $stmt->close();
            
            // Update incident status
            $closed_status = 'Closed';
            $stmt = $conn->prepare("UPDATE incidents SET status = ? WHERE incident_id = ?");
            $stmt->bind_param("si", $closed_status, $incident_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to close incident');
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success = true;
            echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="container">
    <div class="page-header">
        <h1>Close Incident</h1>
        <p><?php echo htmlspecialchars($incident['title']); ?></p>
    </div>

    <div class="section-card form-card" style="max-width: 800px;">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Incident closed successfully! Redirecting...
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
                <label for="root_cause" class="required">Root Cause Analysis</label>
                <textarea 
                    id="root_cause" 
                    name="root_cause" 
                    required 
                    rows="6" 
                    placeholder="Provide detailed analysis of the root cause..."
                    class="form-control"
                ><?php echo isset($_POST['root_cause']) ? htmlspecialchars($_POST['root_cause']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="summary" class="required">Resolution Summary</label>
                <textarea 
                    id="summary" 
                    name="summary" 
                    required 
                    rows="6" 
                    placeholder="Summarize the actions taken and resolution..."
                    class="form-control"
                ><?php echo isset($_POST['summary']) ? htmlspecialchars($_POST['summary']) : ''; ?></textarea>
            </div>

            <div class="alert alert-info">
                ⚠️ <strong>Notice:</strong> Closing this incident will make it read-only. Ensure all required information has been documented.
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Close Incident</button>
                <a href="/cyber_ir/incident_details.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
