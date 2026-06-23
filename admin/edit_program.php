<?php
// admin/edit_program.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Get current registrations count
$regStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE program_id = ?");
$regStmt->execute([$program_id]);
$current_registered = $regStmt->fetchColumn();

// Fetch fields for Smart Edit Mode
$fieldsStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ? ORDER BY id ASC");
$fieldsStmt->execute([$program_id]);
$fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <a href="/iDaftar@STDC/admin/programs.php" class="btn btn-sm btn-outline mb-3">&larr; Back to Programs</a>
    <h1>Edit Program: <?php echo htmlspecialchars($program['title']); ?></h1>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="/iDaftar@STDC/admin/program_actions.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
        
        <?php if ($current_registered == 0): ?>
            <div class="form-group mb-3">
                <label class="form-label" for="title">Program Title</label>
                <input type="text" name="title" id="title" class="form-control" required value="<?php echo htmlspecialchars($program['title']); ?>">
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" style="resize: vertical; max-height: 400px;"><?php echo htmlspecialchars($program['description']); ?></textarea>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-3">
                <strong>Smart Edit Mode:</strong> Because <?php echo $current_registered; ?> participant(s) have already registered, you cannot edit the Title, Description, or Form Fields to protect data integrity.
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">Program Title</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($program['title']); ?>" readonly style="background-color: var(--bg-light); color: var(--text-light); border-color: var(--border-color);">
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" rows="3" readonly style="background-color: var(--bg-light); color: var(--text-light); border-color: var(--border-color); resize: vertical; max-height: 400px;"><?php echo htmlspecialchars($program['description']); ?></textarea>
            </div>
        <?php endif; ?>
        
        <div class="form-group mb-3">
            <label class="form-label" for="capacity">Capacity (Minimum: <?php echo $current_registered; ?>)</label>
            <input type="number" name="capacity" id="capacity" class="form-control" required min="<?php echo $current_registered; ?>" value="<?php echo $program['capacity']; ?>">
        </div>
        
        <div class="form-group mt-3">
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="active" <?php if($program['status'] == 'active') echo 'selected'; ?>>Active</option>
                <option value="closed" <?php if($program['status'] == 'closed') echo 'selected'; ?>>Closed</option>
            </select>
        </div>
        
        <hr class="mt-4 mb-4" style="border-top: 1px solid var(--border-color);">
        
        <h4>Media & Links</h4>
        <p class="mb-3 text-light">Optional: Update poster image and custom redirect link.</p>
        
        <!-- Poster Preview Container -->
        <div class="mb-3" id="poster_preview_container" style="<?php echo empty($program['poster_image']) ? 'display: none;' : ''; ?>">
            <div style="padding: 0; overflow: hidden; border-radius: 8px; border: 1px solid var(--border-color); background: white;">
                <img id="poster_preview" src="<?php echo !empty($program['poster_image']) ? '/iDaftar@STDC/' . htmlspecialchars($program['poster_image']) : ''; ?>" alt="Poster Preview" style="width: 100%; display: block;">
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label class="form-label" for="poster_image">Poster Image (Upload new to replace)</label>
            <input type="file" name="poster_image" id="poster_image" class="form-control" accept="image/jpeg, image/png, image/webp" onchange="previewPoster(this)">
        </div>
        
        <div class="grid grid-cols-2">
            <div class="form-group mb-3">
                <label class="form-label" for="custom_link_text">Custom Link Text</label>
                <input type="text" name="custom_link_text" id="custom_link_text" class="form-control" placeholder="e.g. Click here to download IKTISASS FORM" value="<?php echo htmlspecialchars($program['custom_link_text'] ?? ''); ?>">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="custom_link_url">Custom Link URL</label>
                <input type="url" name="custom_link_url" id="custom_link_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($program['custom_link_url'] ?? ''); ?>">
            </div>
        </div>
        
        <?php if ($current_registered == 0): ?>
            <hr class="mt-4 mb-4" style="border-top: 1px solid var(--border-color);">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h4>Form Builder</h4>
                    <p class="mb-3 text-light">Edit the custom questions for this program.</p>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <button type="button" id="save-template-btn" class="btn btn-sm btn-outline">
                        Save as Template
                    </button>
                </div>
            </div>
            
            <div id="fields-container"></div>
            
            <button type="button" id="add-field-btn" class="btn btn-outline mb-4">+ Add Field</button>
            
            <input type="hidden" name="form_schema" id="form_schema" value="[]">
        <?php else: ?>
            <hr class="mt-4 mb-4" style="border-top: 1px solid var(--border-color);">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h4>Form Fields (Read-Only)</h4>
                    <p class="mb-3 text-light">These are the custom questions currently active for this program.</p>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <button type="button" id="save-template-btn" class="btn btn-sm btn-outline">
                        Save as Template
                    </button>
                </div>
            </div>
            
            <input type="hidden" id="form_schema" value="<?php echo htmlspecialchars(json_encode($fields)); ?>">
            
            <?php if (empty($fields)): ?>
                <p class="text-light">No custom fields defined.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($fields as $field): ?>
                        <div style="background: var(--bg-light); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($field['label']); ?></strong>
                                <span class="badge badge-pending"><?php echo ucfirst($field['type']); ?></span>
                            </div>
                            <?php if ($field['type'] === 'select' || $field['type'] === 'radio'): ?>
                                <p style="margin: 0; color: var(--text-light); font-size: 0.875rem;">
                                    Options: 
                                    <?php 
                                        $opts = is_string($field['options']) ? json_decode($field['options'], true) : $field['options'];
                                        echo is_array($opts) ? htmlspecialchars(implode(', ', $opts)) : 'None';
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p style="margin: 0; color: var(--text-light); font-size: 0.875rem; margin-top: 0.5rem;">Required: <?php echo $field['required'] ? 'Yes' : 'No'; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<!-- Modal for Template System -->
<div id="saveTemplateModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 400px;">
        <h3 class="mb-3">Save as Template</h3>
        <p class="text-light mb-3">Save your current form fields as a reusable template.</p>
        <div class="form-group mb-3">
            <label class="form-label">Template Name</label>
            <input type="text" id="new_template_name" class="form-control" placeholder="e.g. My Custom Feedback Form">
        </div>
        <div class="mt-4 text-right">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('saveTemplateModal').style.display='none'">Cancel</button>
            <button type="button" id="confirm-save-template" class="btn btn-primary">Save Template</button>
        </div>
    </div>
</div>

<script>
    window.initialFormSchema = <?php echo json_encode($fields); ?>;
</script>
<script src="/iDaftar@STDC/assets/js/form-builder.js"></script>

<script>
function previewPoster(input) {
    const container = document.getElementById('poster_preview_container');
    const preview = document.getElementById('poster_preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
