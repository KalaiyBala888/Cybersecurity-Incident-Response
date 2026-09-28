<?php
$pageTitle = 'Dashboard - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireLogin();

$user = getCurrentUser();
$user_id = $user['user_id'];

require_once(__DIR__ . '/includes/header.php');

// Get statistics based on role
$stats = [];

if (hasRole('admin')) {
    // Admin stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM incidents");
    $stmt->execute();
    $stats['total_incidents'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as critical FROM incidents WHERE severity = 'Critical'");
    $stmt->execute();
    $stats['critical_incidents'] = $stmt->get_result()->fetch_assoc()['critical'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as unassigned FROM incidents i WHERE NOT EXISTS (SELECT 1 FROM assignments a WHERE a.incident_id = i.incident_id)");
    $stmt->execute();
    $stats['unassigned'] = $stmt->get_result()->fetch_assoc()['unassigned'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM incidents WHERE status IN ('Open', 'Investigating', 'Contained')");
    $stmt->execute();
    $stats['active'] = $stmt->get_result()->fetch_assoc()['active'];
    $stmt->close();
    
} elseif (hasRole('analyst')) {
    // Analyst stats
    $stmt = $conn->prepare("SELECT COUNT(*) as assigned FROM assignments WHERE assigned_to = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['assigned'] = $stmt->get_result()->fetch_assoc()['assigned'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM assignments a JOIN incidents i ON a.incident_id = i.incident_id WHERE a.assigned_to = ? AND i.status IN ('Open', 'Investigating', 'Contained')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['active'] = $stmt->get_result()->fetch_assoc()['active'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as reported FROM incidents WHERE reported_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['reported'] = $stmt->get_result()->fetch_assoc()['reported'];
    $stmt->close();
}

// Get recent activity
$stmt = $conn->prepare("
    SELECT i.incident_id, i.title, i.severity, i.status, i.date_reported, u.name
    FROM incidents i
    JOIN users u ON i.reported_by = u.user_id
    ORDER BY i.date_reported DESC
    LIMIT 5
");
$stmt->execute();
$recent_incidents = $stmt->get_result();
$stmt->close();
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <?php if (hasRole('admin')): ?>
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <p class="stat-label">Total Incidents</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['total_incidents']; ?>">0</h3>
                </div>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-content">
                <div class="stat-icon">🔴</div>
                <div class="stat-info">
                    <p class="stat-label">Critical Incidents</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['critical_incidents']; ?>">0</h3>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-content">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <p class="stat-label">Unassigned</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['unassigned']; ?>">0</h3>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-content">
                <div class="stat-icon">⚡</div>
                <div class="stat-info">
                    <p class="stat-label">Active Incidents</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['active']; ?>">0</h3>
                </div>
            </div>
        </div>

        <?php elseif (hasRole('analyst')): ?>
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <p class="stat-label">Assigned to Me</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['assigned']; ?>">0</h3>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-content">
                <div class="stat-icon">⚡</div>
                <div class="stat-info">
                    <p class="stat-label">Active Investigations</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['active']; ?>">0</h3>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <p class="stat-label">Incidents Reported</p>
                    <h3 class="stat-number" data-target="<?php echo $stats['reported']; ?>">0</h3>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="section-card">
        <h2>Recent Activity</h2>
        <div class="activity-table">
            <table>
                <thead>
                    <tr>
                        <th>Incident Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($incident = $recent_incidents->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($incident['title']); ?></strong></td>
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
                            <a href="/cyber_ir/incident_details.php?id=<?php echo $incident['incident_id']; ?>" class="btn btn-small">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dashboard-footer">
        <p>System Last Updated: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
