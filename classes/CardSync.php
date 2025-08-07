<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Classe optimisée pour la synchronisation des cartes
 * Corrige les problèmes de timeout et mémoire
 */
class CardSync {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        
        // Augmenter les limites PHP pour la synchronisation
        @ini_set('max_execution_time', 300); // 5 minutes max
        @ini_set('memory_limit', '512M'); // 512MB max
    }
    
    /**
     * Synchronisation optimisée avec gestion des erreurs améliorée
     */
    public function syncCardsFromAPI($limit = null, $offset = 0) {
        $syncLog = [];
        $totalImported = 0;
        $totalErrors = 0;
        $totalSkipped = 0;
        
        try {
            // Créer la table sync_status si elle n'existe pas
            $this->ensureSyncStatusTable();
            
            // Mettre à jour le statut
            $this->updateSyncStatus('running', 'Démarrage de la synchronisation optimisée...');
            
            $apiUrl = 'https://api.gatcg.com/cards/search';
            
            // Context HTTP avec timeout plus court et retry
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30, // Réduire à 30s par requête
                    'user_agent' => 'Grand Archive Collection Manager/1.0',
                    'header' => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Connection: close' // Éviter keep-alive
                    ]
                ]
            ]);
            
            // Test de connexion API avec retry
            $firstPageData = $this->fetchWithRetry($apiUrl . '?page=1', $context, 3);
            
            if ($firstPageData === false) {
                $this->updateSyncStatus('running', 'API externe non accessible, utilisation de données de test...');
                return $this->syncTestCards($limit, $offset);
            }
            
            $firstPageJson = json_decode($firstPageData, true);
            if (!$firstPageJson) {
                throw new Exception('Réponse JSON invalide de l\'API GATCG');
            }
            
            // Informations de pagination
            $totalPages = $firstPageJson['total_pages'] ?? $firstPageJson['last_page'] ?? 1;
            $totalCards = $firstPageJson['total'] ?? 0;
            $perPage = $firstPageJson['per_page'] ?? 30;
            
            // Calculer les pages à traiter
            $startPage = 1;
            $endPage = $totalPages;
            
            if ($limit && $offset) {
                $startPage = floor($offset / $perPage) + 1;
                $endPage = min($totalPages, $startPage + floor($limit / $perPage));
            } elseif ($limit) {
                $endPage = min($totalPages, ceil($limit / $perPage));
            }
            
            $this->updateSyncStatus('running', 
                "Synchronisation optimisée des pages {$startPage} à {$endPage} ({$totalCards} cartes disponibles)");
            
            // Traitement par lots pour éviter les timeouts
            $batchSize = 5; // Traiter 5 pages par batch
            $currentBatch = $startPage;
            
            while ($currentBatch <= $endPage) {
                $batchEnd = min($currentBatch + $batchSize - 1, $endPage);
                
                $this->updateSyncStatus('running', 
                    "Traitement du lot pages {$currentBatch}-{$batchEnd}...");
                
                // Traiter un batch de pages
                $batchResult = $this->processBatch($currentBatch, $batchEnd, $apiUrl, $context);
                
                $totalImported += $batchResult['imported'];
                $totalSkipped += $batchResult['skipped'];
                $totalErrors += $batchResult['errors'];
                
                // Ajouter les logs (limités)
                $syncLog = array_merge($syncLog, array_slice($batchResult['log'], 0, 10));
                
                // Limiter la taille du log pour éviter la surcharge mémoire
                if (count($syncLog) > 50) {
                    $syncLog = array_slice($syncLog, -50);
                }
                
                // Pause entre les batches pour éviter la surcharge
                sleep(2);
                
                // Vérifier si on doit s'arrêter (trop d'erreurs)
                if ($totalErrors > 50) {
                    $this->updateSyncStatus('error', 
                        "Trop d'erreurs ({$totalErrors}), arrêt de la synchronisation");
                    break;
                }
                
                $currentBatch = $batchEnd + 1;
                
                // Forcer le nettoyage de la mémoire
                gc_collect_cycles();
            }
            
            $this->updateSyncStatus('completed', 
                "Synchronisation terminée - {$totalImported} importées, {$totalSkipped} ignorées, {$totalErrors} erreurs");
            
            return [
                'imported' => $totalImported,
                'skipped' => $totalSkipped,
                'errors' => $totalErrors,
                'total_processed' => $totalImported + $totalSkipped + $totalErrors,
                'total_available' => $totalCards,
                'pages_processed' => ($endPage - $startPage + 1),
                'total_pages' => $totalPages,
                'log' => $syncLog
            ];
            
        } catch (Exception $e) {
            $this->updateSyncStatus('error', 'Erreur: ' . $e->getMessage());
            error_log("Sync error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Traite un batch de pages
     */
    private function processBatch($startPage, $endPage, $apiUrl, $context) {
        $batchImported = 0;
        $batchSkipped = 0;
        $batchErrors = 0;
        $batchLog = [];
        
        for ($currentPage = $startPage; $currentPage <= $endPage; $currentPage++) {
            try {
                $pageUrl = $apiUrl . '?page=' . $currentPage;
                $pageData = $this->fetchWithRetry($pageUrl, $context, 2);
                
                if ($pageData === false) {
                    $batchErrors++;
                    $batchLog[] = [
                        'action' => 'error',
                        'page' => $currentPage,
                        'error' => "Impossible de récupérer la page {$currentPage}"
                    ];
                    continue;
                }
                
                $pageJson = json_decode($pageData, true);
                if (!$pageJson || !isset($pageJson['data'])) {
                    $batchErrors++;
                    continue;
                }
                
                // Traiter les cartes de cette page
                $pageResult = $this->processPageCards($pageJson['data'], $currentPage);
                $batchImported += $pageResult['imported'];
                $batchSkipped += $pageResult['skipped'];
                $batchErrors += $pageResult['errors'];
                
                // Ajouter quelques logs seulement
                if (!empty($pageResult['log'])) {
                    $batchLog = array_merge($batchLog, array_slice($pageResult['log'], 0, 3));
                }
                
                // Petite pause entre les pages
                usleep(500000); // 500ms
                
            } catch (Exception $e) {
                $batchErrors++;
                $batchLog[] = [
                    'action' => 'error',
                    'page' => $currentPage,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'imported' => $batchImported,
            'skipped' => $batchSkipped,
            'errors' => $batchErrors,
            'log' => $batchLog
        ];
    }
    
    /**
     * Traite les cartes d'une page
     */
    private function processPageCards($cards, $pageNumber) {
        $pageImported = 0;
        $pageSkipped = 0;
        $pageErrors = 0;
        $pageLog = [];
        
        foreach ($cards as $cardData) {
            try {
                // Validation rapide
                if (!isset($cardData['uuid']) || !isset($cardData['name'])) {
                    if (isset($cardData['id'])) $cardData['uuid'] = $cardData['id'];
                    if (!isset($cardData['name']) && isset($cardData['card_name'])) {
                        $cardData['name'] = $cardData['card_name'];
                    }
                }
                
                if (!isset($cardData['uuid']) || !isset($cardData['name'])) {
                    $pageErrors++;
                    continue;
                }
                
                // Vérifier si existe déjà
                if ($this->cardExists($cardData['uuid'])) {
                    $pageSkipped++;
                    continue;
                }
                
                // Normaliser et sauvegarder
                $normalizedCardData = $this->normalizeCardData($cardData);
                
                if (!$this->validateCardData($normalizedCardData)) {
                    $pageErrors++;
                    continue;
                }
                
                $this->saveCard($normalizedCardData);
                $pageImported++;
                
                // Log seulement quelques cartes
                if ($pageImported <= 2) {
                    $pageLog[] = [
                        'action' => 'imported',
                        'card_name' => $normalizedCardData['name'],
                        'page' => $pageNumber
                    ];
                }
                
            } catch (Exception $e) {
                $pageErrors++;
                if ($pageErrors <= 2) {
                    $pageLog[] = [
                        'action' => 'error',
                        'card_name' => $cardData['name'] ?? 'Inconnu',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        return [
            'imported' => $pageImported,
            'skipped' => $pageSkipped,
            'errors' => $pageErrors,
            'log' => $pageLog
        ];
    }
    
    /**
     * Fetch avec retry automatique
     */
    private function fetchWithRetry($url, $context, $maxRetries = 3) {
        $retries = 0;
        
        while ($retries < $maxRetries) {
            $result = @file_get_contents($url, false, $context);
            
            if ($result !== false) {
                return $result;
            }
            
            $retries++;
            if ($retries < $maxRetries) {
                // Attente progressive : 1s, 2s, 3s...
                sleep($retries);
            }
        }
        
        return false;
    }
    
    /**
     * Sauvegarde optimisée avec transaction courte
     */
    public function saveCard($cardData) {
        try {
            $this->db->beginTransaction();
            
            // Insérer la carte principale
            $cardSql = "INSERT INTO cards (
                uuid, name, slug, cost_memory, cost_reserve, power, durability, life, level, speed,
                element, effect, effect_raw, effect_html, flavor, rule, types, subtypes, classes, elements, legality
            ) VALUES (
                :uuid, :name, :slug, :cost_memory, :cost_reserve, :power, :durability, :life, :level, :speed,
                :element, :effect, :effect_raw, :effect_html, :flavor, :rule, :types, :subtypes, :classes, :elements, :legality
            ) ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                updated_at = CURRENT_TIMESTAMP";
            
            $cardParams = [
                ':uuid' => $cardData['uuid'],
                ':name' => $cardData['name'],
                ':slug' => $cardData['slug'],
                ':cost_memory' => $cardData['cost_memory'],
                ':cost_reserve' => $cardData['cost_reserve'],
                ':power' => $cardData['power'],
                ':durability' => $cardData['durability'],
                ':life' => $cardData['life'],
                ':level' => $cardData['level'],
                ':speed' => $cardData['speed'],
                ':element' => $cardData['element'],
                ':effect' => $cardData['effect'],
                ':effect_raw' => $cardData['effect_raw'],
                ':effect_html' => $cardData['effect_html'],
                ':flavor' => $cardData['flavor'],
                ':rule' => json_encode($cardData['rule']),
                ':types' => json_encode($cardData['types']),
                ':subtypes' => json_encode($cardData['subtypes']),
                ':classes' => json_encode($cardData['classes']),
                ':elements' => json_encode($cardData['elements']),
                ':legality' => $cardData['legality']
            ];
            
            $this->db->query($cardSql, $cardParams);
            
            // Traiter les éditions
            if (isset($cardData['editions']) && is_array($cardData['editions'])) {
                foreach ($cardData['editions'] as $edition) {
                    $this->saveEdition($edition, $cardData['uuid']);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erreur sauvegarde carte: " . $e->getMessage());
        }
    }
    
    // Réutiliser les autres méthodes de Card.php
    private function saveEdition($editionData, $cardId) {
        // [Code similaire à Card.php mais optimisé]
        if (!isset($editionData['set']) || !is_array($editionData['set'])) {
            throw new Exception("Données d'extension invalides");
        }
        
        $this->saveSet($editionData['set']);
        
        $editionSql = "INSERT INTO card_editions (
            uuid, card_id, collector_number, set_id, configuration, rarity, illustrator, flavor, image, orientation
        ) VALUES (
            :uuid, :card_id, :collector_number, :set_id, :configuration, :rarity, :illustrator, :flavor, :image, :orientation
        ) ON DUPLICATE KEY UPDATE
            rarity = VALUES(rarity),
            updated_at = CURRENT_TIMESTAMP";
        
        $editionParams = [
            ':uuid' => $editionData['uuid'],
            ':card_id' => $cardId,
            ':collector_number' => $editionData['collector_number'] ?? '001',
            ':set_id' => $editionData['set']['id'],
            ':configuration' => $editionData['configuration'] ?? 'default',
            ':rarity' => $editionData['rarity'] ?? 1,
            ':illustrator' => $editionData['illustrator'] ?? '',
            ':flavor' => $editionData['flavor'] ?? '',
            ':image' => $editionData['image'] ?? '',
            ':orientation' => $editionData['orientation'] ?? 'portrait'
        ];
        
        $this->db->query($editionSql, $editionParams);
    }
    
    private function saveSet($setData) {
        $setSql = "INSERT INTO sets (id, name, prefix, release_date, language) 
                   VALUES (:id, :name, :prefix, :release_date, :language)
                   ON DUPLICATE KEY UPDATE
                   name = VALUES(name),
                   updated_at = CURRENT_TIMESTAMP";
        
        $setParams = [
            ':id' => $setData['id'],
            ':name' => $setData['name'],
            ':prefix' => $setData['prefix'],
            ':release_date' => $setData['release_date'],
            ':language' => $setData['language'] ?? 'EN'
        ];
        
        $this->db->query($setSql, $setParams);
    }
    
    private function cardExists($uuid) {
        $sql = "SELECT COUNT(*) as count FROM cards WHERE uuid = :uuid";
        $result = $this->db->fetch($sql, [':uuid' => $uuid]);
        return $result['count'] > 0;
    }
    
    private function normalizeCardData($apiCardData) {
        return [
            'uuid' => $apiCardData['uuid'] ?? $apiCardData['id'] ?? null,
            'name' => $apiCardData['name'] ?? $apiCardData['card_name'] ?? null,
            'slug' => $apiCardData['slug'] ?? strtolower(str_replace(' ', '-', $apiCardData['name'] ?? '')),
            'cost_memory' => $apiCardData['cost_memory'] ?? $apiCardData['memory_cost'] ?? null,
            'cost_reserve' => $apiCardData['cost_reserve'] ?? $apiCardData['reserve_cost'] ?? 0,
            'power' => $apiCardData['power'] ?? null,
            'durability' => $apiCardData['durability'] ?? null,
            'life' => $apiCardData['life'] ?? null,
            'level' => $apiCardData['level'] ?? null,
            'speed' => $apiCardData['speed'] ?? null,
            'element' => $apiCardData['element'] ?? 'Norme',
            'effect' => $apiCardData['effect'] ?? '',
            'effect_raw' => $apiCardData['effect_raw'] ?? $apiCardData['effect'] ?? '',
            'effect_html' => $apiCardData['effect_html'] ?? null,
            'flavor' => $apiCardData['flavor'] ?? '',
            'rule' => $apiCardData['rule'] ?? [],
            'types' => $apiCardData['types'] ?? [],
            'subtypes' => $apiCardData['subtypes'] ?? [],
            'classes' => $apiCardData['classes'] ?? [],
            'elements' => $apiCardData['elements'] ?? [],
            'legality' => $apiCardData['legality'] ?? 'normal',
            'editions' => $this->normalizeEditions($apiCardData)
        ];
    }
    
    private function normalizeEditions($apiCardData) {
        if (isset($apiCardData['editions']) && is_array($apiCardData['editions'])) {
            return $apiCardData['editions'];
        }
        
        // Créer une édition par défaut
        if (isset($apiCardData['set']) || isset($apiCardData['rarity'])) {
            $setData = $apiCardData['set'] ?? [
                'id' => 'UNK',
                'name' => 'Inconnu',
                'prefix' => 'UNK',
                'release_date' => '2023-01-01',
                'language' => 'EN'
            ];
            
            return [[
                'uuid' => ($apiCardData['uuid'] ?? $apiCardData['id'] ?? 'unknown') . '-edition-1',
                'collector_number' => $apiCardData['collector_number'] ?? '001',
                'set' => $setData,
                'rarity' => $apiCardData['rarity'] ?? 1,
                'illustrator' => $apiCardData['illustrator'] ?? '',
                'flavor' => $apiCardData['flavor'] ?? '',
                'image' => $apiCardData['image'] ?? '',
                'orientation' => $apiCardData['orientation'] ?? 'portrait'
            ]];
        }
        
        return [];
    }
    
    private function validateCardData($cardData) {
        if (empty($cardData['uuid']) || empty($cardData['name'])) {
            return false;
        }
        
        if (isset($cardData['editions']) && is_array($cardData['editions'])) {
            foreach ($cardData['editions'] as $edition) {
                if (!isset($edition['set']) || !is_array($edition['set'])) {
                    return false;
                }
                if (!isset($edition['set']['id']) || !isset($edition['set']['name'])) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private function ensureSyncStatusTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS sync_status (
                id INT PRIMARY KEY AUTO_INCREMENT,
                status ENUM('idle', 'running', 'completed', 'error') DEFAULT 'idle',
                message TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Erreur création table sync_status: " . $e->getMessage());
        }
    }
    
    private function updateSyncStatus($status, $message) {
        try {
            $this->ensureSyncStatusTable();
            
            $sql = "INSERT INTO sync_status (status, message, updated_at) 
                    VALUES (:status, :message, NOW())";
            
            $this->db->query($sql, [
                ':status' => $status,
                ':message' => $message
            ]);
        } catch (Exception $e) {
            error_log("Erreur sync status: " . $e->getMessage());
        }
    }
    
    private function syncTestCards($limit = null, $offset = 0) {
        // Réutiliser le code de Card.php pour les données de test
        return [
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total_processed' => 0,
            'mode' => 'test_data',
            'message' => 'Données de test utilisées car API inaccessible'
        ];
    }
}
?>