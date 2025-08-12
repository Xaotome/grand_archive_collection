<?php
// Headers CORS les plus permissifs
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration JustTCG
$apiKey = 'tcg_170ff302dbe74270a31655b1256fe621';
$baseUrl = 'api.justtcg.com/v1';

// Récupérer l'endpoint
$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
    echo json_encode(['error' => 'Endpoint manquant']);
    exit;
}

$fullUrl = $baseUrl . '/' . ltrim($endpoint, '/');

// Essayer avec différentes méthodes
$response = null;

// Méthode 1: cURL (si disponible)
if (function_exists('curl_init') && !$response) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $apiKey,
            'User-Agent: Mozilla/5.0 (compatible; PHP-Proxy/1.0)',
            'Accept: application/json'
        ]
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!$error && $httpCode === 200 && $result) {
        $response = $result;
    }
}

// Méthode 2: file_get_contents (si allow_url_fopen est activé)
if (!$response && ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer " . $apiKey . "\r\n" .
                       "User-Agent: Mozilla/5.0 (compatible; PHP-Proxy/1.0)\r\n" .
                       "Accept: application/json\r\n",
            'timeout' => 20
        ]
    ]);
    
    $result = @file_get_contents($fullUrl, false, $context);
    if ($result !== false) {
        $response = $result;
    }
}

// Si on a une réponse, la retourner
if ($response) {
    echo $response;
} else {
    // Fallback: retourner des données vides plutôt qu'une erreur
    echo json_encode([
        'cards' => [],
        'message' => 'JustTCG API temporairement indisponible',
        'debug' => [
            'curl_available' => function_exists('curl_init'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'endpoint_requested' => $endpoint
        ]
    ]);
}
?>