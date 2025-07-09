<?php
require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    $pdo = $db->getConnection();
    
    echo "=== TEST SIMPLE CSR ===\n\n";
    
    // Test avec la carte Diao Chan
    $editionUuid = 'ik31y7hrs9';
    echo "Test avec Diao Chan (UUID: $editionUuid):\n";
    
    $isCSR = $collection->isCardCSR($editionUuid);
    echo "Détection CSR: " . ($isCSR ? 'OUI' : 'NON') . "\n\n";
    
    // Vérifier les données brutes
    $stmt = $pdo->prepare("SELECT rarity, image FROM card_editions WHERE uuid = ?");
    $stmt->execute([$editionUuid]);
    $card = $stmt->fetch();
    
    if ($card) {
        echo "Données brutes:\n";
        echo "- Rareté: " . $card['rarity'] . "\n";
        echo "- Image: " . $card['image'] . "\n";
        echo "- Rareté == 7: " . ($card['rarity'] == 7 ? 'OUI' : 'NON') . "\n";
        echo "- Image contient -csr: " . (strpos($card['image'], '-csr') !== false ? 'OUI' : 'NON') . "\n";
    } else {
        echo "Carte non trouvée!\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>