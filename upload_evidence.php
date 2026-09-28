<?php
$pageTitle = 'Upload Evidence - CyberIR';
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
    die("You do not have permission to upload evidence for this incident");
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
    
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid file';
    }
    
    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'log', 'txt', 'csv', 'zip'];
    $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
    }
    
    // Validate file size (max 10MB)
    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File size exceeds 10MB limit';
    }
    
    if (empty($errors)) {
        try {
            // Create secure file path
            $upload_dir = __DIR__ . '/uploads/' . intval($incident_id);
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $original_filename = basename($_FILES['file']['name']);
            $new_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
            $file_path = $upload_dir . '/' . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                // Store file info in database
                $relative_path = 'uploads/' . intval($incident_id) . '/' . $new_filename;
                
                $stmt = $conn->prepare("
                    INSERT INTO evidence (incident_id, file_name, file_path, file_type, file_size, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if (!$stmt) {
                    throw new Exception('Database preparation failed');
                }
                
                $file_type = mime_content_type($file_path) ?: $file_extension;
                $file_size = filesize($file_path);
                
                $stmt->bind_param("isssii", $incident_id, $original_filename, $relative_path, $file_type, $file_size, $user_id);
                
                if ($stmt->execute()) {
                    $success = true;
                    $stmt->close();
                    echo '<script>setTimeout(function() { window.location.href = "/cyber_ir/incident_details.php?id=' . $incident_id . '"; }, 2000);</script>';
                } else {
                    throw new Exception('Failed to save evidence record');
                }
            } else {
                throw new Exception('Failed to move uploaded file');
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
        <h1>Upload Evidence</h1>
        <p><?php echo htmlspecialchars($incident['title']); ?></p>
    </div>

    <div class="section-card form-card" style="max-width: 600px;">
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ Evidence uploaded successfully! Redirecting...
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

        <form method="POST" enctype="multipart/form-data" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="incident_id" value="<?php echo $incident_id; ?>">

            <div class="form-group">
                <label for="file" class="required">Select File</label>
                <div class="file-upload">
                    <input 
                        type="file" 
                        id="file" 
                        name="file" 
                        required 
                        class="file-input"
                        accept=".jpg,.jpeg,.png,.pdf,.log,.txt,.csv,.zip"
                    >
                    <p class="file-hint">Allowed: jpg, jpeg, png, pdf, log, txt, csv, zip (Max 10MB)</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Upload Evidence</button>
                <a href="/cyber_ir/incident_details.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.file-upload {
    position: relative;
    display: inline-block;
    cursor: pointer;
    width: 100%;
}

.file-input {
    padding: 20px;
    border: 2px dashed #007bff;
    border-radius: 8px;
    background: #f9f9f9;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
}

.file-input:hover {
    background: #f0f0f0;
}

.file-hint {
    margin-top: 10px;
    font-size: 0.85rem;
    color: #666;
}
</style>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
