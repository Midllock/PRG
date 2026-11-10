<?php
// CORS pro komunikaci s frontendem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$message = isset($input["message"]) && is_string($input["message"])
    ? trim($input["message"])
    : "";

if ($message === "") {
    sendJson(["error" => "Dotaz je prázdný."], 400);
}

// Nastav Environment Variable s názvem GEMINI_API_KEY
$apiKey = getenv("GEMINI_API_KEY")
    ?: ($_SERVER["GEMINI_API_KEY"] ?? ($_ENV["GEMINI_API_KEY"] ?? ""));

if ($apiKey === "") {
    sendJson(["error" => "Na serveru není nastavená proměnná GEMINI_API_KEY."], 500);
}

// Endpoint pro nejnovější vlajkový model Gemini 4 Argon
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-4-flash:generateContent?key=" . $apiKey;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $message]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    // Zvýšený timeout na 15 minut (900 s) kvůli delší době zpracování složitějších dotazů u modelu Argon
    CURLOPT_TIMEOUT => 900
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    sendJson(["error" => "Chyba při spojení s Gemini API: " . $curlError], 502);
}

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $apiMessage = $data["error"]["message"] ?? "Gemini API vrátilo chybu.";
    sendJson(["error" => "Gemini API (HTTP " . $httpCode . "): " . $apiMessage], $httpCode);
}

$replyText = "";
if (isset($data["candidates"][0]["content"]["parts"][0]["text"])) {
    $replyText = $data["candidates"][0]["content"]["parts"][0]["text"];
}

if ($replyText === "") {
    sendJson(["error" => "V odpovědi Gemini se nepodařilo najít text."], 502);
}

sendJson(["reply" => $replyText]);
?>