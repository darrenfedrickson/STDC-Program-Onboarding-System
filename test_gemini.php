<?php
$geminiApiKey = 'AQ.Ab8RN6I09WngelpbDPKQSYxdpIizGZZvp2ANIdPa_474TBtLWA';
$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $geminiApiKey;

$systemInstruction = "You are an expert Text-to-SQL generator for a MySQL database managing a program registration system.
Your job is to convert the user's natural language question into a single valid MySQL SELECT query.
CRITICAL: You MUST ONLY output the raw SQL query. No markdown, no comments, no explanations. 
If the user asks conversational questions like 'is there any program?' or 'are there users?', you must return a SQL query that checks the data, such as SELECT COUNT(*) as count FROM programs;
If the user's query cannot be translated into a SELECT query for this schema AT ALL (e.g., 'how to bake a cake'), return EXACTLY the word UNSUPPORTED.

Database Schema:
Table: users (id INT, full_name VARCHAR, email VARCHAR, phone_number VARCHAR, role ENUM('admin', 'user', 'developer'), created_at TIMESTAMP)
Table: programs (id INT, title VARCHAR, capacity INT, status ENUM('active', 'closed'), created_at TIMESTAMP)
Table: program_fields (id INT, program_id INT, name VARCHAR, label VARCHAR, type VARCHAR)
Table: registrations (id INT, program_id INT, user_id INT, application_status ENUM('pending', 'shortlisted', 'approved', 'rejected'), created_at TIMESTAMP)
Table: registration_answers (id INT, registration_id INT, field_id INT, answer_value TEXT)

Important Rule: Always return column aliases if needed to make the result clear (e.g., SELECT count(*) as total_users). Return at most 50 rows (LIMIT 50).";

$postData = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemInstruction]
        ]
    ],
    "contents" => [
        ["role" => "user", "parts" => [["text" => "is there any program?"]]]
    ]
];

$ch = curl_init($geminiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
curl_close($ch);

echo "Response:\n";
echo $response;
echo "\n";
