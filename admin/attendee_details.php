<?php
// admin/attendee_details.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$reg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reg_id) {
    $_SESSION['error'] = "No registration specified.";
    redirect('/admin/programs.php');
}

// Fetch registration, user, and program
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, u.phone_number, p.title as program_title, p.id as program_id
    FROM registrations r 
    JOIN users u ON r.user_id = u.id 
    JOIN programs p ON r.program_id = p.id
    WHERE r.id = ?
");
$stmt->execute([$reg_id]);
$reg = $stmt->fetch();

if (!$reg) {
    $_SESSION['error'] = "Registration not found.";
    redirect('/admin/programs.php');
}

$schemaStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ? ORDER BY id ASC");
$schemaStmt->execute([$reg['program_id']]);
$schema = $schemaStmt->fetchAll();

$ansStmt = $pdo->prepare("SELECT field_id, answer_value FROM registration_answers WHERE registration_id = ?");
$ansStmt->execute([$reg_id]);
$rawAnswers = $ansStmt->fetchAll();
$answers = [];
foreach ($rawAnswers as $ans) {
    $answers[$ans['field_id']] = $ans['answer_value'];
}

// Handle all updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all'])) {
    
    // 1. Update Status
    if (isset($_POST['new_status'])) {
        $new_status = $_POST['new_status'];
        if ($new_status !== $reg['application_status']) {
            $updateStmt = $pdo->prepare("UPDATE registrations SET application_status = ? WHERE id = ?");
            if ($updateStmt->execute([$new_status, $reg_id])) {
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, registration_id, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], $reg_id, 'Application Status', $reg['application_status'], $new_status]);
                $reg['application_status'] = $new_status;
            }
        }
    }

    // 2. Update Answers
    foreach ($schema as $field) {
        $fieldName = 'custom_' . $field['name'];
        $postKey = str_replace([' ', '.'], '_', $fieldName);
        
        if (isset($_POST[$postKey])) {
            $val = $_POST[$postKey];
        } else if ($field['type'] === 'checkbox') {
            $val = [];
        } else {
            continue;
        }
        
        $val = sanitizeInput($val);
        if (is_array($val)) {
            $val = implode(', ', $val);
        }
        
        $old_val = $answers[$field['id']] ?? null;
        if ((string)$old_val !== (string)$val) {
            if ($old_val !== null) {
                $updAns = $pdo->prepare("UPDATE registration_answers SET answer_value = ? WHERE registration_id = ? AND field_id = ?");
                $updAns->execute([$val, $reg_id, $field['id']]);
            } else {
                $insAns = $pdo->prepare("INSERT INTO registration_answers (registration_id, field_id, answer_value) VALUES (?, ?, ?)");
                $insAns->execute([$reg_id, $field['id'], $val]);
            }
            
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, registration_id, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], $reg_id, $field['label'], (string)$old_val, (string)$val]);
        }
    }
    $_SESSION['success'] = "All changes saved successfully.";
    redirect('/admin/attendee_details.php?id=' . $reg_id);
}

// The backend still silently logs changes to activity_logs table here.

?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<form action="<?php echo BASE_URL; ?>/admin/attendee_details.php?id=<?php echo $reg_id; ?>" method="POST">
<input type="hidden" name="update_all" value="1">

<div class="mb-4" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/attendees.php?program_id=<?php echo $reg['program_id']; ?>" class="btn btn-sm btn-outline mb-3">&larr; Back to Attendees</a>
        <h1>Attendee Details</h1>
        <p>Program: <?php echo htmlspecialchars($reg['program_title']); ?></p>
    </div>
    <div>
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Save All Changes</button>
    </div>
</div>

<div class="grid grid-cols-2">
    <div class="card">
        <h3 class="mb-4">Profile Information</h3>
        <table class="table" style="width: 100%; text-align: left;">
            <tr>
                <th style="padding: 0.75rem 0; width: 40%; color: var(--text-light); border: none;">Full Name</th>
                <td style="border: none;"><?php echo htmlspecialchars($reg['full_name']); ?></td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 0; color: var(--text-light); border: none;">Email Address</th>
                <td style="border: none;"><a href="mailto:<?php echo htmlspecialchars($reg['email']); ?>"><?php echo htmlspecialchars($reg['email']); ?></a></td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 0; color: var(--text-light); border: none;">Phone Number</th>
                <td style="border: none;"><?php echo htmlspecialchars($reg['phone_number']); ?></td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 0; color: var(--text-light); border: none;">Applied On</th>
                <td style="border: none;"><?php echo date('M d, Y h:i A', strtotime($reg['created_at'])); ?></td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 0; color: var(--text-light); border: none;">Current Status</th>
                <td style="border: none;">
                    <span class="badge badge-<?php echo $reg['application_status']; ?>">
                        <?php echo ucfirst($reg['application_status']); ?>
                    </span>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 1.5rem 0; border-top: 1px solid var(--border-color);">
        
        <h4 class="mb-3">Update Status</h4>
        <select name="new_status" class="form-control" style="width: 100%;">
            <option value="pending" <?php if($reg['application_status']=='pending') echo 'selected'; ?>>Pending</option>
            <option value="shortlisted" <?php if($reg['application_status']=='shortlisted') echo 'selected'; ?>>Shortlisted</option>
            <option value="approved" <?php if($reg['application_status']=='approved') echo 'selected'; ?>>Approved</option>
            <option value="rejected" <?php if($reg['application_status']=='rejected') echo 'selected'; ?>>Rejected</option>
        </select>
    </div>
    
    <div class="card">
        <h3 class="mb-4">Form Responses</h3>
        <?php if (empty($schema)): ?>
            <p class="text-light">This program did not have any custom form fields.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($schema as $field): ?>
                    <?php 
                        $existingValue = $answers[$field['id']] ?? null;
                        echo renderDynamicField($field, $existingValue); 
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</form>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
