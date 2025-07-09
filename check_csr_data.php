<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== VÉRIFICATION DONNÉES CSR ===\n\n";
    
    echo "1. Données brutes en base pour Diao Chan...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE ce.uuid = 'ik31y7hrs9'
        ORDER BY mc.is_foil, mc.is_csr
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    foreach ($entries as $entry) {
        echo "   ID: {$entry['id']}\n";
        echo "   Nom: {$entry['name']}\n";
        echo "   Quantité: {$entry['quantity']}\n";
        echo "   is_foil: {$entry['is_foil']}\n";
        echo "   is_csr: {$entry['is_csr']}\n";
        echo "   Rareté: {$entry['rarity']}\n";
        echo "   ---\n";
    }
    
    echo "\n2. Forcer mise à jour CSR...\n";
    $stmt = $pdo->prepare("
        UPDATE my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        SET mc.is_csr = 1
        WHERE ce.rarity = 7
    ");
    $stmt->execute();
    echo "   ✓ Mise à jour forcée effectuée\n";
    
    echo "\n3. Vérification après mise à jour...\n";
    $stmt = $pdo->prepare("
        SELECT mc.*, c.name, ce.rarity
        FROM my_collection mc
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        JOIN cards c ON mc.card_uuid = c.uuid
        WHERE ce.uuid = 'ik31y7hrs9'
        ORDER BY mc.is_foil, mc.is_csr
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    foreach ($entries as $entry) {
        echo "   ID: {$entry['id']}\n";
        echo "   Nom: {$entry['name']}\n";
        echo "   is_foil: {$entry['is_foil']}\n";
        echo "   is_csr: {$entry['is_csr']}\n";
        echo "   Type: ";
        if ($entry['is_csr'] && $entry['is_foil']) echo "CSR+FOIL";
        elseif ($entry['is_csr']) echo "CSR";
        elseif ($entry['is_foil']) echo "FOIL";
        else echo "NORMAL";
        echo "\n   ---\n";
    }
    
    echo "\n4. Test requête getMyCollection...\n";
    $stmt = $pdo->prepare("
        SELECT 
            c.name,
            mc.is_foil as owned_foil,
            mc.is_csr as owned_csr,
            ce.rarity
        FROM my_collection mc
        JOIN cards c ON mc.card_uuid = c.uuid
        JOIN card_editions ce ON mc.edition_uuid = ce.uuid
        WHERE ce.uuid = 'ik31y7hrs9'
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    foreach ($results as $result) {
        echo "   Nom: {$result['name']}\n";
        echo "   owned_foil: {$result['owned_foil']}\n";
        echo "   owned_csr: {$result['owned_csr']}\n";
        echo "   rarity: {$result['rarity']}\n";
        echo "   ---\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VÉRIFICATION ===\n";
?>