<?php
$projectId = 'project-8273976d-fcb1-4538-a8c';
$region = 'us-central1';
$credFile = __DIR__ . '/application_default_credentials.json';

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
$accessToken = $tokenJson['access_token'];

$postData = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => "Respond with a valid JSON object: {\"status\": \"ok\"}"]]
        ]
    ]
];

$model = 'gemini-1.5-flash';
$url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$res = curl_exec($ch);
curl_close($ch);

echo $res;
