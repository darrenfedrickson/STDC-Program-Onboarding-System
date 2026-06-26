<?php
// user/chatbot_api.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';
$programId = isset($data['program_id']) ? (int)$data['program_id'] : 0;
$history = $data['history'] ?? []; // Array of {role: 'user'|'model', text: '...'}

if (!$userMessage) {
    echo json_encode(["error" => "Message missing."]);
    exit;
}

$systemInstruction = "";

if ($programId == 0) {
    // PHASE 1: PROGRAM SELECTION MODE
    $progsStmt = $pdo->query("SELECT id, title, description, capacity, (SELECT COUNT(*) FROM registrations r WHERE r.program_id = programs.id) as registered_count FROM programs WHERE status = 'active'");
    $programs = $progsStmt->fetchAll();
    
    $progSummary = [];
    foreach ($programs as $p) {
        if ($p['registered_count'] < $p['capacity']) {
            $progSummary[] = "- {$p['title']} (ID: {$p['id']}): {$p['description']}";
        }
    }
    
    $progText = empty($progSummary) ? "No programs are currently available." : implode("\n", $progSummary);
    
    $systemInstruction = "You are a friendly, conversational Program Discovery Assistant for STDC.
Your job is to help the user choose a program to apply for.

Here are the currently available programs:
{$progText}

YOUR INSTRUCTIONS:
1. Greet the user and ask what kind of program they are looking for, or present them with the available options.
2. Be helpful and answer any questions they have about the programs based ON THE DESCRIPTIONS PROVIDED ABOVE.
3. NEVER mention the program's internal ID in your conversational responses. It is for your internal use only when generating JSON.
4. Once the user clearly decides which program they want to apply for, you MUST output a raw JSON block.
   When outputting JSON, DO NOT output any other text or markdown.

The JSON MUST follow this exact structure:
{
  \"status\": \"program_selected\",
  \"program_id\": X
}
Replace X with the actual integer ID of the chosen program.

If the user hasn't made a final decision yet, just respond with normal conversational text.";

} else {
    // PHASE 2: REGISTRATION MODE
    $progStmt = $pdo->prepare("SELECT title, description FROM programs WHERE id = ?");
    $progStmt->execute([$programId]);
    $program = $progStmt->fetch();

    if (!$program) {
        echo json_encode(["error" => "Invalid program."]);
        exit;
    }

    $fieldsStmt = $pdo->prepare("SELECT id, name, label, type, description, required, options FROM program_fields WHERE program_id = ?");
    $fieldsStmt->execute([$programId]);
    $fields = $fieldsStmt->fetchAll();

    $fieldsSummary = [];
    foreach ($fields as $f) {
        if ($f['required']) {
            $desc = "Field Label: {$f['label']} (input_name: custom_{$f['name']}), Type: {$f['type']}";
            if ($f['options']) {
                $desc .= ", Options: {$f['options']}";
            }
            $fieldsSummary[] = "- " . $desc;
        }
    }
    $fieldsText = implode("\n", $fieldsSummary);

    $systemInstruction = "You are a friendly, conversational Registration Assistant for STDC programs.
You are helping the user register for the program: '{$program['title']}'.
Program Description: '{$program['description']}'.

The user must provide the following required information to complete the registration:
{$fieldsText}

YOUR INSTRUCTIONS:
1. Be friendly, encouraging, and helpful. Use a conversational tone.
2. Ask the user for the required information STRICTLY ONE question at a time. NEVER ask for more than one piece of information in a single message.
3. Keep track of what the user has already answered based on the conversation history.
4. If a field has 'Options' (like radio or select), clearly list the options for the user to pick from.
5. ALWAYS bold (**like this**) the name of the specific information you are asking for to make it stand out.
6. Once you have confidently collected ALL the required information, you MUST present a summary of the collected details to the user and ask them to confirm if everything is correct.
7. If the user wants to change any details, update them accordingly.
8. ONLY AFTER the user explicitly confirms that the summary is correct, you MUST output a raw JSON block containing the final collected data. 
   When outputting JSON, DO NOT output any other text, markdown, or greetings. ONLY output the raw JSON object.

The JSON MUST follow this exact structure:
{
  \"status\": \"complete\",
  \"data\": {
     \"custom_field_name_1\": \"user answer\",
     \"custom_field_name_2\": \"user answer\"
  }
}
Replace 'custom_field_name_1' with the actual input_name of the fields.

If you DO NOT have all the information yet, just respond with normal conversational text asking for the next missing piece of information. DO NOT output JSON until you have everything.";
}

// 3. Vertex AI Helper Function (Duplicated to keep this file standalone as requested)
function callVertexChat($postData, $accessToken, $projectId, $region) {
    $models = ['gemini-2.5-flash', 'gemini-1.5-flash-002', 'gemini-1.5-flash', 'gemini-1.0-pro'];
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if ($data !== null && !isset($data['error'])) {
            return $data;
        }
        $errors[$model] = $data['error'] ?? 'HTTP ' . $httpCode . ' cURL Error: ' . $curlErr;
    }
    return ['error' => ['message' => 'All models failed', 'details' => $errors]];
}

// 4. Authenticate with Vertex AI using ADC
$projectId = 'project-8273976d-fcb1-4538-a8c';
$region = 'us-central1';
$credFile = dirname(__DIR__) . '/application_default_credentials.json';

if (!file_exists($credFile)) {
    echo json_encode(["error" => "Authentication Missing: application_default_credentials.json not found."]);
    exit;
}

$creds = json_decode(file_get_contents($credFile), true);
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'client_id' => $creds['client_id'],
    'client_secret' => $creds['client_secret'],
    'refresh_token' => $creds['refresh_token'],
    'grant_type' => 'refresh_token'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenJson = json_decode($tokenResponse, true);
if (!isset($tokenJson['access_token'])) {
    echo json_encode(["error" => "Failed to get access token from Google."]);
    exit;
}
$accessToken = $tokenJson['access_token'];

// 5. Construct Conversation History for Gemini
$contents = [];
foreach ($history as $msg) {
    // skip system-level or structural messages if any
    $role = ($msg['role'] === 'user') ? 'user' : 'model';
    $contents[] = [
        "role" => $role,
        "parts" => [["text" => $msg['text']]]
    ];
}
// Add the current user message
$contents[] = [
    "role" => "user",
    "parts" => [["text" => $userMessage]]
];

$postData = [
    "systemInstruction" => [
        "role" => "system",
        "parts" => [
            ["text" => $systemInstruction]
        ]
    ],
    "contents" => $contents
];

$geminiData = callVertexChat($postData, $accessToken, $projectId, $region);

if (isset($geminiData['error'])) {
    echo json_encode(["error" => "API Error: " . json_encode($geminiData['error'])]);
    exit;
}

$rawAiResponse = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');
// Clean potential markdown blocks just in case
$cleanResponse = preg_replace('/^```(?:json|)?\s*|\s*```$/i', '', $rawAiResponse);
$cleanResponse = trim($cleanResponse);

// 6. Check if AI returned the JSON block indicating completion or selection
$parsedJson = json_decode($cleanResponse, true);
if ($parsedJson && isset($parsedJson['status'])) {
    if ($parsedJson['status'] === 'complete') {
        echo json_encode([
            "status" => "complete",
            "data" => $parsedJson['data']
        ]);
        exit;
    } else if ($parsedJson['status'] === 'program_selected') {
        echo json_encode([
            "status" => "program_selected",
            "program_id" => $parsedJson['program_id']
        ]);
        exit;
    }
}

// 7. Otherwise, return the conversational text
echo json_encode([
    "status" => "chat",
    "message" => $rawAiResponse
]);
