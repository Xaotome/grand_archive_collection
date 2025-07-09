<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    $pdo = $db->getConnection();
    
    echo "=== DIAGNOSTIC PROBLÈME CSR ===\n\n";
    
    echo "1. Vérification colonne is_csr...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM my_collection LIKE 'is_csr'");
    $csrColumn = $stmt->fetch();
    if ($csrColumn) {
        echo "   ✓ Colonne is_csr existe\n";
    } else {
        echo "   ❌ Colonne is_csr manquante!\n";
        echo "   → Lancez /migrate_csr.php\n\n";
        return;
    }
    
    echo "\n2. Test direct de détection CSR...\n";
    // Prendre une carte rareté 7
    $stmt = $pdo->query("
        SELECT ce.uuid, c.name, ce.rarity, ce.image 
        FROM card_editions ce 
        JOIN cards c ON ce.card_id = c.uuid 
        WHERE ce.rarity = 7 
        LIMIT 1
    ");
    $testCard = $stmt->fetch();
    
    if ($testCard) {
        echo "   Carte test: " . $testCard['name'] . "\n";
        echo "   UUID: " . $testCard['uuid'] . "\n";
        echo "   Rareté: " . $testCard['rarity'] . "\n";
        echo "   Image: " . $testCard['image'] . "\n";
        
        // Test de la méthode isCardCSR
        $isCSR = $collection->isCardCSR($testCard['uuid']);
        echo "   Détection CSR: " . ($isCSR ? 'OUI' : 'NON') . "\n";
    } else {
        echo "   ❌ Aucune carte rareté 7 trouvée\n";
    }
    
    echo "\n3. Test de votre collection actuelle...\n";
    $myCollection = $collection->getMyCollection();
    echo "   Total cartes en collection: " . count($myCollection) . "\n";
    
    if (count($myCollection) > 0) {
        echo "   Exemple de carte:\n";
        $firstCard = $myCollection[0];
        foreach ($firstCard as $key => $value) {
            echo "     $key: $value\n";
        }
        
        echo "\n   Recherche de cartes CSR dans votre collection...\n";
        $csrCount = 0;
        foreach ($myCollection as $card) {
            if (isset($card['owned_csr']) && $card['owned_csr']) {
                $csrCount++;
                echo "     - " . $card['name'] . " (CSR)\n";
            }
        }
        echo "   Cartes CSR trouvées: $csrCount\n";
    }
    
    echo "\n4. Vérification base de données...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM my_collection WHERE is_csr = 1");
    $csrInDB = $stmt->fetch()['count'];
    echo "   Cartes CSR en base: $csrInDB\n";
    
    echo "\n5. Test de mise à jour manuelle...\n";
    // Chercher une carte rareté 7 dans votre collection
    $stmt = $pdo->query("
        SELECT mc.*, c.name, ce.rarity 
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE ce.rarity = 7
        LIMIT 1
    ");
    $csrInCollection = $stmt->fetch();
    
    if ($csrInCollection) {
        echo "   Carte CSR dans votre collection: " . $csrInCollection['name'] . "\n";
        echo "   is_csr actuel: " . ($csrInCollection['is_csr'] ? 'TRUE' : 'FALSE') . "\n";
        
        if (!$csrInCollection['is_csr']) {
            echo "   → Mise à jour manuelle...\n";
            $stmt = $pdo->prepare("UPDATE my_collection SET is_csr = 1 WHERE id = ?");
            $stmt->execute([$csrInCollection['id']]);
            echo "   ✓ Carte mise à jour\n";
        }
    } else {
        echo "   Aucune carte CSR dans votre collection\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>