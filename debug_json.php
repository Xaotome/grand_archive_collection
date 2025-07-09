<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug spécifique pour les filtres JSON</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie</p>";
    
    // Test 1: Version MySQL et support JSON
    echo "<h2>1. Version MySQL et support JSON</h2>";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "<p>Version MySQL: <strong>{$result['version']}</strong></p>";
    
    // Test si JSON_TABLE est supporté
    try {
        $stmt = $pdo->query("SELECT JSON_TABLE('[]', '$[*]' COLUMNS (test VARCHAR(100) PATH '$')) AS test LIMIT 1");
        echo "<p style='color: green;'>✓ JSON_TABLE supporté</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ JSON_TABLE non supporté: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Examiner les données réelles
    echo "<h2>2. Données réelles dans ta collection</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.name, 
            c.classes, 
            c.element,
            mc.quantity
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        LIMIT 5
    ");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes (JSON)</th><th>Element</th><th>Quantité</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace; background: #f5f5f5;'>" . htmlspecialchars($row['classes']) . "</td>";
        echo "<td>{$row['element']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Tester différentes méthodes de recherche JSON
    echo "<h2>3. Tests de recherche JSON pour 'Champion'</h2>";
    
    // Méthode 1: JSON_SEARCH
    echo "<h3>Méthode 1: JSON_SEARCH</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_SEARCH(c.classes, 'one', ?) IS NOT NULL
            LIMIT 3
        ");
        $stmt->execute(['Champion']);
        echo "<p>Résultats JSON_SEARCH: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_SEARCH: " . $e->getMessage() . "</p>";
    }
    
    // Méthode 2: JSON_CONTAINS
    echo "<h3>Méthode 2: JSON_CONTAINS</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_CONTAINS(c.classes, ?)
            LIMIT 3
        ");
        $stmt->execute(['"Champion"']);
        echo "<p>Résultats JSON_CONTAINS: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_CONTAINS: " . $e->getMessage() . "</p>";
    }
    
    // Méthode 3: JSON_TABLE (actuelle)
    echo "<h3>Méthode 3: JSON_TABLE (actuelle)</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE EXISTS (
                SELECT 1 FROM JSON_TABLE(
                    c.classes, '$.''[*]' 
                    COLUMNS (class_name VARCHAR(100) PATH '$')
                ) jt WHERE jt.class_name = ?
            )
            LIMIT 3
        ");
        $stmt->execute(['Champion']);
        echo "<p>Résultats JSON_TABLE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_TABLE: " . $e->getMessage() . "</p>";
    }
    
    // Méthode 4: LIKE simple
    echo "<h3>Méthode 4: LIKE simple</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.classes LIKE ?
            LIMIT 3
        ");
        $stmt->execute(['%Champion%']);
        echo "<p>Résultats LIKE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur LIKE: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Vérifier le format exact des données JSON
    echo "<h2>4. Format exact des données JSON</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.name,
            c.classes,
            JSON_TYPE(c.classes) as json_type,
            JSON_LENGTH(c.classes) as json_length
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.classes IS NOT NULL
        LIMIT 3
    ");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes</th><th>Type JSON</th><th>Longueur JSON</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
        echo "<td>{$row['json_type']}</td>";
        echo "<td>{$row['json_length']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 5: Extraire les valeurs JSON manuellement
    echo "<h2>5. Extraction manuelle des valeurs JSON</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.name,
            c.classes,
            JSON_EXTRACT(c.classes, '$[0]') as first_class,
            JSON_EXTRACT(c.classes, '$[1]') as second_class
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.classes IS NOT NULL
        LIMIT 3
    ");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes JSON</th><th>Première classe</th><th>Deuxième classe</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['classes']) . "</td>";
        echo "<td>{$row['first_class']}</td>";
        echo "<td>{$row['second_class']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 6: Tester avec JSON_EXTRACT
    echo "<h2>6. Test avec JSON_EXTRACT pour 'Champion'</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE JSON_EXTRACT(c.classes, '$[0]') = ? 
               OR JSON_EXTRACT(c.classes, '$[1]') = ?
               OR JSON_EXTRACT(c.classes, '$[2]') = ?
            LIMIT 3
        ");
        $stmt->execute(['Champion', 'Champion', 'Champion']);
        echo "<p>Résultats JSON_EXTRACT: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur JSON_EXTRACT: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de connexion: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h2 { color: #333; margin-top: 30px; }
h3 { color: #666; }
</style>