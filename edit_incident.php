<?php
$pageTitle = 'Edit Incident - CyberIR';
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

if (!canEditIncident($user_id, $incident_id, $conn)) {
    die("You do not have permission to edit this incident");
}

// Get incident
$stmt = $conn->prepare("SELECT * FROM incidents WHERE incident_id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$incident) {
    die("Incident not found");
}

if ($incident['status'] === 'Closed') {
    die("Cannot edit closed incidents");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? ''l );
    $status = sanitizeInput($_POST['status'] ?? '');
    $severity = sanitizeInput($_POST['severity'] ?? '');
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    $valid_statuses = ['Open', 'Investigating', 'Contained', 'Resolved'];
    if (!in_array($status, $valid_statuses)) {
        $errors[] = 'Invalid status';
    }
    
    $valid_severities = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($severity, $valid_severities)) {
        $errors[] = 'Invalid severity';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE incidents SET
                    title = ?,
                    description = ?,
                    type = ?,
                    status = ?,
                    severity = ?
                WHERE incident_id = ?
            ");
            
            $stmt->bind_param("sssssi", $title, $description, $type, $status, $severity, $incident_id);
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
            } else {
                throw new Exception('Failed to update incident');
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
        <h1>Edit Incident</h1>
        <p>Incident #<?php echo $incident_id; ?></p>
    </div>

    <div class="section-card form-card">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Incident updated successfully! Redirecting...
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
                <label for="title" class="required">Title</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required 
                    value="<?php echo htmlspecialchars($incident['title']); ?>"
                    class="form-control"
                >
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="required">Type</label>
                    <select id="type" name="type" required class="form-control">
                        <option value="Malware" <?php echo $incident['type'] === 'Malware' ? 'selected' : ''; ?>>Malware</option>
                        <option value="Phishing" <?php echo $incident['type'] === 'Phishing' ? 'selected' : ''; ?>>Phishing</option>
                        <option value="Data Breach" <?php echo $incident['type'] === 'Data Breach' ? 'selected' : ''; ?>>Data Breach</option>
                        <option value="Unauthorized Access" <?php echo $incident['type'] === 'Unauthorized Access' ? 'selected' : ''; ?>>Unauthorized Access</option>
                        <option value="DDoS Attack" <?php echo $incident['type'] === 'DDoS Attack' ? 'selected' : ''; ?>>DDoS Attack</option>
                        <option value="System Compromise" <?php echo $incident['type'] === 'System Compromise' ? 'selected' : ''; ?>>System Compromise</option>
                        <option value="Other" <?php echo $incident['type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="severity" class="required">Severity</label>
                    <select id="severity" name="severity" required class="form-control">
                        <option value="Low" <?php echo $incident['severity'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $incident['severity'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $incident['severity'] === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?php echo $incident['severity'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" required class="form-control">
                        <option value="Open" <?php echo $incident['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="Investigating" <?php echo $incident['status'] === 'Investigating' ? 'selected' : ''; ?>>Investigating</option>
                        <option value="Contained" <?php echo $incident['status'] === 'Contained' ? 'selected' : ''; ?>>Contained</option>
                        <option value="Resolved" <?php echo $incident['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="required">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    required 
                    rows="6" 
                    class="form-control"
                ><?php echo htmlspecialchars($incident['description']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Incident</button>
                <a href="/cyber_ir/incident_details.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
