<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== MIGRATION CSR ===\n\n";
    
    // Vérifier si la colonne is_csr existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM my_collection LIKE 'is_csr'");
    $csrExists = $stmt->fetch();
    
    if (!$csrExists) {
        echo "1. Ajout de la colonne is_csr...\n";
        $pdo->exec("ALTER TABLE my_collection ADD COLUMN is_csr BOOLEAN NOT NULL DEFAULT FALSE AFTER is_foil");
        echo "   ✓ Colonne is_csr ajoutée\n\n";
        
        echo "2. Suppression de l'ancienne contrainte unique...\n";
        try {
            $pdo->exec("ALTER TABLE my_collection DROP INDEX unique_collection_entry");
            echo "   ✓ Ancienne contrainte supprimée\n\n";
        } catch (Exception $e) {
            echo "   ! Contrainte déjà supprimée ou inexistante\n\n";
        }
        
        echo "3. Ajout de la nouvelle contrainte unique...\n";
        $pdo->exec("ALTER TABLE my_collection ADD CONSTRAINT unique_collection_entry UNIQUE KEY (card_uuid, edition_uuid, is_foil, is_csr)");
        echo "   ✓ Nouvelle contrainte ajoutée\n\n";
        
        echo "4. Ajout de l'index CSR...\n";
        $pdo->exec("ALTER TABLE my_collection ADD INDEX idx_csr (is_csr)");
        echo "   ✓ Index CSR ajouté\n\n";
        
        echo "5. Détection automatique des cartes CSR existantes...\n";
        
        // Critère principal : rareté 7 (cartes signées/CSR)
        $stmt = $pdo->query("
            UPDATE my_collection mc
            JOIN card_editions ce ON mc.edition_uuid = ce.uuid
            SET mc.is_csr = TRUE
            WHERE ce.rarity = 7
        ");
        $csrByRarity = $stmt->rowCount();
        echo "   ✓ $csrByRarity cartes CSR détectées par rareté 7\n";
        
        // Critère secondaire : image contient -csr (fallback)
        $stmt = $pdo->query("
            UPDATE my_collection mc
            JOIN card_editions ce ON mc.edition_uuid = ce.uuid
            SET mc.is_csr = TRUE
            WHERE ce.image LIKE '%-csr%' AND mc.is_csr = FALSE
        ");
        $csrByImage = $stmt->rowCount();
        echo "   ✓ $csrByImage cartes CSR supplémentaires détectées par image\n";
        
        $totalCSR = $csrByRarity + $csrByImage;
        echo "   ✓ Total: $totalCSR cartes CSR détectées et mises à jour\n\n";
        
    } else {
        echo "Migration déjà appliquée - is_csr existe déjà\n\n";
    }
    
    // Statistiques finales
    echo "=== STATISTIQUES ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM my_collection WHERE is_csr = TRUE");
    $csrCount = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM my_collection WHERE is_foil = TRUE AND is_csr = FALSE");
    $foilCount = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM my_collection WHERE is_foil = FALSE AND is_csr = FALSE");
    $normalCount = $stmt->fetch()['total'];
    
    echo "Cartes normales: $normalCount\n";
    echo "Cartes foil: $foilCount\n";
    echo "Cartes CSR: $csrCount\n\n";
    
    echo "✅ Migration terminée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur durant la migration: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>