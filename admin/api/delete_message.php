<?php
// admin/api/delete_message.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$messageId = isset($data['message_id']) ? (int)$data['message_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$messageId) {
    echo json_encode(["error" => "No message ID provided."]);
    exit;
}

// Verify ownership of the message
$stmt = $pdo->prepare("
    SELECT m.id 
    FROM ai_messages m
    JOIN ai_sessions s ON m.session_id = s.id
    WHERE m.id = ? AND s.user_id = ?
");
$stmt->execute([$messageId, $userId]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["error" => "Message not found or unauthorized."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM ai_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    echo json_encode(["success" => true]);
} catch (\PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
