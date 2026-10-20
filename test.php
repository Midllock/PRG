<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$apiKey = "AQ.Ab8RN6JUNspU_sLaiu0g7mSV15naEb1hO0byFxSLKVdPwXUgBw";
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey;

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => "Ahoj, funguješ?"]]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h3>HTTP Kód od Googlu: $httpCode</h3>";

if ($error) {
    echo "<p style='color: red;'>Chyba cURL: $error</p>";
}

echo "<h3>Čistá odpověď serveru:</h3>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($response) . "</pre>";
echo "</div>";
?>