<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$justTCGApiUrl = 'https://api.justtcg.com';
$justTCGApiKey = 'tcg_170ff302dbe74270a31655b1256fe621';

$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint manquant']);
    exit;
}

$url = $justTCGApiUrl . '/' . ltrim($endpoint, '/');

// Essayer d'abord avec cURL si disponible
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $justTCGApiKey,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError) {
        // Fallback vers file_get_contents si cURL échoue
        $useFallback = true;
    } else if ($httpCode === 200) {
        echo $response;
        exit;
    } else {
        // API accessible mais erreur (403, 429, etc.) - pas besoin de fallback
        http_response_code($httpCode);
        echo json_encode([
            'error' => 'JustTCG API error',
            'http_code' => $httpCode,
            'response' => substr($response, 0, 300)
        ]);
        exit;
    }
} else {
    $useFallback = true;
}

// Fallback avec file_get_contents si cURL n'est pas disponible ou a échoué
if (isset($useFallback)) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Authorization: Bearer ' . $justTCGApiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        http_response_code(503);
        echo json_encode([
            'error' => 'JustTCG API temporairement indisponible',
            'message' => 'Les prix ne peuvent pas être récupérés actuellement'
        ]);
        exit;
    }

    // Analyser les headers de réponse
    $statusLine = $http_response_header[0] ?? '';
    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
        $httpCode = (int)$matches[1];
        if ($httpCode === 200) {
            echo $response;
            exit;
        }
    }
    
    http_response_code(503);
    echo json_encode([
        'error' => 'JustTCG API error',
        'response' => substr($response, 0, 300)
    ]);
}
?>