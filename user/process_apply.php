<?php
// user/process_apply.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
if (isAdmin()) {
    redirect('/admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = (int)$_POST['program_id'];
    $user_id = $_SESSION['user_id'];
    
    // Security check: ensure program is active and user hasn't applied
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ? AND status = 'active'");
    $stmt->execute([$program_id]);
    $program = $stmt->fetch();
    
    if (!$program) {
        $_SESSION['error'] = "Invalid or inactive program.";
        redirect('/user/index.php');
    }
    
    // Check capacity
    $regCountStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE program_id = ?");
    $regCountStmt->execute([$program_id]);
    $currentCount = $regCountStmt->fetchColumn();
    
    if ($currentCount >= $program['capacity']) {
        $_SESSION['error'] = "This program is full.";
        redirect('/user/index.php');
    }
    
    $checkStmt = $pdo->prepare("SELECT id FROM registrations WHERE program_id = ? AND user_id = ?");
    $checkStmt->execute([$program_id, $user_id]);
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = "You have already applied.";
        redirect('/user/index.php');
    }

    // Collect responses based on schema
    $schemaStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ?");
    $schemaStmt->execute([$program_id]);
    $schema = $schemaStmt->fetchAll();
    $responses = [];
    
    foreach ($schema as $field) {
        $fieldName = 'custom_' . $field['name'];
        $postKey = str_replace([' ', '.'], '_', $fieldName);
        $val = '';
        
        if ($field['type'] === 'file') {
            // Very basic file handling (in a real app, handle uploads properly)
            if (isset($_FILES[$postKey]) && $_FILES[$postKey]['error'] === UPLOAD_ERR_OK) {
                $val = $_FILES[$postKey]['name']; // Just storing name for demo
            }
        } else {
            $val = $_POST[$postKey] ?? '';
            $val = sanitizeInput($val);
            if (is_array($val)) {
                $val = implode(', ', $val);
            }
        }
        
        // Simple required validation
        if ($field['required'] && empty($val)) {
            $_SESSION['error'] = "Please fill in all required fields.";
            redirect('/user/register.php?id=' . $program_id);
        }
        
        $responses[] = [
            'field_id' => $field['id'],
            'value' => $val
        ];
    }
    
    $insertStmt = $pdo->prepare("INSERT INTO registrations (program_id, user_id, application_status) VALUES (?, ?, 'pending')");
    
    if ($insertStmt->execute([$program_id, $user_id])) {
        $reg_id = $pdo->lastInsertId();
        
        if (!empty($responses)) {
            $ansStmt = $pdo->prepare("INSERT INTO registration_answers (registration_id, field_id, answer_value) VALUES (?, ?, ?)");
            foreach ($responses as $ans) {
                $ansStmt->execute([$reg_id, $ans['field_id'], $ans['value']]);
            }
        }
        
        $_SESSION['success'] = "Registration submitted successfully!";
    } else {
        $_SESSION['error'] = "Failed to submit registration.";
    }
}

redirect('/user/index.php');
?>
