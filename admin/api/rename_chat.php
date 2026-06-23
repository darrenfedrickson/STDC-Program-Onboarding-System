<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = $data['session_id'] ?? null;
$newTitle = $data['title'] ?? null;
$userId = $_SESSION['user_id'];

if (!$sessionId || !$newTitle) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    // Only allow updating if the session belongs to the user
    $stmt = $pdo->prepare("UPDATE ai_sessions SET title = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newTitle, $sessionId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Session not found or not owned by user']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
