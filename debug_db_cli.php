<?php
// Script CLI pour diagnostiquer la base de données
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "=== DIAGNOSTIC DE LA BASE DE DONNÉES ===\n\n";
    
    // 1. Vérifier la structure de la table cards
    echo "1. STRUCTURE DE LA TABLE CARDS:\n";
    $structure = $db->fetchAll("DESCRIBE cards");
    foreach ($structure as $row) {
        echo "  {$row['Field']}: {$row['Type']}\n";
    }
    echo "\n";
    
    // 2. Vérifier le contenu des classes dans la table cards
    echo "2. EXEMPLES DE DONNÉES CLASSES:\n";
    $cards = $db->fetchAll("SELECT uuid, name, classes, element FROM cards LIMIT 5");
    foreach ($cards as $card) {
        echo "  Carte: {$card['name']}\n";
        echo "    Classes: {$card['classes']}\n";
        echo "    Element: {$card['element']}\n";
        echo "    ---\n";
    }
    echo "\n";
    
    // 3. Vérifier le contenu de ma collection
    echo "3. CONTENU DE MA COLLECTION:\n";
    $my_collection = $db->fetchAll("
        SELECT 
            c.name, 
            c.classes, 
            c.element, 
            mc.quantity 
        FROM my_collection mc 
        JOIN cards c ON mc.card_uuid = c.uuid 
        LIMIT 5
    ");
    foreach ($my_collection as $item) {
        echo "  Collection: {$item['name']}\n";
        echo "    Classes: {$item['classes']}\n";
        echo "    Element: {$item['element']}\n";
        echo "    Quantity: {$item['quantity']}\n";
        echo "    ---\n";
    }
    echo "\n";
    
    // 4. Tester JSON_SEARCH pour 'Mage'
    echo "4. TEST JSON_SEARCH pour 'Mage':\n";
    $json_search = $db->fetchAll("
        SELECT name, classes 
        FROM cards 
        WHERE JSON_SEARCH(classes, 'one', 'Mage') IS NOT NULL 
        LIMIT 3
    ");
    echo "  Résultats trouvés: " . count($json_search) . "\n";
    foreach ($json_search as $item) {
        echo "  - {$item['name']}: {$item['classes']}\n";
    }
    echo "\n";
    
    // 5. Tester JSON_CONTAINS pour 'Mage'
    echo "5. TEST JSON_CONTAINS pour 'Mage':\n";
    $json_contains = $db->fetchAll("
        SELECT name, classes 
        FROM cards 
        WHERE JSON_CONTAINS(classes, JSON_QUOTE('Mage')) 
        LIMIT 3
    ");
    echo "  Résultats trouvés: " . count($json_contains) . "\n";
    foreach ($json_contains as $item) {
        echo "  - {$item['name']}: {$item['classes']}\n";
    }
    echo "\n";
    
    // 6. Lister toutes les classes uniques
    echo "6. TOUTES LES CLASSES UNIQUES:\n";
    $all_classes = $db->fetchAll("SELECT DISTINCT classes FROM cards WHERE classes IS NOT NULL LIMIT 10");
    foreach ($all_classes as $class) {
        echo "  {$class['classes']}\n";
    }
    echo "\n";
    
    // 7. Compter les cartes totales
    echo "7. STATISTIQUES:\n";
    $total_cards = $db->fetch("SELECT COUNT(*) as count FROM cards");
    echo "  Total cartes: {$total_cards['count']}\n";
    
    $total_collection = $db->fetch("SELECT COUNT(*) as count FROM my_collection");
    echo "  Total dans ma collection: {$total_collection['count']}\n";
    
    // 8. Test spécifique pour recherche de classe
    echo "\n8. TEST RECHERCHE CLASSE SPECIFIC:\n";
    $class_test = $db->fetchAll("
        SELECT name, classes 
        FROM cards 
        WHERE classes LIKE '%Mage%' 
        LIMIT 3
    ");
    echo "  Résultats LIKE '%Mage%': " . count($class_test) . "\n";
    foreach ($class_test as $item) {
        echo "  - {$item['name']}: {$item['classes']}\n";
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
?>