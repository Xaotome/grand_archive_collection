<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug exact du format des classes</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie</p>";
    
    // Test 1: Afficher le format exact des classes
    echo "<h2>1. Format exact des classes dans ta collection</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.name,
            c.classes,
            LENGTH(c.classes) as length_classes,
            HEX(c.classes) as hex_classes,
            CHAR_LENGTH(c.classes) as char_length
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.classes IS NOT NULL
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes</th><th>Length</th><th>Char Length</th><th>Format HEX</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace; background: #f0f0f0; padding: 5px;'>" . htmlspecialchars($row['classes']) . "</td>";
        echo "<td>{$row['length_classes']}</td>";
        echo "<td>{$row['char_length']}</td>";
        echo "<td style='font-family: monospace; font-size: 11px;'>" . substr($row['hex_classes'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 2: Test de recherche caractère par caractère
    echo "<h2>2. Test de recherche pour chaque caractère</h2>";
    $stmt = $pdo->query("
        SELECT c.name, c.classes
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.classes IS NOT NULL
        LIMIT 1
    ");
    $row = $stmt->fetch();
    
    echo "<p>Carte exemple: <strong>{$row['name']}</strong></p>";
    echo "<p>Classes: <code>" . htmlspecialchars($row['classes']) . "</code></p>";
    
    $classes_string = $row['classes'];
    echo "<p>Caractères:</p>";
    echo "<div style='font-family: monospace; background: #f0f0f0; padding: 10px;'>";
    for ($i = 0; $i < strlen($classes_string); $i++) {
        $char = $classes_string[$i];
        echo "Position {$i}: '" . htmlspecialchars($char) . "' (ASCII: " . ord($char) . ")<br>";
    }
    echo "</div>";
    
    // Test 3: Tests de recherche avec différents formats
    echo "<h2>3. Tests de recherche avec différents formats</h2>";
    
    $test_patterns = [
        'MAGE',
        '"MAGE"',
        '["MAGE"]',
        '[\"MAGE\"]',
        'MAGE',
        'Mage',
        'mage',
        'CLERIC',
        'TAMER'
    ];
    
    foreach ($test_patterns as $pattern) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.classes LIKE ?
        ");
        $stmt->execute(['%' . $pattern . '%']);
        $count = $stmt->fetch()['count'];
        echo "<p>Pattern '<strong>{$pattern}</strong>': {$count} résultats</p>";
    }
    
    // Test 4: Test avec REGEX si disponible
    echo "<h2>4. Test avec REGEXP</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.classes
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            WHERE c.classes REGEXP ?
            LIMIT 3
        ");
        $stmt->execute(['MAGE']);
        echo "<p>Résultats REGEXP pour MAGE: " . $stmt->rowCount() . "</p>";
        while ($row = $stmt->fetch()) {
            echo "<p>- {$row['name']}: {$row['classes']}</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur REGEXP: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Test avec différents encodages
    echo "<h2>5. Test toutes les cartes et leurs classes</h2>";
    $stmt = $pdo->query("
        SELECT 
            c.name,
            c.classes,
            c.element
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.classes IS NOT NULL
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nom</th><th>Classes brutes</th><th>Element</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='font-family: monospace; background: #f0f0f0; padding: 5px;'>";
        
        // Afficher chaque caractère
        $classes = $row['classes'];
        for ($i = 0; $i < strlen($classes); $i++) {
            $char = $classes[$i];
            if ($char === '"') {
                echo '<span style="color: red; font-weight: bold;">"</span>';
            } elseif ($char === '[') {
                echo '<span style="color: blue; font-weight: bold;">[</span>';
            } elseif ($char === ']') {
                echo '<span style="color: blue; font-weight: bold;">]</span>';
            } elseif ($char === '\\') {
                echo '<span style="color: green; font-weight: bold;">\\</span>';
            } else {
                echo htmlspecialchars($char);
            }
        }
        
        echo "</td>";
        echo "<td>{$row['element']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>