<?php
$pageTitle = 'Create Incident - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole(['admin', 'analyst']);

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();
$user_id = $user['user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? '');
    $severity = sanitizeInput($_POST['severity'] ?? 'Medium');
    
    // Validate required fields
    if (empty($title)) {
        $errors[] = 'Incident title is required';
    }
    if (empty($description)) {
        $errors[] = 'Incident description is required';
    }
    if (empty($type)) {
        $errors[] = 'Incident type is required';
    }
    
    // Validate severity
    $valid_severities = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($severity, $valid_severities)) {
        $errors[] = 'Invalid severity level';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO incidents (title, description, type, severity, status, reported_by, date_reported)
                VALUES (?, ?, ?, ?, 'Open', ?, NOW())
            ");
            
            if (!$stmt) {
                throw new Exception('Database preparation failed');
            }
            
            $stmt->bind_param("ssssi", $title, $description, $type, $severity, $user_id);
            
            if ($stmt->execute()) {
                $incident_id = $stmt->insert_id;
                $stmt->close();
                
                $success = true;
                echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
            } else {
                throw new Exception('Failed to create incident');
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
        <h1>Create New Incident</h1>
        <p>Report a new security incident</p>
    </div>

    <div class="section-card form-card">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Incident created successfully! Redirecting...
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
                <label for="title" class="required">Incident Title</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required 
                    placeholder="e.g., Unauthorized Access Detected on Web Server"
                    value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                    class="form-control"
                >
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="required">Incident Type</label>
                    <select id="type" name="type" required class="form-control">
                        <option value="">Select Type</option>
                        <option value="Malware" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Malware') ? 'selected' : ''; ?>>Malware</option>
                        <option value="Phishing" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Phishing') ? 'selected' : ''; ?>>Phishing</option>
                        <option value="Data Breach" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Data Breach') ? 'selected' : ''; ?>>Data Breach</option>
                        <option value="Unauthorized Access" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Unauthorized Access') ? 'selected' : ''; ?>>Unauthorized Access</option>
                        <option value="DDoS Attack" <?php echo (isset($_POST['type']) && $_POST['type'] === 'DDoS Attack') ? 'selected' : ''; ?>>DDoS Attack</option>
                        <option value="System Compromise" <?php echo (isset($_POST['type']) && $_POST['type'] === 'System Compromise') ? 'selected' : ''; ?>>System Compromise</option>
                        <option value="Other" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="severity" class="required">Severity Level</label>
                    <select id="severity" name="severity" required class="form-control">
                        <option value="Low" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo (!isset($_POST['severity']) || $_POST['severity'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'High') ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Critical') ? 'selected' : ''; ?>>Critical</option>
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
                    placeholder="Provide detailed information about the incident..."
                    class="form-control"
                ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Incident</button>
                <a href="/cyber_ir/incidents.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
