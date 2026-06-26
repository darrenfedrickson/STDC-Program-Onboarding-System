<?php
// admin/program_actions.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $capacity = (int)$_POST['capacity'];
        $status = $_POST['status'];
        $custom_link_text = sanitizeInput($_POST['custom_link_text'] ?? '');
        $custom_link_url = sanitizeInput($_POST['custom_link_url'] ?? '');
        $intake_date = sanitizeInput($_POST['intake_date'] ?? '');
        $duration = sanitizeInput($_POST['duration'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $form_schema = $_POST['form_schema']; // JSON string from JS
        
        $poster_image = null;
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__) . '/uploads/posters/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['poster_image']['name']));
            if (move_uploaded_file($_FILES['poster_image']['tmp_name'], $uploadDir . $fileName)) {
                $poster_image = 'uploads/posters/' . $fileName;
            }
        }
        
        // Validate JSON
        $fields = json_decode($form_schema, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = "Invalid form schema data.";
            redirect('/admin/programs.php');
        }

        $insertStmt = $pdo->prepare("INSERT INTO programs (title, description, capacity, status, created_by, custom_link_text, custom_link_url, poster_image, intake_date, duration, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insertStmt->execute([$title, $description, $capacity, $status, $_SESSION['user_id'], $custom_link_text, $custom_link_url, $poster_image, $intake_date, $duration, $location])) {
            $program_id = $pdo->lastInsertId();
            
            // Insert into program_fields
            if (is_array($fields)) {
                $fieldStmt = $pdo->prepare("INSERT INTO program_fields (program_id, name, label, description, type, options, required) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($fields as $field) {
                    $options = isset($field['options']) ? json_encode($field['options']) : null;
                    $required = isset($field['required']) ? (int)$field['required'] : 1;
                    $f_desc = isset($field['description']) ? sanitizeInput($field['description']) : null;
                    $fieldStmt->execute([
                        $program_id, 
                        $field['name'], 
                        $field['label'],
                        $f_desc,
                        $field['type'], 
                        $options, 
                        $required
                    ]);
                }
            }
            
            $_SESSION['success'] = "Program created successfully.";
        } else {
            $_SESSION['error'] = "Failed to create program.";
        }
    } elseif ($action === 'edit') {
        $program_id = (int)$_POST['program_id'];
        $capacity = (int)$_POST['capacity'];
        $status = $_POST['status'];
        $custom_link_text = sanitizeInput($_POST['custom_link_text'] ?? '');
        $custom_link_url = sanitizeInput($_POST['custom_link_url'] ?? '');
        $intake_date = sanitizeInput($_POST['intake_date'] ?? '');
        $duration = sanitizeInput($_POST['duration'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        
        $poster_image = null;
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__) . '/uploads/posters/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['poster_image']['name']));
            if (move_uploaded_file($_FILES['poster_image']['tmp_name'], $uploadDir . $fileName)) {
                $poster_image = 'uploads/posters/' . $fileName;
            }
        }
        
        // Count registrations
        $regStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE program_id = ?");
        $regStmt->execute([$program_id]);
        $current_registered = $regStmt->fetchColumn();
        
        if ($capacity < $current_registered) {
            $_SESSION['error'] = "Capacity cannot be less than the current number of registered attendees ({$current_registered}).";
            redirect('/admin/edit_program.php?id=' . $program_id);
        } else {
            if ($current_registered == 0 && isset($_POST['title'], $_POST['form_schema'])) {
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $form_schema = $_POST['form_schema'];
                
                $fields = json_decode($form_schema, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $_SESSION['error'] = "Invalid form schema data.";
                    redirect('/admin/edit_program.php?id=' . $program_id);
                }
                
                
                if ($poster_image) {
                    $updateStmt = $pdo->prepare("UPDATE programs SET title = ?, description = ?, capacity = ?, status = ?, custom_link_text = ?, custom_link_url = ?, poster_image = ?, intake_date = ?, duration = ?, location = ? WHERE id = ?");
                    $updateSuccess = $updateStmt->execute([$title, $description, $capacity, $status, $custom_link_text, $custom_link_url, $poster_image, $intake_date, $duration, $location, $program_id]);
                } else {
                    $updateStmt = $pdo->prepare("UPDATE programs SET title = ?, description = ?, capacity = ?, status = ?, custom_link_text = ?, custom_link_url = ?, intake_date = ?, duration = ?, location = ? WHERE id = ?");
                    $updateSuccess = $updateStmt->execute([$title, $description, $capacity, $status, $custom_link_text, $custom_link_url, $intake_date, $duration, $location, $program_id]);
                }
                
                if ($updateSuccess) {
                    
                    // Rebuild fields
                    $pdo->prepare("DELETE FROM program_fields WHERE program_id = ?")->execute([$program_id]);
                    
                    if (is_array($fields) && count($fields) > 0) {
                        $fieldStmt = $pdo->prepare("INSERT INTO program_fields (program_id, name, label, description, type, options, required) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($fields as $field) {
                            $options = isset($field['options']) ? json_encode($field['options']) : null;
                            $required = isset($field['required']) ? (int)$field['required'] : 1;
                            $f_desc = isset($field['description']) ? sanitizeInput($field['description']) : null;
                            $fieldStmt->execute([
                                $program_id, 
                                $field['name'], 
                                $field['label'], 
                                $f_desc,
                                $field['type'], 
                                $options, 
                                $required
                            ]);
                        }
                    }
                    
                    $_SESSION['success'] = "Program fully updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update program.";
                }
            } else {
                // Limited edit
                if ($poster_image) {
                    $updateStmt = $pdo->prepare("UPDATE programs SET capacity = ?, status = ?, custom_link_text = ?, custom_link_url = ?, poster_image = ?, intake_date = ?, duration = ?, location = ? WHERE id = ?");
                    $updateSuccess = $updateStmt->execute([$capacity, $status, $custom_link_text, $custom_link_url, $poster_image, $intake_date, $duration, $location, $program_id]);
                } else {
                    $updateStmt = $pdo->prepare("UPDATE programs SET capacity = ?, status = ?, custom_link_text = ?, custom_link_url = ?, intake_date = ?, duration = ?, location = ? WHERE id = ?");
                    $updateSuccess = $updateStmt->execute([$capacity, $status, $custom_link_text, $custom_link_url, $intake_date, $duration, $location, $program_id]);
                }
                
                if ($updateSuccess) {
                    $_SESSION['success'] = "Program capacity and status updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update program.";
                }
            }
            redirect('/admin/programs.php');
        }
    } elseif ($action === 'delete') {
        requireDeveloper(); // Security measure: ensure ONLY developers can trigger this action
        
        $program_id = (int)$_POST['program_id'];
        
        if ($program_id) {
            $deleteStmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
            if ($deleteStmt->execute([$program_id])) {
                $_SESSION['success'] = "Program deleted successfully. All associated fields and registrations were erased.";
            } else {
                $_SESSION['error'] = "Failed to delete program.";
            }
        } else {
            $_SESSION['error'] = "Invalid program ID.";
        }
        
        redirect('/admin/programs.php');
    }
}

redirect('/admin/programs.php');
?>
