<?php
// Povolení CORS pro komunikaci s frontendem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($message)) {
    echo json_encode(["error" => "Dotaz je prázdný."]);
    exit;
}

// Zde je tvůj AQ. token
$apiKey = "AQ.Ab8RN6JUNspU_sLaiu0g7mSV15naEb1hO0byFxSLKVdPwXUgBw";
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent";

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => $message]]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// OPRAVA ZDE: Access tokeny se musí posílat jako Bearer
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $geminiData = json_decode($response, true);
    if (isset($geminiData['candidates'][0]['content']['parts'][0]['text'])) {
        $replyText = $geminiData['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(["reply" => $replyText]);
    } else {
        echo json_encode(["error" => "Chyba při čtení odpovědi od AI."]);
    }
} else {
    echo json_encode(["error" => "Chyba API. HTTP Kód: $httpCode. Detail: " . $response]);
}
?>