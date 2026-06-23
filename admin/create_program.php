<?php
// admin/create_program.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$initialSchema = '[]';
if (isset($_GET['template_id'])) {
    $tplId = (int)$_GET['template_id'];
    $stmt = $pdo->prepare("SELECT schema_json FROM form_templates WHERE id = ?");
    $stmt->execute([$tplId]);
    $schema = $stmt->fetchColumn();
    if ($schema) {
        $initialSchema = $schema;
    }
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <a href="/iDaftar@STDC/admin/programs.php" class="btn btn-sm btn-outline mb-3">&larr; Back to Programs</a>
    <h1>Create New Program</h1>
    <p>Define the details and registration form for your new program.</p>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="/iDaftar@STDC/admin/program_actions.php" method="POST" enctype="multipart/form-data" class="mt-3">
        <input type="hidden" name="action" value="create">
        
        <div class="grid grid-cols-2">
            <div class="form-group">
                <label class="form-label" for="title">Program Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="capacity">Capacity</label>
                <input type="number" name="capacity" id="capacity" class="form-control" required min="1">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3" style="resize: vertical; max-height: 400px;"></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="active">Active</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        
        <hr class="mt-4 mb-4" style="border-top: 1px solid var(--border-color);">
        
        <h4>Media & Links</h4>
        <p class="mb-3 text-light">Optional: Add a poster image and a custom redirect link.</p>
        
        <!-- Poster Preview Container -->
        <div class="mb-3" id="poster_preview_container" style="display: none;">
            <div style="padding: 0; overflow: hidden; border-radius: 8px; border: 1px solid var(--border-color); background: white;">
                <img id="poster_preview" src="" alt="Poster Preview" style="width: 100%; display: block;">
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label class="form-label" for="poster_image">Poster Image</label>
            <input type="file" name="poster_image" id="poster_image" class="form-control" accept="image/jpeg, image/png, image/webp" onchange="previewPoster(this)">
            <small class="text-light">Will be displayed at the top of the registration form.</small>
        </div>
        
        <div class="grid grid-cols-2">
            <div class="form-group mb-3">
                <label class="form-label" for="custom_link_text">Custom Link Text</label>
                <input type="text" name="custom_link_text" id="custom_link_text" class="form-control" placeholder="e.g. Click here to download IKTISASS FORM">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="custom_link_url">Custom Link URL</label>
                <input type="url" name="custom_link_url" id="custom_link_url" class="form-control" placeholder="https://...">
            </div>
        </div>
        
        <hr class="mt-4 mb-4" style="border-top: 1px solid var(--border-color);">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h4>Form Builder</h4>
                <p class="mb-3 text-light">Define the custom questions you want to ask attendees during registration.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                <button type="button" id="save-template-btn" class="btn btn-sm btn-outline">
                    Save as Template
                </button>
            </div>
        </div>
        
        <div id="fields-container"></div>
        
        <button type="button" id="add-field-btn" class="btn btn-outline mb-4">+ Add Field</button>
        
        <!-- Hidden input to store JSON array of fields -->
        <input type="hidden" name="form_schema" id="form_schema" value="[]">
        
        <div class="form-group mt-4 text-right">
            <button type="submit" class="btn btn-primary">Save Program</button>
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
    // Inject fetched template schema
    window.initialFormSchema = <?php echo $initialSchema; ?>;
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
