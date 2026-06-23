<?php
// includes/functions.php
session_start();

function redirect($url) {
    if (strpos($url, '/') === 0) {
        $url = '/iDaftar@STDC' . $url;
    }
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isDeveloper() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'developer';
}

function isAdmin() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'developer']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        redirect('/admin/login.php');
    }
    if (!isAdmin()) {
        die("Access Denied: Admin only.");
    }
}

function requireDeveloper() {
    if (!isDeveloper()) {
        $_SESSION['error'] = "Access denied. Only developers can perform this action.";
        redirect('/admin/programs.php');
    }
}

function displayAlert() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
}

function sanitizeInput($data) {
    if (is_null($data)) return '';
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

function renderDynamicField($field, $existingValue = null) {
    $type = $field['type'];
    $label = htmlspecialchars($field['label']);
    $name = htmlspecialchars($field['name']);
    $required = !empty($field['required']) ? 'required' : '';
    $desc = !empty($field['description']) ? htmlspecialchars($field['description']) : '';
    
    // Outer Google Form style card
    $html = '<div class="g-form-card">';
    
    // Label and Required asterisk
    $html .= "<div class='g-form-question'>{$label} " . ($required ? '<span style="color:var(--primary-red)">*</span>' : '') . "</div>";
    
    // Description text if it exists
    if ($desc) {
        $html .= "<div class='g-form-desc'>{$desc}</div>";
    }
    
    $html .= '<div class="g-form-input-wrapper">';
    switch ($type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
            $valAttr = $existingValue !== null ? "value='" . htmlspecialchars($existingValue) . "'" : "";
            $html .= "<input type='{$type}' name='custom_{$name}' class='g-form-input' placeholder='Your answer' {$valAttr} {$required}>";
            break;
        case 'textarea':
            $valText = $existingValue !== null ? htmlspecialchars($existingValue) : "";
            $html .= "<textarea name='custom_{$name}' class='g-form-input' rows='2' placeholder='Your answer' style='resize: vertical;' {$required}>{$valText}</textarea>";
            break;
        case 'select':
            $html .= "<select name='custom_{$name}' class='g-form-select' {$required}>";
            $html .= "<option value=''>Choose</option>";
            if (!empty($field['options'])) {
                $opts = is_string($field['options']) ? json_decode($field['options'], true) : $field['options'];
                if (is_array($opts)) {
                    foreach ($opts as $opt) {
                        $opt_val = htmlspecialchars($opt);
                        $selected = ($existingValue !== null && $existingValue === $opt) ? 'selected' : '';
                        $html .= "<option value='{$opt_val}' {$selected}>{$opt_val}</option>";
                    }
                }
            }
            $html .= "</select>";
            break;
        case 'radio':
        case 'checkbox':
            $inputType = $type; // 'radio' or 'checkbox'
            $inputRequired = ($type === 'checkbox') ? '' : $required;
            
            $existingArray = [];
            if ($existingValue !== null) {
                $existingArray = is_array($existingValue) ? $existingValue : array_map('trim', explode(',', $existingValue));
            }
            
            if (!empty($field['options'])) {
                $opts = is_string($field['options']) ? json_decode($field['options'], true) : $field['options'];
                if (is_array($opts)) {
                    $html .= "<div style='display:flex; flex-direction:column; gap:0.75rem;'>";
                    foreach ($opts as $idx => $opt) {
                        $opt_val = htmlspecialchars($opt);
                        $id = "custom_{$name}_{$idx}";
                        // For checkboxes, name needs to be an array: custom_name[]
                        $inputName = $type === 'checkbox' ? "custom_{$name}[]" : "custom_{$name}";
                        $checked = in_array($opt, $existingArray) ? 'checked' : '';
                        $html .= "<label for='{$id}' class='g-form-radio-label'>";
                        $html .= "<input type='{$inputType}' name='{$inputName}' id='{$id}' value='{$opt_val}' {$inputRequired} {$checked}>";
                        $html .= "<span>{$opt_val}</span>";
                        $html .= "</label>";
                    }
                    $html .= "</div>";
                }
            }
            break;
        case 'file':
            $html .= "<input type='file' name='custom_{$name}' class='form-control' style='font-size: 0.875rem;' " . ($existingValue ? '' : $required) . ">";
            if ($existingValue) {
                $html .= "<small class='text-light mt-1' style='display:block;'>Current file: " . htmlspecialchars($existingValue) . " (Leave empty to keep current)</small>";
            }
            break;
    }
    $html .= '</div>'; // close input wrapper
    
    $html .= '</div>'; // close card
    return $html;
}
?>
