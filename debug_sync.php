<?php
require_once __DIR__ . '/classes/Card.php';

try {
    $card = new Card();
    
    echo "=== DEBUG SYNC STATUS ===\n";
    
    // 1. Check current sync status
    echo "\n1. Current sync status:\n";
    $syncStatus = $card->getSyncStatus();
    print_r($syncStatus);
    
    // 2. Check total cards in database
    echo "\n2. Total cards in database:\n";
    $totalCards = $card->getCardsCount();
    echo "Total cards: $totalCards\n";
    
    // 3. Test database connection and get some stats
    echo "\n3. Database connection test:\n";
    $db = new Database();
    
    // Check if sync_status table exists and get latest entries
    try {
        $sql = "SELECT * FROM sync_status ORDER BY updated_at DESC LIMIT 10";
        $syncRecords = $db->fetchAll($sql);
        echo "Latest sync records:\n";
        foreach ($syncRecords as $record) {
            echo "[{$record['updated_at']}] {$record['status']}: {$record['message']}\n";
        }
    } catch (Exception $e) {
        echo "Error reading sync_status: " . $e->getMessage() . "\n";
    }
    
    // 4. Check table structures
    echo "\n4. Table information:\n";
    try {
        $sql = "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'grand_archive_collection'";
        $tables = $db->fetchAll($sql);
        foreach ($tables as $table) {
            echo "{$table['TABLE_NAME']}: {$table['TABLE_ROWS']} rows\n";
        }
    } catch (Exception $e) {
        echo "Error getting table info: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>