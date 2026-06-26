<?php
// admin/programs.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Fetch programs
$stmt = $pdo->query("SELECT p.*, u.full_name as creator_name FROM programs p LEFT JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC");
$programs = $stmt->fetchAll();

// Fetch form templates
$tplStmt = $pdo->query("SELECT id, name FROM form_templates ORDER BY created_at ASC");
$formTemplates = $tplStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle template deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_template') {
    $tpl_id = (int)$_POST['template_id'];
    $delStmt = $pdo->prepare("DELETE FROM form_templates WHERE id = ?");
    if ($delStmt->execute([$tpl_id])) {
        $_SESSION['success'] = "Template deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete template.";
    }
    redirect('/admin/programs.php');
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <h1>Manage Programs</h1>
    <p>Create and manage STDC programs and their registration forms.</p>
</div>

<div class="grid grid-cols-1">
    <!-- List Programs -->
    <div class="card">
        <h3>Existing Programs</h3>
        <div class="table-responsive mt-3">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Title</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($programs) > 0): ?>
                        <?php $rowNum = 1; ?>
                        <?php foreach ($programs as $prog): ?>
                            <tr>
                                <td><?php echo $rowNum++; ?></td>
                                <td>
                                    <h4 class="mb-1"><?php echo htmlspecialchars($prog['title']); ?></h4>
                                    <p class="mb-1" style="font-size: 0.875rem;"><?php echo htmlspecialchars(substr($prog['description'], 0, 100)) . '...'; ?></p>
                                    <small class="text-light">Created by: <?php echo htmlspecialchars($prog['creator_name'] ?? 'System'); ?></small>
                                </td>
                                <td><?php echo $prog['capacity']; ?></td>
                                <td>
                                    <?php if ($prog['status'] === 'active'): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-closed">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <a href="<?php echo BASE_URL; ?>/admin/attendees.php?program_id=<?php echo $prog['id']; ?>" class="btn btn-sm btn-outline">View Attendees</a>
                                        <a href="<?php echo BASE_URL; ?>/admin/edit_program.php?id=<?php echo $prog['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        
                                        <?php if (isDeveloper()): ?>
                                            <form action="<?php echo BASE_URL; ?>/admin/program_actions.php" method="POST" style="margin: 0; display: contents;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="program_id" value="<?php echo $prog['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline btn-danger" onclick="return confirm('WARNING: Are you absolutely sure you want to delete this program? This will permanently erase all custom fields and all user registrations associated with it.');" style="border-color: #dc3545; color: #dc3545;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: auto;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No programs created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Start a new program gallery (Google Forms style) -->
    <div class="mt-4 mb-4" style="background-color: var(--bg-light); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
        <h3 class="mb-3" style="font-size: 1.1rem; font-weight: 500;">Start a new program</h3>
        
        <div class="template-gallery" style="display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem;">
            <!-- Blank Template -->
            <a href="<?php echo BASE_URL; ?>/admin/create_program.php" class="template-card-link" style="text-decoration: none; color: inherit; width: 140px; flex-shrink: 0;">
                <div class="template-card-preview" style="height: 180px; background: white; border: 1px solid #dadce0; border-radius: 4px; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: border-color 0.2s;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: #ea4335;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </div>
                <div class="template-card-title mt-2" style="font-weight: 500; font-size: 0.875rem;">Blank</div>
            </a>
            
            <!-- Saved Templates -->
            <?php foreach ($formTemplates as $tpl): ?>
            <div style="position: relative; width: 140px; flex-shrink: 0;">
                <a href="<?php echo BASE_URL; ?>/admin/create_program.php?template_id=<?php echo $tpl['id']; ?>" class="template-card-link" style="text-decoration: none; color: inherit; display: block;">
                    <div class="template-card-preview" style="height: 180px; background: white; border: 1px solid #dadce0; border-radius: 4px; padding: 10px; cursor: pointer; transition: border-color 0.2s; position: relative; overflow: hidden;">
                        <!-- Generic Document Thumbnail -->
                        <div style="width: 100%; height: 20px; background: #f1f3f4; border-radius: 2px; margin-bottom: 8px;"></div>
                        <div style="width: 80%; height: 10px; background: #f1f3f4; border-radius: 2px; margin-bottom: 6px;"></div>
                        <div style="width: 90%; height: 10px; background: #f1f3f4; border-radius: 2px; margin-bottom: 6px;"></div>
                        <div style="width: 40%; height: 10px; background: #f1f3f4; border-radius: 2px; margin-bottom: 16px;"></div>
                        
                        <div style="width: 100%; height: 20px; background: #f1f3f4; border-radius: 2px; margin-bottom: 8px;"></div>
                        <div style="width: 70%; height: 10px; background: #f1f3f4; border-radius: 2px; margin-bottom: 6px;"></div>
                        <div style="width: 85%; height: 10px; background: #f1f3f4; border-radius: 2px; margin-bottom: 6px;"></div>
                        
                        <div style="position: absolute; bottom: 10px; right: 10px; color: var(--primary-red);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#ea4335" opacity="0.1"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="#ea4335" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="14 2 14 8 20 8" fill="none" stroke="#ea4335" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><line x1="16" y1="13" x2="8" y2="13" stroke="#ea4335" stroke-width="1.5" stroke-linecap="round"/><line x1="16" y1="17" x2="8" y2="17" stroke="#ea4335" stroke-width="1.5" stroke-linecap="round"/><polyline points="10 9 9 9 8 9" stroke="#ea4335" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                    </div>
                    <div class="template-card-title mt-2" style="font-weight: 500; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($tpl['name']); ?></div>
                </a>
                
                <form action="<?php echo BASE_URL; ?>/admin/programs.php" method="POST" style="position: absolute; top: 8px; right: 8px; margin: 0; z-index: 10;">
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="template_id" value="<?php echo $tpl['id']; ?>">
                    <button type="submit" onclick="return confirm('Delete this template?');" style="background: white; border: 1px solid #dadce0; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--danger); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
