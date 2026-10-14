<?php
// api/support.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Tvůj API klíč
$apiKey = "AQ.Ab8RN6JUNspU_sLaiu0g7mSV15naEb1hO0byFxSLKVdPwXUgBw";

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(["error" => "Nebyl zadán žádný dotaz."]);
    exit();
}

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey;

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "Jsi zákaznická a technická podpora pro web a e-shop. Odpověz stručně, srozumitelně a česky: " . $message]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Expect:" // Klíčové pro hladký běh na lokálním serveru
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(["error" => "Chyba připojení ze serveru: " . $curlError]);
    exit();
}

$responseData = json_decode($response, true);

if ($httpCode === 200 && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        "status" => "success",
        "reply"  => $responseData['candidates'][0]['content']['parts'][0]['text']
    ]);
} else {
    // Vlastní zpracování 503 přetížení
    if ($httpCode === 503) {
        $errorMsg = "Síť podpory je momentálně extrémně vytížená. Zkuste prosím odeslat dotaz za pár minut znovu.";
    } else {
        $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : "Neznámá chyba API.";
    }
    
    http_response_code(500);
    echo json_encode(["error" => $errorMsg]);
}
?>