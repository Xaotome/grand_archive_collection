<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test des solutions alternatives</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie</p>";
    
    // Test 1: LIKE avec le bon format
    echo "<h2>1. Test LIKE avec format correct</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.classes LIKE ?
            LIMIT 5
        ");
        $stmt->execute(['%"MAGE"%']);
        echo "<p>Résultats LIKE pour MAGE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur LIKE: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: JSON_SEARCH avec le bon format
    echo "<h2>2. Test JSON_SEARCH avec format correct</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_SEARCH(c.classes, 'one', ?) IS NOT NULL
            LIMIT 5
        ");
        $stmt->execute(['MAGE']);
        echo "<p>Résultats JSON_SEARCH pour MAGE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_SEARCH: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: JSON_CONTAINS avec le bon format
    echo "<h2>3. Test JSON_CONTAINS avec format correct</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_CONTAINS(c.classes, JSON_QUOTE(?))
            LIMIT 5
        ");
        $stmt->execute(['MAGE']);
        echo "<p>Résultats JSON_CONTAINS pour MAGE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_CONTAINS: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Test avec tous les types de classes
    echo "<h2>4. Test avec toutes les classes trouvées</h2>";
    $classes = ['MAGE', 'CLERIC', 'TAMER'];
    
    foreach ($classes as $class) {
        echo "<h3>Test pour {$class}</h3>";
        
        // Test LIKE
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.classes LIKE ?
        ");
        $stmt->execute(['%"' . $class . '"%']);
        $like_count = $stmt->fetch()['count'];
        
        // Test JSON_SEARCH
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_SEARCH(c.classes, 'one', ?) IS NOT NULL
        ");
        $stmt->execute([$class]);
        $json_search_count = $stmt->fetch()['count'];
        
        echo "<p>{$class}: LIKE = {$like_count}, JSON_SEARCH = {$json_search_count}</p>";
    }
    
    // Test 5: Test avec les éléments
    echo "<h2>5. Test filtrage par élément</h2>";
    $stmt = $pdo->prepare("
        SELECT c.name, c.element
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.element = ?
        LIMIT 5
    ");
    $stmt->execute(['NORM']);
    echo "<p>Résultats pour élément NORM: " . $stmt->rowCount() . "</p>";
    while ($row = $stmt->fetch()) {
        echo "<p>- {$row['name']}: {$row['element']}</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>