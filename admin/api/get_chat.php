<?php
// admin/api/get_chat.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json');

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$sessionId) {
    echo json_encode(["error" => "No session ID provided."]);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id, title FROM ai_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo json_encode(["error" => "Session not found or unauthorized."]);
    exit;
}

// Fetch messages
$stmt = $pdo->prepare("SELECT id, role, content, raw_data_json, chart_type FROM ai_messages WHERE session_id = ? ORDER BY id ASC");
$stmt->execute([$sessionId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "session" => $session,
    "messages" => $messages
]);
?>
