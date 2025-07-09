<?php
require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    
    echo "=== TEST CORRECTION CSR ===\n\n";
    
    echo "1. Test de la méthode columnExists corrigée...\n";
    $reflection = new ReflectionClass($collection);
    $method = $reflection->getMethod('columnExists');
    $method->setAccessible(true);
    
    $csrExists = $method->invoke($collection, 'my_collection', 'is_csr');
    echo "   columnExists('my_collection', 'is_csr'): " . ($csrExists ? 'TRUE' : 'FALSE') . "\n";
    
    echo "\n2. Test getMyCollection() avec la correction...\n";
    $results = $collection->getMyCollection();
    
    foreach ($results as $card) {
        if (strpos($card['name'], 'Diao') !== false && $card['rarity'] == 7) {
            echo "   - {$card['name']}: owned_foil={$card['owned_foil']}, owned_csr={$card['owned_csr']}, rarity={$card['rarity']}\n";
        }
    }
    
    echo "\n3. Test API directe...\n";
    $apiUrl = 'http://localhost/grand-archive-collection/api/cards.php?action=collection&name=Diao';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "   API fonctionne, résultats:\n";
            foreach ($data['data'] as $card) {
                if ($card['rarity'] == 7) {
                    echo "   - {$card['name']}: owned_csr={$card['owned_csr']}, rarity={$card['rarity']}\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>