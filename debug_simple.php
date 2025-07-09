<?php
// Script simple pour diagnostiquer la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Base de Données</h1>";

// Test de connexion basique
try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie à la base de données</p>";
    
    // Test 1: Vérifier si la table cards existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'cards'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table 'cards' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Table 'cards' n'existe pas</p>";
    }
    
    // Test 2: Vérifier si la table my_collection existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'my_collection'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table 'my_collection' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Table 'my_collection' n'existe pas</p>";
    }
    
    // Test 3: Compter les cartes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cards");
    $result = $stmt->fetch();
    echo "<p>Nombre total de cartes: <strong>{$result['count']}</strong></p>";
    
    // Test 4: Compter ma collection
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM my_collection");
    $result = $stmt->fetch();
    echo "<p>Nombre d'éléments dans ma collection: <strong>{$result['count']}</strong></p>";
    
    // Test 5: Examiner quelques cartes et leurs classes
    echo "<h2>Exemples de cartes et leurs classes:</h2>";
    $stmt = $pdo->query("SELECT name, classes, element FROM cards LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Nom</th><th>Classes (JSON)</th><th>Élément</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['classes']}</td>";
        echo "<td>{$row['element']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 6: Cartes dans ma collection
    echo "<h2>Cartes dans ma collection:</h2>";
    $stmt = $pdo->query("
        SELECT c.name, c.classes, c.element, mc.quantity 
        FROM my_collection mc 
        JOIN cards c ON mc.card_uuid = c.uuid 
        LIMIT 5
    ");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Nom</th><th>Classes</th><th>Élément</th><th>Quantité</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['classes']}</td>";
        echo "<td>{$row['element']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 7: Recherche de cartes Mage avec différentes méthodes
    echo "<h2>Tests de recherche pour 'Mage':</h2>";
    
    // Méthode 1: LIKE
    $stmt = $pdo->query("SELECT name, classes FROM cards WHERE classes LIKE '%Mage%' LIMIT 3");
    echo "<h3>Méthode LIKE '%Mage%':</h3>";
    echo "<p>Résultats trouvés: " . $stmt->rowCount() . "</p>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>{$row['name']} - {$row['classes']}</li>";
    }
    echo "</ul>";
    
    // Méthode 2: JSON_SEARCH
    $stmt = $pdo->query("SELECT name, classes FROM cards WHERE JSON_SEARCH(classes, 'one', 'Mage') IS NOT NULL LIMIT 3");
    echo "<h3>Méthode JSON_SEARCH:</h3>";
    echo "<p>Résultats trouvés: " . $stmt->rowCount() . "</p>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>{$row['name']} - {$row['classes']}</li>";
    }
    echo "</ul>";
    
    // Méthode 3: JSON_CONTAINS
    $stmt = $pdo->query("SELECT name, classes FROM cards WHERE JSON_CONTAINS(classes, '\"Mage\"') LIMIT 3");
    echo "<h3>Méthode JSON_CONTAINS:</h3>";
    echo "<p>Résultats trouvés: " . $stmt->rowCount() . "</p>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>{$row['name']} - {$row['classes']}</li>";
    }
    echo "</ul>";
    
    // Test 8: Recherche dans la collection
    echo "<h2>Recherche Mage dans ma collection:</h2>";
    $stmt = $pdo->query("
        SELECT c.name, c.classes, mc.quantity 
        FROM my_collection mc 
        JOIN cards c ON mc.card_uuid = c.uuid 
        WHERE c.classes LIKE '%Mage%'
    ");
    echo "<p>Cartes Mage dans ma collection: " . $stmt->rowCount() . "</p>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>{$row['name']} (x{$row['quantity']}) - {$row['classes']}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de connexion: " . $e->getMessage() . "</p>";
}
?>