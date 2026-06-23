<?php
// admin/api_templates.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all templates
    $stmt = $pdo->query("SELECT id, name, schema_json FROM form_templates ORDER BY created_at DESC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $templates]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save new template
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (empty($data['name']) || empty($data['schema_json'])) {
        echo json_encode(['status' => 'error', 'message' => 'Name and schema are required.']);
        exit();
    }
    
    // Validate schema
    $schemaObj = json_decode($data['schema_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid schema JSON.']);
        exit();
    }
    
    $name = sanitizeInput($data['name']);
    
    $stmt = $pdo->prepare("INSERT INTO form_templates (name, schema_json) VALUES (?, ?)");
    if ($stmt->execute([$name, $data['schema_json']])) {
        echo json_encode(['status' => 'success', 'message' => 'Template saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save template to database.']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
