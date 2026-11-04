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

// Na Renderu nastav Environment Variable s názvem OPENAI_API_KEY.
// API klíč zůstává na serveru a neposílá se do frontendu.
$apiKey = getenv("OPENAI_API_KEY")
    ?: ($_SERVER["OPENAI_API_KEY"] ?? ($_ENV["OPENAI_API_KEY"] ?? ""));

if ($apiKey === "") {
    sendJson(["error" => "Na serveru není nastavená proměnná OPENAI_API_KEY."], 500);
}

$endpoint = "https://api.openai.com/v1/responses";
$payload = [
    "model" => "gpt-4.1-mini",
    "input" => $message
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 60
    // SSL ověřování zůstává zapnuté (výchozí nastavení cURL).
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    sendJson(["error" => "Chyba při spojení s OpenAI API: " . $curlError], 502);
}

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $apiMessage = $data["error"]["message"] ?? "OpenAI API vrátilo chybu.";
    sendJson(["error" => "OpenAI API (HTTP " . $httpCode . "): " . $apiMessage], $httpCode);
}

// Responses API vrací text v output[].content[] jako output_text.
$replyText = "";
if (isset($data["output"]) && is_array($data["output"])) {
    foreach ($data["output"] as $item) {
        if (!isset($item["content"]) || !is_array($item["content"])) {
            continue;
        }
        foreach ($item["content"] as $content) {
            if (($content["type"] ?? "") === "output_text" && isset($content["text"])) {
                $replyText .= $content["text"];
            }
        }
    }
}

if ($replyText === "") {
    sendJson(["error" => "V odpovědi OpenAI se nepodařilo najít text."], 502);
}

sendJson(["reply" => $replyText]);
?>
