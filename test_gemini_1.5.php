<?php
$geminiApiKey = 'AQ.Ab8RN6I09WngelpbDPKQSYxdpIizGZZvp2ANIdPa_474TBtLWA';
$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $geminiApiKey;

$postData = [
    "contents" => [
        ["role" => "user", "parts" => [["text" => "say hello"]]]
    ]
];

$ch = curl_init($geminiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
curl_close($ch);

echo "Response from gemini-1.5-flash:\n";
echo $response;
?>