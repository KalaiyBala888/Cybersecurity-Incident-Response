<?php
$pageTitle = 'Reports - CyberIR';
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/includes/auth.php');

requireRole('admin');

require_once(__DIR__ . '/includes/header.php');

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM incidents");
$stmt->execute();
$total_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");
$stmt->execute();
$severity_data = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
$stmt->execute();
$status_data = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT u.name, COUNT(i.incident_id) as reported_count
    FROM incidents i
    JOIN users u ON i.reported_by = u.user_id
    GROUP BY i.reported_by
    ORDER BY reported_count DESC
");
$stmt->execute();
$reporter_stats = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT u.name, COUNT(a.assignment_id) as assigned_count
    FROM assignments a
    JOIN users u ON a.assigned_to = u.user_id
    GROUP BY a.assigned_to
    ORDER BY assigned_count DESC
");
$stmt->execute();
$analyst_stats = $stmt->get_result();
$stmt->close();
?>

<div class="container">
    <div class="page-header">
        <h1>Reports & Analysis</h1>
        <p>System-wide incident statistics and reports</p>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <p class="stat-label">Total Incidents</p>
                    <h3 class="stat-number"><?php echo $total_incidents; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Data -->
    <div class="reports-grid">
        <!-- Incidents by Severity -->
        <div class="section-card">
            <h3>Incidents by Severity</h3>
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $severity_data->data_seek(0);
                        while ($row = $severity_data->fetch_assoc()): 
                        $percentage = $total_incidents > 0 ? round(($row['count'] / $total_incidents) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php echo strtolower($row['severity']); ?>">
                                    <?php echo htmlspecialchars($row['severity']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['count']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <?php echo $percentage; ?>%
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Incidents by Status -->
        <div class="section-card">
            <h3>Incidents by Status</h3>
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $status_data->data_seek(0);
                        while ($row = $status_data->fetch_assoc()): 
                        $percentage = $total_incidents > 0 ? round(($row['count'] / $total_incidents) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['count']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <?php echo $percentage; ?>%
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Reporters -->
        <div class="section-card">
            <h3>Top Incident Reporters</h3>
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Incidents Reported</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($reporter_stats->num_rows > 0):
                            while ($row = $reporter_stats->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['reported_count']; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="2">No data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Analysts -->
        <div class="section-card">
            <h3>Top Analysts</h3>
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Assignments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($analyst_stats->num_rows > 0):
                            while ($row = $analyst_stats->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['assigned_count']; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="2">No data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="report-footer">
        <p>Report Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        <button onclick="window.print()" class="btn btn-secondary">Print Report</button>
    </div>
</div>

<style>
.reports-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.report-table {
    overflow-x: auto;
}

.progress-bar {
    display: inline-block;
    width: 100px;
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    margin-right: 10px;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    transition: width 0.3s ease;
}

.report-footer {
    margin-top: 30px;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 768px) {
    .reports-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .page-header, .report-footer button,
    .navbar, .footer {
        display: none;
    }
    
    body {
        background: white;
    }
}
</style>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
