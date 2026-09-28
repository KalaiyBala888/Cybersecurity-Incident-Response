<?php
$pageTitle = 'Assign Incident - CyberIR';
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
$stmt = $conn->prepare("SELECT title FROM incidents WHERE incident_id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$incident) {
    die("Incident not found");
}

// Get all analysts
$stmt = $conn->prepare("SELECT user_id, name, email FROM users WHERE role = 'analyst' AND status = 'active' ORDER BY name");
$stmt->execute();
$analysts = $stmt->get_result();
$stmt->close();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token validation failed';
    }
    
    $analyst_id = isset($_POST['analyst_id']) ? intval($_POST['analyst_id']) : 0;
    $deadline = sanitizeInput($_POST['deadline'] ?? '');
    
    if ($analyst_id <= 0) {
        $errors[] = 'Please select an analyst';
    }
    
    if (empty($deadline)) {
        $errors[] = 'Deadline is required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO assignments (incident_id, assigned_to, assigned_by, deadline, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            if (!$stmt) {
                throw new Exception('Database preparation failed');
            }
            
            $stmt->bind_param("iiii", $incident_id, $analyst_id, $user_id, $deadline);
            
            if ($stmt->execute()) {
                $success = true;
                $stmt->close();
                echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
            } else {
                throw new Exception('Failed to assign incident');
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
        <h1>Assign Incident</h1>
        <p><?php echo htmlspecialchars($incident['title']); ?></p>
    </div>

    <div class="section-card form-card" style="max-width: 600px;">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Incident assigned successfully! Redirecting...
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
                <label for="analyst_id" class="required">Assign to Analyst</label>
                <select id="analyst_id" name="analyst_id" required class="form-control">
                    <option value="">Select Analyst</option>
                    <?php while ($analyst = $analysts->fetch_assoc()): ?>
                    <option value="<?php echo $analyst['user_id']; ?>">
                        <?php echo htmlspecialchars($analyst['name']); ?> (<?php echo htmlspecialchars($analyst['email']); ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="deadline" class="required">Deadline</label>
                <input 
                    type="datetime-local" 
                    id="deadline" 
                    name="deadline" 
                    required 
                    class="form-control"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Assign Incident</button>
                <a href="/cyber_ir/incident_details.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
