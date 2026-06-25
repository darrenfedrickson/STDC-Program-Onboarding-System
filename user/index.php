<?php
// user/index.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
if (isAdmin()) {
    redirect('/admin/index.php');
}

$user_id = $_SESSION['user_id'];

// Get applied programs
$appliedStmt = $pdo->prepare("
    SELECT p.*, r.application_status, r.created_at as applied_at
    FROM programs p
    JOIN registrations r ON p.id = r.program_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$appliedStmt->execute([$user_id]);
$appliedPrograms = $appliedStmt->fetchAll();

// Get IDs of applied programs to exclude from available list
$appliedIds = array_column($appliedPrograms, 'id');

// Get active programs not yet applied to
if (!empty($appliedIds)) {
    $placeholders = implode(',', array_fill(0, count($appliedIds), '?'));
    $availStmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM registrations r WHERE r.program_id = p.id) as registered_count FROM programs p WHERE p.status = 'active' AND p.id NOT IN ($placeholders) ORDER BY p.created_at DESC");
    $availStmt->execute($appliedIds);
} else {
    $availStmt = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM registrations r WHERE r.program_id = p.id) as registered_count FROM programs p WHERE p.status = 'active' ORDER BY p.created_at DESC");
}
$availablePrograms = $availStmt->fetchAll();
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <h1>User Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>.</p>
</div>

<div class="grid grid-cols-2">
    <!-- Available Programs -->
    <div class="card">
        <h3>Available Programs</h3>
        <p class="text-light mb-3">Programs you can apply for right now.</p>
        
        <?php if (count($availablePrograms) > 0): ?>
            <?php foreach ($availablePrograms as $prog): ?>
                <div class="builder-field mb-3" style="border-left: 4px solid var(--primary-red);">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($prog['title']); ?></h4>
                            <p class="mb-2" style="font-size: 0.875rem;"><?php echo htmlspecialchars(substr($prog['description'], 0, 100)) . '...'; ?></p>
                        </div>
                        <?php if ($prog['registered_count'] >= $prog['capacity']): ?>
                            <button class="btn btn-sm" style="background: var(--text-light); color: white; cursor: not-allowed;" disabled>Full</button>
                        <?php else: ?>
                            <a href="/stdc-program-onboarding-system/user/register.php?id=<?php echo $prog['id']; ?>" class="btn btn-sm btn-primary">Apply Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No new active programs available at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- My Applications -->
    <div class="card">
        <h3>My Applications</h3>
        <p class="text-light mb-3">Status of your past registrations.</p>
        
        <?php if (count($appliedPrograms) > 0): ?>
            <?php foreach ($appliedPrograms as $prog): ?>
                <div class="builder-field mb-3">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($prog['title']); ?></h4>
                            <p class="mb-1" style="font-size: 0.75rem; color: var(--text-light);">
                                Applied on: <?php echo date('M d, Y', strtotime($prog['applied_at'])); ?>
                            </p>
                        </div>
                        <span class="badge badge-<?php echo $prog['application_status']; ?>">
                            <?php echo ucfirst($prog['application_status']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't applied to any programs yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
