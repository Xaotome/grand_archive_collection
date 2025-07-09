<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug du filtre par extension</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=grand_archive_collection;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✓ Connexion réussie</p>";
    
    // Test 1: Vérifier les extensions dans ma collection
    echo "<h2>1. Extensions dans ma collection</h2>";
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.name,
            s.prefix,
            COUNT(DISTINCT mc.card_uuid) as nb_cartes
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN sets s ON ce.set_id = s.id
        GROUP BY s.id, s.name, s.prefix
        ORDER BY s.name
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Préfixe</th><th>Nb cartes</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td><strong>{$row['prefix']}</strong></td>";
        echo "<td>{$row['nb_cartes']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 2: Vérifier les paramètres de filtrage
    echo "<h2>2. Test des paramètres de filtrage</h2>";
    
    // Obtenir la première extension
    $stmt = $pdo->query("
        SELECT s.prefix
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN sets s ON ce.set_id = s.id
        LIMIT 1
    ");
    $first_set = $stmt->fetch();
    
    if ($first_set) {
        $test_prefix = $first_set['prefix'];
        echo "<p>Test avec l'extension: <strong>{$test_prefix}</strong></p>";
        
        // Test 3: Requête collection avec set_prefix
        echo "<h3>Test requête collection avec set_prefix</h3>";
        $stmt = $pdo->prepare("
            SELECT 
                c.name,
                s.name as set_name,
                s.prefix as set_prefix,
                mc.quantity
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            JOIN card_editions ce ON mc.edition_uuid = ce.uuid
            JOIN sets s ON ce.set_id = s.id
            WHERE s.prefix = ?
        ");
        $stmt->execute([$test_prefix]);
        echo "<p>Résultats trouvés: " . $stmt->rowCount() . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nom carte</th><th>Extension</th><th>Préfixe</th><th>Quantité</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['set_name']}</td>";
            echo "<td><strong>{$row['set_prefix']}</strong></td>";
            echo "<td>{$row['quantity']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test 4: Vérifier le mapping des paramètres
        echo "<h3>Test mapping paramètres API</h3>";
        echo "<p>Paramètre reçu par l'API: <code>set_prefix</code></p>";
        echo "<p>Champ en base: <code>s.prefix</code></p>";
        
        // Simuler l'appel API
        $filters = [
            'set_prefix' => $test_prefix
        ];
        
        $sql = "SELECT 
                    c.name,
                    s.prefix,
                    mc.quantity
                FROM my_collection mc
                JOIN cards c ON mc.card_uuid = c.uuid
                JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                JOIN sets s ON ce.set_id = s.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['set_prefix'])) {
            $sql .= " AND s.prefix = :set_prefix";
            $params[':set_prefix'] = $filters['set_prefix'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo "<p>Requête SQL: <code>" . htmlspecialchars($sql) . "</code></p>";
        echo "<p>Paramètres: <code>" . htmlspecialchars(json_encode($params)) . "</code></p>";
        echo "<p>Résultats: " . $stmt->rowCount() . "</p>";
        
    } else {
        echo "<p style='color: red;'>Aucune extension trouvée dans la collection</p>";
    }
    
    // Test 5: Vérifier les paramètres JavaScript
    echo "<h2>3. Test des paramètres JavaScript</h2>";
    echo "<p>Vérifier que le JavaScript envoie bien le paramètre <code>set_prefix</code> et non <code>set</code></p>";
    
    // Test 6: Vérifier les logs récents
    echo "<h2>4. Différences possibles</h2>";
    echo "<p><strong>Collection:</strong> Paramètre attendu = <code>set_prefix</code></p>";
    echo "<p><strong>Recherche:</strong> Paramètre attendu = <code>set_prefix</code></p>";
    echo "<p><strong>Champ base:</strong> <code>s.prefix</code></p>";
    
    // Test 7: Vérifier si c'est un problème de nom de paramètre
    echo "<h2>5. Test avec différents noms de paramètres</h2>";
    
    $param_tests = [
        'set' => 'set',
        'set_prefix' => 'set_prefix',
        'prefix' => 'prefix'
    ];
    
    foreach ($param_tests as $param_name => $param_value) {
        echo "<h3>Test avec paramètre '{$param_name}'</h3>";
        
        $url = "http://localhost/grand-archive-collection/api/cards.php?action=collection&{$param_name}={$test_prefix}";
        $response = file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p style='color: green;'>✓ Fonctionne - " . count($data['data']) . " résultats</p>";
            } else {
                echo "<p style='color: red;'>✗ Erreur: " . ($data['error'] ?? 'Unknown') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Pas de réponse</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background: #f0f0f0; padding: 2px 4px; }
</style>