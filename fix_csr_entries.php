<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== CORRECTION ENTRÉES CSR ===\n\n";
    
    echo "1. État actuel de Diao Chan...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity,
               CASE WHEN mc.is_csr = 1 THEN 'CSR'
                    WHEN mc.is_foil = 1 THEN 'FOIL' 
                    ELSE 'NORMAL' END as type
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE ce.uuid = 'ik31y7hrs9'
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    foreach ($entries as $entry) {
        echo "   - {$entry['name']}: Quantité={$entry['quantity']}, Type={$entry['type']}, is_foil={$entry['is_foil']}, is_csr={$entry['is_csr']}\n";
    }
    
    echo "\n2. Correction automatique...\n";
    echo "   Les cartes rareté 7 doivent avoir is_csr=1\n";
    
    $stmt = $pdo->prepare("
        UPDATE my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        SET mc.is_csr = 1
        WHERE ce.rarity = 7 AND mc.is_csr = 0
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   ✓ $updated entrées mises à jour\n";
    
    echo "\n3. État après correction...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity,
               CASE WHEN mc.is_csr = 1 AND mc.is_foil = 1 THEN 'CSR+FOIL'
                    WHEN mc.is_csr = 1 THEN 'CSR'
                    WHEN mc.is_foil = 1 THEN 'FOIL' 
                    ELSE 'NORMAL' END as type
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE ce.uuid = 'ik31y7hrs9'
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    foreach ($entries as $entry) {
        echo "   - {$entry['name']}: Quantité={$entry['quantity']}, Type={$entry['type']}\n";
    }
    
    echo "\n4. Vérification globale...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM my_collection WHERE is_csr = 1");
    $csrCount = $stmt->fetch()['count'];
    echo "   Total cartes CSR en collection: $csrCount\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN CORRECTION ===\n";
?>