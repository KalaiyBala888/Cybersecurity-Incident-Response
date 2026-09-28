<?php
$pageTitle = 'Incident Details - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireLogin();

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();
$user_id = $user['user_id'];
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($incident_id <= 0) {
    die("Invalid incident ID");
}

// Get incident details
$stmt = $conn->prepare("
    SELECT i.*, u.name as reported_name, u.email as reported_email
    FROM incidents i
    JOIN users u ON i.reported_by = u.user_id
    WHERE i.incident_id = ?
");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$incident) {
    die("Incident not found");
}

// Get assignments
$stmt = $conn->prepare("
    SELECT a.*, u.name as analyst_name, u.email
    FROM assignments a
    JOIN users u ON a.assigned_to = u.user_id
    WHERE a.incident_id = ?
    ORDER BY a.assigned_at DESC
");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();

// Get response logs
$stmt = $conn->prepare("
    SELECT l.*, u.name as analyst_name
    FROM response_logs l
    JOIN users u ON l.analyst_id = u.user_id
    WHERE l.incident_id = ?
    ORDER BY l.log_time DESC
");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

// Get evidence
$stmt = $conn->prepare("
    SELECT e.*, u.name as uploaded_by_name
    FROM evidence e
    JOIN users u ON e.uploaded_by = u.user_id
    WHERE e.incident_id = ?
    ORDER BY e.uploaded_at DESC
");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$evidence_list = $stmt->get_result();
$stmt->close();

// Get resolution if exists
$stmt = $conn->prepare("
    SELECT r.*, u.name as resolved_by_name
    FROM resolutions r
    JOIN users u ON r.resolved_by = u.user_id
    WHERE r.incident_id = ?
");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$resolution = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check permissions
$can_edit = canEditIncident($user_id, $incident_id, $conn);
$can_assign = hasRole('admin');
$can_close = hasRole('admin');
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?php echo htmlspecialchars($incident['title']); ?></h1>
            <p>Incident #<?php echo $incident_id; ?></p>
        </div>
        <div class="header-actions">
            <?php if ($can_edit && $incident['status'] !== 'Closed'): ?>
            <a href="/cyber_ir/edit_incident.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
            
            <?php if ($can_assign && $incident['status'] !== 'Closed'): ?>
            <a href="/cyber_ir/assign_incident.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Assign</a>
            <?php endif; ?>
            
            <?php if ($can_close && $incident['status'] !== 'Closed'): ?>
            <a href="/cyber_ir/close_incident.php?id=<?php echo $incident_id; ?>" class="btn btn-danger">Close</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="incident-grid">
        <!-- Left Column - Incident Details -->
        <div class="incident-details">
            <!-- Status Section -->
            <div class="section-card">
                <h3>Incident Status</h3>
                <div class="status-info">
                    <div class="info-row">
                        <span class="label">Current Status:</span>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $incident['status'])); ?>">
                            <?php echo htmlspecialchars($incident['status']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Severity:</span>
                        <span class="badge badge-<?php echo strtolower($incident['severity']); ?>">
                            <?php echo htmlspecialchars($incident['severity']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Type:</span>
                        <span><?php echo htmlspecialchars($incident['type']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Incident Information -->
            <div class="section-card">
                <h3>Incident Information</h3>
                <div class="info-row">
                    <span class="label">Reported By:</span>
                    <span><?php echo htmlspecialchars($incident['reported_name']); ?> (<?php echo htmlspecialchars($incident['reported_email']); ?>)</span>
                </div>
                <div class="info-row">
                    <span class="label">Reported Date:</span>
                    <span><?php echo date('M d, Y H:i:s', strtotime($incident['date_reported'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Last Updated:</span>
                    <span><?php echo date('M d, Y H:i:s', strtotime($incident['date_updated'])); ?></span>
                </div>
            </div>

            <!-- Description -->
            <div class="section-card">
                <h3>Description</h3>
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                </div>
            </div>

            <!-- Assignment Section -->
            <div class="section-card">
                <h3>Assignments</h3>
                <?php if ($assignments->num_rows > 0): ?>
                <div class="assignments-list">
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                    <div class="assignment-item">
                        <div>
                            <strong><?php echo htmlspecialchars($assignment['analyst_name']); ?></strong>
                            <p class="text-muted"><?php echo htmlspecialchars($assignment['email']); ?></p>
                        </div>
                        <div>
                            <span class="badge badge-<?php echo $assignment['status']; ?>">
                                <?php echo ucfirst($assignment['status']); ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <?php if ($assignment['deadline']): ?>
                            <p>Deadline: <?php echo date('M d, Y', strtotime($assignment['deadline'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No assignments yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Logs and Evidence -->
        <div class="incident-sidebar">
            <!-- Response Logs -->
            <div class="section-card">
                <h3>Response Logs & Timeline</h3>
                <?php if ($can_edit && $incident['status'] !== 'Closed'): ?>
                <a href="/cyber_ir/add_log.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary btn-block">+ Add Log Entry</a>
                <hr>
                <?php endif; ?>

                <?php if ($logs->num_rows > 0): ?>
                <div class="logs-timeline">
                    <?php while ($log = $logs->fetch_assoc()): ?>
                    <div class="log-item">
                        <div class="log-time"><?php echo date('M d, H:i', strtotime($log['log_time'])); ?></div>
                        <div class="log-analyst"><?php echo htmlspecialchars($log['analyst_name']); ?></div>
                        <div class="log-action"><?php echo nl2br(htmlspecialchars($log['action_taken'])); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No log entries yet</p>
                <?php endif; ?>
            </div>

            <!-- Evidence -->
            <div class="section-card">
                <h3>Evidence Files</h3>
                <?php if ($can_edit && $incident['status'] !== 'Closed'): ?>
                <a href="/cyber_ir/upload_evidence.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary btn-block">+ Upload Evidence</a>
                <hr>
                <?php endif; ?>

                <?php if ($evidence_list->num_rows > 0): ?>
                <div class="evidence-list">
                    <?php while ($ev = $evidence_list->fetch_assoc()): ?>
                    <div class="evidence-item">
                        <div class="file-icon">📄</div>
                        <div>
                            <a href="/cyber_ir/<?php echo htmlspecialchars($ev['file_path']); ?>" target="_blank" download>
                                <?php echo htmlspecialchars($ev['file_name']); ?>
                            </a>
                            <p class="text-muted"><?php echo htmlspecialchars($ev['file_type']); ?> (<?php echo round($ev['file_size'] / 1024, 2); ?> KB)</p>
                            <p class="text-muted">Uploaded by <?php echo htmlspecialchars($ev['uploaded_by_name']); ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No evidence files</p>
                <?php endif; ?>
            </div>

            <!-- Resolution Section -->
            <?php if ($resolution): ?>
            <div class="section-card success-card">
                <h3>✓ Resolution</h3>
                <div class="info-row">
                    <span class="label">Resolved By:</span>
                    <span><?php echo htmlspecialchars($resolution['resolved_by_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Resolved Date:</span>
                    <span><?php echo date('M d, Y H:i:s', strtotime($resolution['resolved_date'])); ?></span>
                </div>
                <div style="margin-top: 15px;">
                    <strong>Root Cause:</strong>
                    <p><?php echo nl2br(htmlspecialchars($resolution['root_cause'])); ?></p>
                </div>
                <div style="margin-top: 15px;">
                    <strong>Summary:</strong>
                    <p><?php echo nl2br(htmlspecialchars($resolution['summary'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.incident-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.incident-details, .incident-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.status-info, .info-row {
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.status-info .info-row:last-child {
    border-bottom: none;
}

.assignments-list {
    max-height: 400px;
    overflow-y: auto;
}

.assignment-item {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 10px;
}

.logs-timeline {
    max-height: 500px;
    overflow-y: auto;
}

.log-item {
    padding: 12px;
    border-left: 3px solid #007bff;
    margin-bottom: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.log-time {
    font-size: 0.85rem;
    color: #666;
    font-weight: bold;
}

.log-analyst {
    font-size: 0.9rem;
    color: #007bff;
    margin: 3px 0;
}

.log-action {
    margin-top: 8px;
    color: #333;
    word-wrap: break-word;
}

.evidence-list {
    max-height: 400px;
    overflow-y: auto;
}

.evidence-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 10px;
}

.evidence-item .file-icon {
    font-size: 1.5rem;
}

.evidence-item a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.evidence-item a:hover {
    text-decoration: underline;
}

.description-box {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
    line-height: 1.6;
    max-height: 400px;
    overflow-y: auto;
}

.success-card {
    background: #f0fdf4 !important;
    border-left: 4px solid #22c55e !important;
}

.header-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .incident-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
