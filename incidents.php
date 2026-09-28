<?php
$pageTitle = 'Incidents - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireLogin();

require_once(__DIR__ . '/includes/header.php');

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get filter parameters
$severity_filter = $_GET['severity'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT i.incident_id, i.title, i.description, i.severity, i.status, i.date_reported, u.name, COUNT(a.assignment_id) as assigned FROM incidents i JOIN users u ON i.reported_by = u.user_id LEFT JOIN assignments a ON i.incident_id = a.incident_id WHERE 1=1";
$params = [];
$types = '';

if (!empty($severity_filter)) {
    $query .= " AND i.severity = ?";
    $params[] = $severity_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $query .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (i.title LIKE ? OR i.description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$query .= " GROUP BY i.incident_id ORDER BY i.date_reported DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$incidents = $stmt->get_result();
$stmt->close();
?>

<div class="container">
    <div class="page-header">
        <h1>Incidents Management</h1>
        <?php if (hasRole(['admin', 'analyst'])): ?>
        <a href="/cyber_ir/create_incident.php" class="btn btn-primary">+ Create New Incident</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="section-card">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search incidents..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="form-control"
                >
            </div>

            <div class="filter-group">
                <select name="severity" class="form-control">
                    <option value="">All Severities</option>
                    <option value="Low" <?php echo $severity_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $severity_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo $severity_filter === 'High' ? 'selected' : ''; ?>>High</option>
                    <option value="Critical" <?php echo $severity_filter === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="Investigating" <?php echo $status_filter === 'Investigating' ? 'selected' : ''; ?>>Investigating</option>
                    <option value="Contained" <?php echo $status_filter === 'Contained' ? 'selected' : ''; ?>>Contained</option>
                    <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="/cyber_ir/incidents.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Incidents Table -->
    <div class="section-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Assigned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($incidents->num_rows > 0): ?>
                    <?php while ($incident = $incidents->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $incident['incident_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars(substr($incident['title'], 0, 50)); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($incident['severity']); ?>">
                                <?php echo htmlspecialchars($incident['severity']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $incident['status'])); ?>">
                                <?php echo htmlspecialchars($incident['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($incident['name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($incident['date_reported'])); ?></td>
                        <td>
                            <?php echo $incident['assigned'] > 0 ? '✓ Assigned' : '<span class="text-warning">Unassigned</span>'; ?>
                        </td>
                        <td>
                            <a href="/cyber_ir/incident_details.php?id=<?php echo $incident['incident_id']; ?>" class="btn btn-small">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No incidents found</td>
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
