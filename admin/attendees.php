<?php
// admin/attendees.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if (!$program_id) {
    $_SESSION['error'] = "No program specified.";
    redirect('/admin/programs.php');
}

// Fetch program info
$stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$program_id]);
$program = $stmt->fetch();

if (!$program) {
    $_SESSION['error'] = "Program not found.";
    redirect('/admin/programs.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registration_id'], $_POST['new_status'])) {
    $reg_id = (int)$_POST['registration_id'];
    $new_status = $_POST['new_status'];
    
    $updateStmt = $pdo->prepare("UPDATE registrations SET application_status = ? WHERE id = ? AND program_id = ?");
    if ($updateStmt->execute([$new_status, $reg_id, $program_id])) {
        $_SESSION['success'] = "Status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update status.";
    }
    redirect('/admin/attendees.php?program_id=' . $program_id);
}

// Fetch registrations
$regStmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, u.phone_number 
    FROM registrations r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.program_id = ? 
    ORDER BY r.created_at DESC
");
$regStmt->execute([$program_id]);
$registrations = $regStmt->fetchAll();

// Parse schema to know the questions - removed in EAV
// $schema = json_decode($program['form_schema'], true) ?: [];
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <a href="/stdc-program-onboarding-system/admin/programs.php" class="btn btn-sm btn-outline mb-3">&larr; Back to Programs</a>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1>Attendees: <?php echo htmlspecialchars($program['title']); ?></h1>
            <p>Capacity: <?php echo count($registrations); ?> / <?php echo $program['capacity']; ?></p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="/stdc-program-onboarding-system/admin/program_stats.php?program_id=<?php echo $program_id; ?>" class="btn btn-sm btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-color: var(--border-color); color: var(--text-color);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                Statistics
            </a>
            <a href="/stdc-program-onboarding-system/admin/export_attendees.php?program_id=<?php echo $program_id; ?>" class="btn btn-sm btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-color: var(--border-color); color: var(--text-color);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Attendee</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($registrations) > 0): ?>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><?php echo htmlspecialchars($reg['phone_number']); ?></td>
                            
                            <td>
                                <span class="badge badge-<?php echo $reg['application_status']; ?>">
                                    <?php echo ucfirst($reg['application_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <form action="/stdc-program-onboarding-system/admin/attendees.php?program_id=<?php echo $program_id; ?>" method="POST" style="display: flex; gap: 0.25rem;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                        <select name="new_status" class="form-control" style="padding: 0.15rem 0.5rem; font-size: 0.75rem; height: auto;">
                                            <option value="pending" <?php if($reg['application_status']=='pending') echo 'selected'; ?>>Pending</option>
                                            <option value="shortlisted" <?php if($reg['application_status']=='shortlisted') echo 'selected'; ?>>Shortlisted</option>
                                            <option value="approved" <?php if($reg['application_status']=='approved') echo 'selected'; ?>>Approved</option>
                                            <option value="rejected" <?php if($reg['application_status']=='rejected') echo 'selected'; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary" style="padding: 0.15rem 0.5rem; font-size: 0.75rem;">Update</button>
                                    </form>
                                    <a href="/stdc-program-onboarding-system/admin/attendee_details.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-outline" style="text-align: center; padding: 0.15rem; font-size: 0.75rem;">View Details</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No registrations yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
