<?php
// admin/api/delete_chat.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = isset($data['session_id']) ? (int)$data['session_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$sessionId) {
    echo json_encode(["error" => "No session ID provided."]);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM ai_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo json_encode(["error" => "Session not found or unauthorized."]);
    exit;
}

// Delete the session. The `ON DELETE CASCADE` in MySQL will handle deleting the messages.
try {
    $stmt = $pdo->prepare("DELETE FROM ai_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    echo json_encode(["success" => true]);
} catch (\PDOException $e) {
    echo json_encode(["error" => "Failed to delete session: " . $e->getMessage()]);
}
?>
