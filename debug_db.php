<?php
// Script de diagnostic pour examiner la base de données
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "<h1>Diagnostic de la base de données grand_archive_collection</h1>";
    
    // 1. Vérifier la structure de la table cards
    echo "<h2>1. Structure de la table cards</h2>";
    $structure = $db->fetchAll("DESCRIBE cards");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $row) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Vérifier le contenu des classes dans la table cards
    echo "<h2>2. Exemples de données classes dans la table cards</h2>";
    $cards = $db->fetchAll("SELECT uuid, name, classes, element FROM cards LIMIT 10");
    echo "<table border='1'>";
    echo "<tr><th>UUID</th><th>Name</th><th>Classes (JSON)</th><th>Element</th></tr>";
    foreach ($cards as $card) {
        echo "<tr>";
        echo "<td>{$card['uuid']}</td>";
        echo "<td>{$card['name']}</td>";
        echo "<td>{$card['classes']}</td>";
        echo "<td>{$card['element']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Vérifier la structure de la table my_collection
    echo "<h2>3. Structure de la table my_collection</h2>";
    $collection_structure = $db->fetchAll("DESCRIBE my_collection");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($collection_structure as $row) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Vérifier le contenu de ma collection
    echo "<h2>4. Contenu de ma collection avec les classes</h2>";
    $my_collection = $db->fetchAll("
        SELECT 
            c.name, 
            c.classes, 
            c.element, 
            mc.quantity 
        FROM my_collection mc 
        JOIN cards c ON mc.card_uuid = c.uuid 
        LIMIT 10
    ");
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Classes (JSON)</th><th>Element</th><th>Quantity</th></tr>";
    foreach ($my_collection as $item) {
        echo "<tr>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['classes']}</td>";
        echo "<td>{$item['element']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Tester les requêtes JSON
    echo "<h2>5. Test des requêtes JSON pour les classes</h2>";
    
    // Test JSON_SEARCH
    echo "<h3>Test JSON_SEARCH pour 'Mage'</h3>";
    $json_search = $db->fetchAll("
        SELECT name, classes 
        FROM cards 
        WHERE JSON_SEARCH(classes, 'one', 'Mage') IS NOT NULL 
        LIMIT 5
    ");
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Classes</th></tr>";
    foreach ($json_search as $item) {
        echo "<tr>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['classes']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test JSON_CONTAINS
    echo "<h3>Test JSON_CONTAINS pour 'Mage'</h3>";
    $json_contains = $db->fetchAll("
        SELECT name, classes 
        FROM cards 
        WHERE JSON_CONTAINS(classes, JSON_QUOTE('Mage')) 
        LIMIT 5
    ");
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Classes</th></tr>";
    foreach ($json_contains as $item) {
        echo "<tr>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['classes']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Lister toutes les classes uniques
    echo "<h2>6. Toutes les classes uniques dans la base</h2>";
    $all_classes = $db->fetchAll("SELECT DISTINCT classes FROM cards WHERE classes IS NOT NULL");
    echo "<ul>";
    foreach ($all_classes as $class) {
        echo "<li>{$class['classes']}</li>";
    }
    echo "</ul>";
    
    // 7. Compter les cartes par classe
    echo "<h2>7. Nombre de cartes par classe (approximatif)</h2>";
    $class_counts = $db->fetchAll("
        SELECT 
            classes,
            COUNT(*) as count 
        FROM cards 
        WHERE classes IS NOT NULL 
        GROUP BY classes 
        ORDER BY count DESC
    ");
    echo "<table border='1'>";
    echo "<tr><th>Classes JSON</th><th>Count</th></tr>";
    foreach ($class_counts as $item) {
        echo "<tr>";
        echo "<td>{$item['classes']}</td>";
        echo "<td>{$item['count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h1>Erreur de base de données</h1>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}
?>