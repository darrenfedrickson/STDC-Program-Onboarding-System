<?php
// user/applications.php
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

?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <a href="<?php echo BASE_URL; ?>/user/index.php" class="btn btn-sm btn-outline mb-3">&larr; Back to Dashboard</a>
    <h1>My Applications</h1>
    <p class="text-light">Status of your past registrations.</p>
</div>

<div class="card mb-4">
    <?php if (count($appliedPrograms) > 0): ?>
        <?php foreach ($appliedPrograms as $prog): ?>
            <div class="builder-field mb-3" style="padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-surface);">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($prog['title']); ?></h4>
                        <p class="mb-1" style="font-size: 0.85rem; color: var(--text-light);">
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

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
