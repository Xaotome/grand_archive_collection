<?php
require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    
    echo "=== DIAGNOSTIC FRONTEND CSR ===\n\n";
    
    echo "1. Données brutes de la collection...\n";
    $myCollection = $collection->getMyCollection();
    
    foreach ($myCollection as $card) {
        if (strpos($card['name'], 'Diao') !== false) {
            echo "   Carte: {$card['name']}\n";
            echo "   owned_foil: {$card['owned_foil']}\n";
            echo "   owned_csr: {$card['owned_csr']}\n";
            echo "   edition_uuid: {$card['edition_uuid']}\n";
            echo "   rarity: {$card['rarity']}\n";
            echo "   image: {$card['image']}\n";
            echo "   ---\n";
        }
    }
    
    echo "\n2. Test API directe...\n";
    // Simuler l'appel API
    $_GET = ['action' => 'getCollection'];
    ob_start();
    include 'api/collection.php';
    $apiResponse = ob_get_clean();
    
    echo "   Réponse API (premiers 500 caractères):\n";
    echo "   " . substr($apiResponse, 0, 500) . "...\n";
    
    // Parser la réponse JSON
    $data = json_decode($apiResponse, true);
    if ($data && isset($data['data'])) {
        echo "\n3. Analyse des données API...\n";
        foreach ($data['data'] as $card) {
            if (strpos($card['name'], 'Diao') !== false) {
                echo "   Carte: {$card['name']}\n";
                echo "   owned_foil: " . (isset($card['owned_foil']) ? $card['owned_foil'] : 'NON DÉFINI') . "\n";
                echo "   owned_csr: " . (isset($card['owned_csr']) ? $card['owned_csr'] : 'NON DÉFINI') . "\n";
                echo "   ---\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>