<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test des filtres - Diagnostic</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie</p>";
    
    // Test 1: Examiner les données réelles
    echo "<h2>1. Données réelles dans la base</h2>";
    $stmt = $pdo->query("SELECT name, classes, element FROM cards WHERE classes IS NOT NULL LIMIT 10");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes (JSON)</th><th>Element</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
        echo "<td>{$row['element']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 2: Tester JSON_TABLE pour les classes
    echo "<h2>2. Test JSON_TABLE pour les classes</h2>";
    try {
        $stmt = $pdo->query("
            SELECT 
                c.name, 
                c.classes, 
                jt.class_name
            FROM cards c
            JOIN JSON_TABLE(
                c.classes, '$[*]' 
                COLUMNS (class_name VARCHAR(100) PATH '$')
            ) jt
            WHERE c.classes IS NOT NULL
            LIMIT 10
        ");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom</th><th>Classes JSON</th><th>Classe extraite</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
            echo "<td><strong>{$row['class_name']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_TABLE: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Tester la requête de recherche pour "Mage"
    echo "<h2>3. Test recherche pour 'Mage'</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name, 
                c.classes
            FROM cards c
            WHERE EXISTS (
                SELECT 1 FROM JSON_TABLE(
                    c.classes, '$[*]' 
                    COLUMNS (class_name VARCHAR(100) PATH '$')
                ) jt WHERE jt.class_name = ?
            )
            LIMIT 5
        ");
        $stmt->execute(['Mage']);
        echo "<p>Résultats trouvés: " . $stmt->rowCount() . "</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom</th><th>Classes</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur recherche Mage: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Tester la requête de collection pour "Mage"
    echo "<h2>4. Test collection pour 'Mage'</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name, 
                c.classes,
                mc.quantity
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE EXISTS (
                SELECT 1 FROM JSON_TABLE(
                    c.classes, '$[*]' 
                    COLUMNS (class_name VARCHAR(100) PATH '$')
                ) jt WHERE jt.class_name = ?
            )
            LIMIT 5
        ");
        $stmt->execute(['Mage']);
        echo "<p>Cartes Mage dans ma collection: " . $stmt->rowCount() . "</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom</th><th>Classes</th><th>Quantité</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur collection Mage: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Tester le filtre par élément
    echo "<h2>5. Test filtre par élément 'Fire'</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name, 
                c.element,
                mc.quantity
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.element = ?
            LIMIT 5
        ");
        $stmt->execute(['Fire']);
        echo "<p>Cartes Fire dans ma collection: " . $stmt->rowCount() . "</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom</th><th>Element</th><th>Quantité</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['element']}</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur filtre Fire: " . $e->getMessage() . "</p>";
    }
    
    // Test 6: Lister tous les éléments uniques
    echo "<h2>6. Tous les éléments dans la base</h2>";
    $stmt = $pdo->query("SELECT DISTINCT element FROM cards WHERE element IS NOT NULL ORDER BY element");
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li><strong>{$row['element']}</strong></li>";
    }
    echo "</ul>";
    
    // Test 7: Lister toutes les classes uniques
    echo "<h2>7. Toutes les classes dans la base</h2>";
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT jt.class_name
            FROM cards c
            JOIN JSON_TABLE(
                c.classes, '$[*]' 
                COLUMNS (class_name VARCHAR(100) PATH '$')
            ) jt
            WHERE c.classes IS NOT NULL
            ORDER BY jt.class_name
        ");
        echo "<ul>";
        while ($row = $stmt->fetch()) {
            echo "<li><strong>{$row['class_name']}</strong></li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur classes uniques: " . $e->getMessage() . "</p>";
    }
    
    // Test 8: Compter les cartes dans ma collection
    echo "<h2>8. Statistiques de ma collection</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM my_collection");
    $result = $stmt->fetch();
    echo "<p>Total éléments dans ma collection: <strong>{$result['count']}</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT card_uuid) as count FROM my_collection");
    $result = $stmt->fetch();
    echo "<p>Cartes uniques dans ma collection: <strong>{$result['count']}</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>