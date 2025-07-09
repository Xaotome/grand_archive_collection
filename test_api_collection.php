<?php
header('Content-Type: text/plain');

echo "=== TEST API COLLECTION ===\n\n";

echo "1. Test direct de l'API collection...\n";
$apiUrl = 'http://localhost/grand-archive-collection/api/cards.php?action=collection';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($apiUrl, false, $context);
if ($response === false) {
    echo "   ❌ Erreur lors de l'appel API\n";
} else {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "   ✓ API répond correctement\n";
        echo "   Nombre de cartes: " . count($data['data']) . "\n\n";
        
        echo "2. Recherche de Diao Chan dans les résultats...\n";
        foreach ($data['data'] as $card) {
            if (strpos($card['name'], 'Diao') !== false) {
                echo "   Carte trouvée: {$card['name']}\n";
                echo "   owned_foil: " . (isset($card['owned_foil']) ? $card['owned_foil'] : 'NON DÉFINI') . "\n";
                echo "   owned_csr: " . (isset($card['owned_csr']) ? $card['owned_csr'] : 'NON DÉFINI') . "\n";
                echo "   rarity: {$card['rarity']}\n";
                echo "   ---\n";
            }
        }
    } else {
        echo "   ❌ Erreur API: " . ($data['error'] ?? 'Réponse invalide') . "\n";
        echo "   Réponse brute: $response\n";
    }
}

echo "\n3. Test avec filtrage de nom...\n";
$apiUrl = 'http://localhost/grand-archive-collection/api/cards.php?action=collection&name=Diao';
$response = file_get_contents($apiUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "   Résultats filtrés: " . count($data['data']) . "\n";
        foreach ($data['data'] as $card) {
            echo "   - {$card['name']}: owned_csr=" . (isset($card['owned_csr']) ? $card['owned_csr'] : 'N/D') . "\n";
        }
    }
}

echo "\n=== FIN TEST ===\n";
?>