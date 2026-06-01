<?php

// Question ----> generate embedding----> try comm (top 3 chunk)

$gooleapiKey='';
$question='What is office address';
function generateEmbedding($text, $apiKey)
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key=".$apiKey;

    $data = [
        "content" => [
            "parts" => [
                [
                    "text" => $text
                ]
            ]
        ]
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    curl_close($ch);

    $result = json_decode($response, true);
   
    return $result['embedding']['values'];
}


$queryEmbedding = generateEmbedding(
   $question,
    $gooleapiKey
);

// credentials related try croma 
$apiKey  = "";
$tenant  = "";
$database = "";
$collectionId = "";
$url = "https://api.trychroma.com/api/v2/tenants/$tenant/databases/$database/collections/$collectionId/query";
$data = [

    "query_embeddings" => [
        array_map('floatval', $queryEmbedding)
    ],
    "n_results" => 3,
    "include" => ["documents", "metadatas", "distances"]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Chroma-Token: $apiKey",
    "X-Chroma-Tenant: $tenant",
    "X-Chroma-Database: $database",
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

curl_close($ch);

$result = json_decode($response, true);
$chunks = $result['documents'][0] ?? [];

/*
|--------------------------------------------------------------------------
| STEP 5 : CREATE CONTEXT
|--------------------------------------------------------------------------
*/

 $context = implode("\n", $chunks);




$prompt = "

Answer the question using the provided context.
If the context does not contain the answer, use web knowledge.
Return only the exact and concise answer.

Context:
$context

Question:
$question

";

/*
|--------------------------------------------------------------------------
| STEP 7 : CLAUDE HEADERS
|--------------------------------------------------------------------------
*/

function anthropicHeaders($apiKey)
{
    return [
        "Content-Type: application/json",
        "x-api-key: " . $apiKey,
        "anthropic-version: 2023-06-01"
    ];
}

/*
|--------------------------------------------------------------------------
| STEP 8 : CALL CLAUDE LLM
|--------------------------------------------------------------------------
*/

$model = "claude-sonnet-4-6";
$apiUrl = "https://api.anthropic.com/v1/messages";

$payload = [
    'model' => $model,
    'max_tokens' => 1024,
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt,
        ],
    ],
];

$ch = curl_init($apiUrl);
if ($ch === false) {
    die("Failed to initialize CURL.");
}
$headers = anthropicHeaders('anthopic api key here');
curl_setopt_array($ch, [

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POST => true,

    CURLOPT_HTTPHEADER => $headers,

    CURLOPT_POSTFIELDS => json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
    ),

    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);

$curlErr = curl_error($ch);

$statusCode = (int) curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

if ($curlErr) {

    die($curlErr);
}

$result = json_decode($response, true);

/*
|--------------------------------------------------------------------------
| STEP 9 : FINAL ANSWER
|--------------------------------------------------------------------------
*/

echo $answer = $result['content'][0]['text'] ?? 'No answer found';




