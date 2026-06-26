<?php
// admin/ai_query.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Ensure only admins can access this endpoint
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userPrompt = $data['prompt'] ?? '';
$sessionId = !empty($data['session_id']) ? (int) $data['session_id'] : null;
$selectedModel = $data['model'] ?? 'auto';

if (!$userPrompt) {
    echo json_encode(["error" => "No prompt provided."]);
    exit;
}

$userId = $_SESSION['user_id'];
if (!$sessionId) {
    $title = strlen($userPrompt) > 50 ? substr($userPrompt, 0, 47) . '...' : $userPrompt;
    $stmt = $pdo->prepare("INSERT INTO ai_sessions (user_id, title) VALUES (?, ?)");
    $stmt->execute([$userId, $title]);
    $sessionId = $pdo->lastInsertId();
}

// Save user message
$stmt = $pdo->prepare("INSERT INTO ai_messages (session_id, role, content) VALUES (?, 'user', ?)");
$stmt->execute([$sessionId, $userPrompt]);
$userMsgId = $pdo->lastInsertId();

// 2. Define the System Prompt & Schema for STDC-Program-Onboarding-System
// Extract all custom field names from the database so the AI knows they exist
$fieldsQuery = $pdo->query("SELECT DISTINCT name, label FROM program_fields");
$customFields = [];
while ($row = $fieldsQuery->fetch(PDO::FETCH_ASSOC)) {
    $customFields[] = $row['name'] . " (" . $row['label'] . ")";
}
$fieldsString = implode(", ", $customFields);

$systemInstruction = "You are an AI data analyst built into the STDC Program Registration dashboard.
Your job is to translate user questions into valid MySQL queries.

Here is the database schema:
" . file_get_contents(dirname(__DIR__) . '/setup.sql') . "

CRITICAL KNOWLEDGE:
The database uses an EAV (Entity-Attribute-Value) pattern for custom registration fields!
The `program_fields` table contains the following dynamic fields that users answer when registering:
[" . $fieldsString . "]

If the user asks about ANY of those fields (like 'gender' or 'age'), you MUST join the `registration_answers` table and the `program_fields` table!
Example: `SELECT ra.answer_value as Gender, COUNT(*) FROM registration_answers ra JOIN program_fields pf ON ra.field_id = pf.id WHERE pf.name = 'gender' GROUP BY ra.answer_value`

RULES:
1. ONLY return the JSON. No markdown, no explanations, no chat.
The JSON must contain exactly four keys:
1. \"sql\": The raw MySQL SELECT query. (If the prompt is completely unrelated to this database, return \"UNSUPPORTED\" here. Otherwise, ALWAYS return a valid SQL query even if the requested chart seems strange or lacks obvious numeric axes).
2. \"chart_type\": The type of chart the user specifically asked for (e.g., \"bar\", \"horizontalBar\", \"line\", \"pie\", \"doughnut\", \"scatter\", \"radar\", \"polarArea\", \"bubble\"). If the user did not specify a chart type, return \"auto\". Note: For 'bubble' charts, your SQL MUST return exactly 3 numeric columns representing X, Y, and Radius (size) in that order, plus an optional 4th label column.
3. \"chart_title\": A short, descriptive title for the chart based on the prompt. Return null if not applicable.
4. \"is_stacked\": true or false. Set to true ONLY if the user asks for a stacked chart or comparing multiple overlapping groups. If true, your SQL MUST return exactly 3 columns: XAxis_Label, Group_Label, and Numeric_Value.

Table: users (id INT, full_name VARCHAR, email VARCHAR, phone_number VARCHAR, role ENUM('admin', 'user', 'developer'), created_at TIMESTAMP)
Table: programs (id INT, title VARCHAR, capacity INT, status ENUM('active', 'closed'), created_at TIMESTAMP)
Table: program_fields (id INT, program_id INT, name VARCHAR, label VARCHAR, type VARCHAR)
Table: registrations (id INT, program_id INT, user_id INT, application_status ENUM('pending', 'shortlisted', 'approved', 'rejected'), created_at TIMESTAMP)
Table: registration_answers (id INT, registration_id INT, field_id INT, answer_value TEXT)

Important Rule: Always return column aliases if needed to make the result clear (e.g., SELECT count(*) as total_users). Return at most 50 rows (LIMIT 50).
";

// Helper function to call Vertex AI with automatic model fallback
function callGeminiWithFallback($postData, $accessToken, $projectId, $region, $preferredModel = 'auto')
{
    $validModels = [
        'gemini-2.5-flash',
        'gemini-1.5-flash-002',
        'gemini-1.5-flash',
        'gemini-1.0-pro'
    ];

    $models = $validModels;
    if ($preferredModel !== 'auto' && in_array($preferredModel, $validModels)) {
        $models = array_diff($validModels, [$preferredModel]);
        array_unshift($models, $preferredModel);
    }
    
    // Only try a maximum of 3 models to prevent huge delays if the API is down
    $models = array_slice($models, 0, 3);

    $errors = [];
    foreach ($models as $model) {
        $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Expect:'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Give Gemini more time to think
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if ($data === null) {
            $errors[$model] = 'cURL/JSON fail. HTTP: ' . $httpCode . ' | cURL Error: ' . $curlErr . ' | Raw Res: ' . $res;
            continue;
        }
        if (!isset($data['error'])) {
            return $data; // Success!
        }
        
        $errors[$model] = $data['error'];
        
        // If it's a 400 Bad Request, falling back probably won't help, but we'll collect it anyway
        // Or if it's a 400, maybe we SHOULD just return immediately? Let's return the collected errors so we can debug.
    }
    return ['error' => ['message' => 'All models failed in fallback loop.', 'details' => $errors]];
}

// 3. Authenticate with Vertex AI using ADC
$projectId = 'project-8273976d-fcb1-4538-a8c';
$region = 'us-central1';
$credFile = dirname(__DIR__) . '/application_default_credentials.json';

if ($projectId === 'ENTER_YOUR_PROJECT_ID_HERE') {
    echo json_encode(["error" => "Setup Incomplete: Please open admin/ai_query.php and replace ENTER_YOUR_PROJECT_ID_HERE with your Google Cloud Project ID."]);
    exit;
}

if (!file_exists($credFile)) {
    echo json_encode(["error" => "Authentication Missing: The application_default_credentials.json file was not found in your project folder. Please run the setup_adc.sh command and copy the file as instructed."]);
    exit;
}

$creds = json_decode(file_get_contents($credFile), true);
if (!isset($creds['refresh_token'])) {
    echo json_encode(["error" => "Invalid credentials file: missing refresh_token."]);
    exit;
}

// Exchange refresh_token for an access_token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'client_id' => $creds['client_id'],
    'client_secret' => $creds['client_secret'],
    'refresh_token' => $creds['refresh_token'],
    'grant_type' => 'refresh_token'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenJson = json_decode($tokenResponse, true);
if (!isset($tokenJson['access_token'])) {
    echo json_encode(["error" => "Failed to get access token from Google. Are your credentials expired? Try running the setup_adc.sh command again."]);
    exit;
}
$accessToken = $tokenJson['access_token'];

$systemInstruction .= "\nYou will be provided with the conversation history. Use it to understand follow-up questions (e.g., 'sure', 'what about X'). Regardless of the history, your current response MUST be ONLY the JSON object containing the SQL query.";

$contents = [];
$stmt = $pdo->prepare("SELECT role, content FROM ai_messages WHERE session_id = ? ORDER BY id ASC");
$stmt->execute([$sessionId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg) {
    $role = ($msg['role'] === 'user') ? 'user' : 'model';
    $contents[] = [
        "role" => $role,
        "parts" => [["text" => $msg['content']]]
    ];
}

$postData = [
    "systemInstruction" => [
        "role" => "system",
        "parts" => [
            ["text" => $systemInstruction]
        ]
    ],
    "contents" => $contents
];

$geminiData = callGeminiWithFallback($postData, $accessToken, $projectId, $region, $selectedModel);

// Handle API level errors (like 503 High Demand or 429 Rate Limit)
if (isset($geminiData['error'])) {
    echo json_encode(["error" => "API Error: " . json_encode($geminiData['error'])]);
    exit;
}

$rawAiResponse = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');
$rawAiResponse = preg_replace('/^```(?:json|)?\s*|\s*```$/i', '', $rawAiResponse);
$rawAiResponse = trim($rawAiResponse);

if (empty($rawAiResponse) || $rawAiResponse === '{}') {
    $debugData = json_encode($geminiData);
    echo json_encode(["error" => "AI failed to return valid JSON. Response was: " . $rawAiResponse . " | Debug Data: " . $debugData]);
    exit;
}

$parsedAi = json_decode($rawAiResponse, true);

if (!$parsedAi || !isset($parsedAi['sql'])) {
    echo json_encode(["error" => "AI failed to return valid JSON. Response was: " . $rawAiResponse]);
    exit;
}

$sqlQuery = trim($parsedAi['sql']);
$chartType = $parsedAi['chart_type'] ?? 'auto';
$chartTitle = $parsedAi['chart_title'] ?? null;
$isStacked = $parsedAi['is_stacked'] ?? false;

if ($sqlQuery === 'UNSUPPORTED' || empty($sqlQuery)) {
    echo json_encode([
        "unsupported" => "I can only answer questions related to the system's data (users, programs, registrations)."
    ]);
    exit;
}

// Security Check: Ensure it's a SELECT query to prevent destructive actions
if (stripos($sqlQuery, 'SELECT') !== 0) {
    echo json_encode(["error" => "Security error: Only SELECT queries are allowed."]);
    exit;
}

// 4. Run the SQL Query
try {
    $stmt = $pdo->query($sqlQuery);
    $dataArray = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $aiMessage = "";
    $prompt2 = "The user asked: '{$userPrompt}'. The database returned this data: " . json_encode($dataArray) . ". Write a short, natural chatbot response answering the user's question based on this data. At the end of your response, suggest 1 or 2 interesting follow-up prompts the user could ask to explore this data further. Keep it friendly and concise. Do NOT use any markdown formatting (no bold **, no italics *). Do not include raw JSON or technical SQL terms.";

    $contents2 = $contents;
    $lastIndex = count($contents2) - 1;
    $contents2[$lastIndex]['parts'][0]['text'] = $prompt2;

    $postData2 = [
        "contents" => $contents2
    ];

    $gem2Data = callGeminiWithFallback($postData2, $accessToken, $projectId, $region, $selectedModel);
    $aiMessage = trim($gem2Data['candidates'][0]['content']['parts'][0]['text'] ?? "Here is the data you requested.");

    // Strip markdown asterisks in case the AI ignores the prompt
    $aiMessage = str_replace(['**', '*'], '', $aiMessage);

    // Save AI response
    $payload = [
        "data" => $dataArray,
        "chartTitle" => $chartTitle,
        "isStacked" => $isStacked
    ];
    $stmt = $pdo->prepare("INSERT INTO ai_messages (session_id, role, content, raw_data_json, chart_type) VALUES (?, 'ai', ?, ?, ?)");
    $stmt->execute([$sessionId, $aiMessage, json_encode($payload), $chartType]);
    $aiMsgId = $pdo->lastInsertId();

    // Update session
    $pdo->exec("UPDATE ai_sessions SET updated_at = NOW() WHERE id = $sessionId");

    echo json_encode([
        "session_id" => $sessionId,
        "user_msg_id" => $userMsgId,
        "ai_msg_id" => $aiMsgId,
        "message" => $aiMessage,
        "data" => $dataArray,
        "chartType" => $chartType,
        "chartTitle" => $chartTitle,
        "isStacked" => $isStacked
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        "error" => "Failed to execute generated query.",
        "sql" => $sqlQuery,
        "db_error" => $e->getMessage()
    ]);
}
?>