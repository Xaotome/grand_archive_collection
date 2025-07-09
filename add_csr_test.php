<?php
require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    $pdo = $db->getConnection();
    
    echo "=== AJOUT CARTE CSR TEST ===\n\n";
    
    // Prendre la carte Diao Chan qui est CSR (rareté 7)
    $editionUuid = 'ik31y7hrs9';
    
    echo "1. Vérification de la carte...\n";
    $stmt = $pdo->prepare("
        SELECT c.uuid as card_uuid, c.name, ce.uuid as edition_uuid, ce.rarity, ce.image 
        FROM card_editions ce 
        JOIN cards c ON ce.card_id = c.uuid 
        WHERE ce.uuid = ?
    ");
    $stmt->execute([$editionUuid]);
    $card = $stmt->fetch();
    
    if ($card) {
        echo "   Carte: " . $card['name'] . "\n";
        echo "   UUID carte: " . $card['card_uuid'] . "\n";
        echo "   UUID édition: " . $card['edition_uuid'] . "\n";
        echo "   Rareté: " . $card['rarity'] . "\n";
        echo "   Image: " . $card['image'] . "\n";
        
        $isCSR = $collection->isCardCSR($card['edition_uuid']);
        echo "   Détection CSR: " . ($isCSR ? 'OUI' : 'NON') . "\n\n";
        
        echo "2. Ajout à la collection (version normale)...\n";
        $collection->updateQuantity($card['card_uuid'], $card['edition_uuid'], false, 1);
        echo "   ✓ Carte normale ajoutée\n\n";
        
        echo "3. Ajout à la collection (version foil)...\n";
        $collection->updateQuantity($card['card_uuid'], $card['edition_uuid'], true, 1);
        echo "   ✓ Carte foil ajoutée\n\n";
        
        echo "4. Vérification en base...\n";
        $stmt = $pdo->prepare("
            SELECT *, 
                   CASE WHEN is_csr = 1 THEN 'CSR' 
                        WHEN is_foil = 1 THEN 'FOIL' 
                        ELSE 'NORMAL' END as type
            FROM my_collection 
            WHERE edition_uuid = ?
        ");
        $stmt->execute([$card['edition_uuid']]);
        $entries = $stmt->fetchAll();
        
        foreach ($entries as $entry) {
            echo "   - Quantité: {$entry['quantity']}, Type: {$entry['type']}\n";
        }
        
    } else {
        echo "   ❌ Carte non trouvée!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>