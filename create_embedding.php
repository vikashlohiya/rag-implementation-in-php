<?php

// https://aistudio.google.com/api-keys 
$gooleapiKey='';

$text = file_get_contents("company_rag_sample.txt");

function createChunks($text, $chunkSize = 100)
{
    return str_split($text, $chunkSize);
}

$chunks = createChunks($text);
$ids = []; $documents = []; $embeddings = [];
foreach ($chunks as $index => $chunk) {
    $ids[] = uniqid("doc_" . $index);
    $documents[] = $chunk;
    $embeddings[] = generateEmbedding($chunk, $gooleapiKey);
}
$data = ["ids" => $ids, "documents" => $documents, "embeddings" => $embeddings];

// credentials related try croma 
$apiKey  = "";
$tenant  = "";
$database = "";
$collectionId = "";
$url = "https://api.trychroma.com/api/v2/tenants/$tenant/databases/$database/collections/$collectionId/add";


$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-chroma-token: $apiKey",
    "X-Chroma-Tenant: $tenant",
    "X-Chroma-Database: $database",
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);
echo $response;

// google api to generate embedding
function generateEmbedding($text, $apiKey){
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

$embedding = $result['embedding']['values'];
return $embedding;

}

