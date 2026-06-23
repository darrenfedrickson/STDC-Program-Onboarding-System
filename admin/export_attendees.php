<?php
// admin/export_attendees.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if (!$program_id) {
    die("No program specified.");
}

// Fetch program info
$stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$program_id]);
$program = $stmt->fetch();

if (!$program) {
    die("Program not found.");
}

// Fetch fields
$schemaStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ? ORDER BY id ASC");
$schemaStmt->execute([$program_id]);
$schema = $schemaStmt->fetchAll();

// Fetch registrations
$regStmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, u.phone_number 
    FROM registrations r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.program_id = ? 
    ORDER BY r.created_at ASC
");
$regStmt->execute([$program_id]);
$registrations = $regStmt->fetchAll();

// Clean filename
$filename = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($program['title'])) . '_attendees_' . date('Ymd') . '.csv';

// Output headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Write BOM for Excel UTF-8 support
fputs($output, "\xEF\xBB\xBF");

// Write column headers
$headers = ['Full Name', 'Email Address', 'Phone Number', 'Status', 'Applied On'];
foreach ($schema as $field) {
    $headers[] = $field['label'];
}
fputcsv($output, $headers);

// Write data rows
foreach ($registrations as $reg) {
    $row = [
        $reg['full_name'],
        $reg['email'],
        $reg['phone_number'],
        ucfirst($reg['application_status']),
        date('Y-m-d H:i:s', strtotime($reg['created_at']))
    ];
    
    $ansStmt = $pdo->prepare("SELECT field_id, answer_value FROM registration_answers WHERE registration_id = ?");
    $ansStmt->execute([$reg['id']]);
    $rawAnswers = $ansStmt->fetchAll();
    $answers = [];
    foreach ($rawAnswers as $ans) {
        $answers[$ans['field_id']] = $ans['answer_value'];
    }
    
    foreach ($schema as $field) {
        $val = $answers[$field['id']] ?? '';
        // Replace newlines with spaces so it stays on one line in the CSV
        $val = str_replace(["\r\n", "\n", "\r"], ' ', $val);
        $row[] = $val;
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
