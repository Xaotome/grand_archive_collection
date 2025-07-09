<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Collection.php';

echo "<h1>Test des filtres collection</h1>";

try {
    $collection = new Collection();
    echo "<p style='color: green;'>✓ Classe Collection créée</p>";
    
    // Test 1: Classes dans la collection
    echo "<h2>1. Classes présentes dans ta collection</h2>";
    $classes = $collection->getCollectionClasses();
    echo "<p>Nombre de classes trouvées: <strong>" . count($classes) . "</strong></p>";
    echo "<ul>";
    foreach ($classes as $class) {
        echo "<li><strong>{$class['class_name']}</strong></li>";
    }
    echo "</ul>";
    
    // Test 2: Éléments dans la collection
    echo "<h2>2. Éléments présents dans ta collection</h2>";
    $elements = $collection->getCollectionElements();
    echo "<p>Nombre d'éléments trouvés: <strong>" . count($elements) . "</strong></p>";
    echo "<ul>";
    foreach ($elements as $element) {
        echo "<li><strong>{$element}</strong></li>";
    }
    echo "</ul>";
    
    // Test 3: Extensions dans la collection
    echo "<h2>3. Extensions présentes dans ta collection</h2>";
    $sets = $collection->getCollectionSets();
    echo "<p>Nombre d'extensions trouvées: <strong>" . count($sets) . "</strong></p>";
    echo "<ul>";
    foreach ($sets as $set) {
        echo "<li><strong>{$set['name']}</strong> ({$set['prefix']})</li>";
    }
    echo "</ul>";
    
    // Test 4: Test des endpoints API
    echo "<h2>4. Test des endpoints API</h2>";
    
    $api_tests = [
        'collection_classes' => 'Classes de collection',
        'collection_elements' => 'Éléments de collection',
        'collection_sets' => 'Extensions de collection'
    ];
    
    foreach ($api_tests as $endpoint => $description) {
        echo "<h3>{$description}</h3>";
        
        $url = "http://localhost/grand-archive-collection/api/cards.php?action={$endpoint}";
        $response = file_get_contents($url);
        
        if ($response === false) {
            echo "<p style='color: red;'>Erreur lors de l'appel API</p>";
        } else {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p style='color: green;'>✓ API fonctionne - " . count($data['data']) . " éléments</p>";
                echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 12px;'>";
                echo htmlspecialchars(json_encode($data['data'], JSON_PRETTY_PRINT));
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>✗ Erreur API: " . ($data['error'] ?? 'Unknown') . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
pre { max-height: 200px; overflow-y: auto; }
</style>