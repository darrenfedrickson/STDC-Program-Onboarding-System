<?php
// user/register.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
if (isAdmin()) {
    redirect('/admin/index.php');
}

$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$program_id) {
    $_SESSION['error'] = "No program specified.";
    redirect('/user/index.php');
}

// Check if already applied
$checkStmt = $pdo->prepare("SELECT id FROM registrations WHERE program_id = ? AND user_id = ?");
$checkStmt->execute([$program_id, $_SESSION['user_id']]);
if ($checkStmt->fetch()) {
    $_SESSION['error'] = "You have already applied for this program.";
    redirect('/user/index.php');
}

// Fetch program info
$stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ? AND status = 'active'");
$stmt->execute([$program_id]);
$program = $stmt->fetch();

if (!$program) {
    $_SESSION['error'] = "Program not found or no longer active.";
    redirect('/user/index.php');
}

// Check capacity
$regCountStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE program_id = ?");
$regCountStmt->execute([$program_id]);
$currentCount = $regCountStmt->fetchColumn();

if ($currentCount >= $program['capacity']) {
    $_SESSION['error'] = "This program has reached its maximum capacity.";
    redirect('/user/index.php');
}

// Fetch fields from program_fields table
$schemaStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ? ORDER BY id ASC");
$schemaStmt->execute([$program_id]);
$schema = $schemaStmt->fetchAll();

// Check if user has a phone number and fetch all basic details
$userStmt = $pdo->prepare("SELECT full_name, email, phone_number FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch();

if (empty($currentUser['phone_number'])) {
    $_SESSION['error'] = "Please update your phone number in your profile before applying for programs.";
    redirect('/user/profile.php');
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<!-- Override body background for this specific page to match Google Forms -->
<style>
    body {
        background-color: #f0ebdf !important;
    }
</style>

<div class="mb-4 text-center">
    <a href="/stdc-program-onboarding-system/user/index.php" class="btn btn-sm btn-outline" style="background: white;">&larr; Back to Dashboard</a>
</div>

<div class="g-form-container">
    <div class="g-form-header-card">
        <h1 class="g-form-title"><?php echo htmlspecialchars($program['title']); ?></h1>
        <div class="g-form-desc-main"><?php echo nl2br(htmlspecialchars($program['description'])); ?></div>
        
        <?php if (!empty($program['custom_link_url'])): ?>
            <a href="<?php echo htmlspecialchars($program['custom_link_url']); ?>" target="_blank" class="g-form-link">
                <?php echo htmlspecialchars($program['custom_link_text'] ?: $program['custom_link_url']); ?>
            </a>
        <?php endif; ?>
        
        <hr class="g-form-divider">
        

        
        <div class="g-form-required-note">* Indicates required question</div>
    </div>
    
    <?php if (!empty($program['poster_image'])): ?>
        <div class="g-form-card" style="padding: 0; overflow: hidden;">
            <img src="/stdc-program-onboarding-system/<?php echo htmlspecialchars($program['poster_image']); ?>" alt="Program Poster" style="width: 100%; display: block;">
        </div>
    <?php endif; ?>
    
    <form action="/stdc-program-onboarding-system/user/process_apply.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
        
        <!-- Pre-filled Personal Information styled as cards -->
        <div class="g-form-card">
            <div class="g-form-question">Full Name (as per IC/Passport) <span style="color:var(--primary-red)">*</span></div>
            <div class="g-form-input-wrapper">
                <input type="text" class="g-form-input" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" readonly>
            </div>
        </div>
        
        <div class="g-form-card">
            <div class="g-form-question">Email Address <span style="color:var(--primary-red)">*</span></div>
            <div class="g-form-input-wrapper">
                <input type="email" class="g-form-input" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" readonly>
            </div>
        </div>
        
        <div class="g-form-card">
            <div class="g-form-question">Phone Number <span style="color:var(--primary-red)">*</span></div>
            <div class="g-form-input-wrapper">
                <input type="text" class="g-form-input" value="<?php echo htmlspecialchars($currentUser['phone_number'] ?? ''); ?>" readonly>
            </div>
        </div>
        
        <?php if (!empty($schema)): ?>
            <?php foreach ($schema as $field): ?>
                <?php echo renderDynamicField($field); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="g-form-card">
                <div class="g-form-desc-main" style="margin: 0;">This program requires no additional information. Click Submit to register.</div>
            </div>
        <?php endif; ?>
        
        <div class="g-form-submit-row">
            <button type="submit" class="g-form-submit-btn">Submit</button>
            <button type="reset" class="g-form-clear-btn">Clear form</button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const programId = <?php echo $program['id']; ?>;
    const draftKey = 'draft_program_' + programId;
    const form = document.querySelector('form');
    
    // 1. Load draft if it exists
    const draftData = localStorage.getItem(draftKey);
    if (draftData) {
        try {
            const data = JSON.parse(draftData);
            for (const key in data) {
                const inputs = form.querySelectorAll(`[name="${key}"]`);
                if (inputs.length === 0) continue;
                
                const type = inputs[0].type;
                if (type === 'radio' || type === 'checkbox') {
                    inputs.forEach(input => {
                        // For checkboxes, name could end with [] so data might be array
                        const valueArray = Array.isArray(data[key]) ? data[key] : [data[key]];
                        if (valueArray.includes(input.value)) {
                            input.checked = true;
                        }
                    });
                } else if (type !== 'file') {
                    inputs[0].value = data[key];
                }
            }
        } catch (e) {
            console.error('Error loading draft', e);
        }
    }
    
    // 2. Save draft on input change
    function saveDraft() {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            // Skip system hidden fields and files
            if (key === 'program_id') continue;
            
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'file') continue;
            
            if (data[key] !== undefined) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                if (key.endsWith('[]')) {
                    data[key] = [value];
                } else {
                    data[key] = value;
                }
            }
        }
        localStorage.setItem(draftKey, JSON.stringify(data));
    }
    
    form.addEventListener('input', saveDraft);
    form.addEventListener('change', saveDraft);
    
    // 3. Clear draft on form clear
    const clearBtn = form.querySelector('.g-form-clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            localStorage.removeItem(draftKey);
        });
    }
    
    // 4. We do not clear on submit to protect data in case of server validation errors.
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
