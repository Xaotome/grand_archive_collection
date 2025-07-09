<?php
require_once 'config/database.php';
require_once 'classes/Collection.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $collection = new Collection($db);
    
    echo "=== DEBUG getMyCollection ===\n\n";
    
    echo "1. Test getMyCollection() sans filtres...\n";
    $results = $collection->getMyCollection();
    
    echo "   Nombre total de résultats: " . count($results) . "\n";
    
    foreach ($results as $card) {
        if (strpos($card['name'], 'Diao') !== false && $card['rarity'] == 7) {
            echo "   - {$card['name']}: owned_foil={$card['owned_foil']}, owned_csr={$card['owned_csr']}, rarity={$card['rarity']}\n";
        }
    }
    
    echo "\n2. Test getMyCollection() avec filtre nom...\n";
    $results = $collection->getMyCollection(['name' => 'Diao']);
    
    echo "   Nombre de résultats filtrés: " . count($results) . "\n";
    
    foreach ($results as $card) {
        echo "   - {$card['name']}: owned_foil={$card['owned_foil']}, owned_csr={$card['owned_csr']}, rarity={$card['rarity']}\n";
    }
    
    echo "\n3. Debug de la méthode columnExists...\n";
    $reflection = new ReflectionClass($collection);
    $method = $reflection->getMethod('columnExists');
    $method->setAccessible(true);
    
    $csrExists = $method->invoke($collection, 'my_collection', 'is_csr');
    echo "   columnExists('my_collection', 'is_csr'): " . ($csrExists ? 'TRUE' : 'FALSE') . "\n";
    
    echo "\n4. Test direct avec instance Collection...\n";
    $pdo = $db->getConnection();
    
    // Test direct de la méthode columnExists
    $stmt = $pdo->query("SHOW COLUMNS FROM my_collection LIKE 'is_csr'");
    $directResult = $stmt->fetch();
    echo "   Test direct SHOW COLUMNS: " . ($directResult ? 'TRUE' : 'FALSE') . "\n";
    
    // Test de fetch vs fetchAll
    $stmt = $pdo->prepare("SELECT * FROM my_collection WHERE edition_uuid = 'ik31y7hrs9' LIMIT 1");
    $stmt->execute();
    $testRow = $stmt->fetch();
    
    if ($testRow) {
        echo "   Test row: is_csr=" . (isset($testRow['is_csr']) ? $testRow['is_csr'] : 'NON DÉFINI') . "\n";
        echo "   Toutes les colonnes disponibles: " . implode(', ', array_keys($testRow)) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>