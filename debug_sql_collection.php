<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== DEBUG SQL COLLECTION ===\n\n";
    
    echo "1. État actuel en base pour Diao Chan rareté 7...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity, ce.uuid as edition_uuid
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.name LIKE '%Diao%' AND ce.rarity = 7
        ORDER BY mc.is_foil, mc.is_csr
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    if (empty($entries)) {
        echo "   ❌ Aucune carte Diao Chan rareté 7 trouvée!\n";
    } else {
        foreach ($entries as $entry) {
            echo "   - {$entry['name']}: is_foil={$entry['is_foil']}, is_csr={$entry['is_csr']}, quantity={$entry['quantity']}\n";
        }
    }
    
    echo "\n2. Forcer mise à jour CSR pour rareté 7...\n";
    $stmt = $pdo->prepare("
        UPDATE my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        SET mc.is_csr = 1
        WHERE ce.rarity = 7
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   ✓ $updated entrées mises à jour\n";
    
    echo "\n3. Vérification après mise à jour...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE c.name LIKE '%Diao%' AND ce.rarity = 7
        ORDER BY mc.is_foil, mc.is_csr
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    foreach ($entries as $entry) {
        echo "   - {$entry['name']}: is_foil={$entry['is_foil']}, is_csr={$entry['is_csr']}\n";
    }
    
    echo "\n4. Test de la requête getMyCollection exacte...\n";
    
    // Reproduire exactement la requête de getMyCollection
    $stmt = $pdo->query("SHOW COLUMNS FROM my_collection LIKE 'is_csr'");
    $csrExists = $stmt->fetch();
    echo "   Colonne is_csr existe: " . ($csrExists ? 'OUI' : 'NON') . "\n";
    
    $csrSelect = $csrExists ? 'mc.is_csr as owned_csr,' : 'FALSE as owned_csr,';
    
    $sql = "SELECT 
                c.uuid,
                c.name,
                c.slug,
                c.element,
                c.types,
                c.subtypes,
                c.classes,
                ce.uuid as edition_uuid,
                ce.collector_number,
                ce.rarity,
                ce.illustrator,
                ce.image,
                s.name as set_name,
                s.prefix as set_prefix,
                s.release_date,
                mc.quantity as owned_quantity,
                mc.is_foil as owned_foil,
                {$csrSelect}
                mc.condition_card,
                mc.notes,
                mc.acquired_date,
                mc.price_paid,
                mc.created_at as added_to_collection
            FROM my_collection mc
            JOIN cards c ON mc.card_uuid = c.uuid
            JOIN card_editions ce ON mc.edition_uuid = ce.uuid
            JOIN sets s ON ce.set_id = s.id
            WHERE c.name LIKE '%Diao%' AND ce.rarity = 7";
    
    echo "\n   Requête SQL:\n";
    echo "   " . str_replace("\n", "\n   ", $sql) . "\n\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "   Résultats:\n";
    foreach ($results as $result) {
        echo "   - {$result['name']}: owned_foil={$result['owned_foil']}, owned_csr={$result['owned_csr']}, rarity={$result['rarity']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>