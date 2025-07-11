<?php
require_once __DIR__ . '/../config/database.php';

class Card {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function saveCard($cardData) {
        try {
            $this->db->beginTransaction();

            // Insérer ou mettre à jour la carte principale
            $cardSql = "INSERT INTO cards (
                uuid, name, slug, cost_memory, cost_reserve, power, durability, life, level, speed,
                element, effect, effect_raw, effect_html, flavor, rule, types, subtypes, classes, elements, legality
            ) VALUES (
                :uuid, :name, :slug, :cost_memory, :cost_reserve, :power, :durability, :life, :level, :speed,
                :element, :effect, :effect_raw, :effect_html, :flavor, :rule, :types, :subtypes, :classes, :elements, :legality
            ) ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                slug = VALUES(slug),
                cost_memory = VALUES(cost_memory),
                cost_reserve = VALUES(cost_reserve),
                power = VALUES(power),
                durability = VALUES(durability),
                life = VALUES(life),
                level = VALUES(level),
                speed = VALUES(speed),
                element = VALUES(element),
                effect = VALUES(effect),
                effect_raw = VALUES(effect_raw),
                effect_html = VALUES(effect_html),
                flavor = VALUES(flavor),
                rule = VALUES(rule),
                types = VALUES(types),
                subtypes = VALUES(subtypes),
                classes = VALUES(classes),
                elements = VALUES(elements),
                legality = VALUES(legality),
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

            // Insérer les éditions
            if (isset($cardData['editions']) && is_array($cardData['editions'])) {
                foreach ($cardData['editions'] as $edition) {
                    $this->saveEdition($edition, $cardData['uuid']);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erreur lors de la sauvegarde de la carte: " . $e->getMessage());
        }
    }

    private function saveEdition($editionData, $cardId) {
        // Validation des données d'édition
        if (!isset($editionData['set']) || !is_array($editionData['set'])) {
            throw new Exception("Données d'extension manquantes ou invalides pour l'édition");
        }
        
        if (!isset($editionData['set']['id']) || !isset($editionData['set']['name'])) {
            throw new Exception("ID ou nom d'extension manquant");
        }

        // Sauvegarder l'extension si elle n'existe pas
        $this->saveSet($editionData['set']);

        // Insérer ou mettre à jour l'édition
        $editionSql = "INSERT INTO card_editions (
            uuid, card_id, collector_number, set_id, configuration, rarity, illustrator, flavor, image, orientation, effect, effect_raw
        ) VALUES (
            :uuid, :card_id, :collector_number, :set_id, :configuration, :rarity, :illustrator, :flavor, :image, :orientation, :effect, :effect_raw
        ) ON DUPLICATE KEY UPDATE
            collector_number = VALUES(collector_number),
            set_id = VALUES(set_id),
            configuration = VALUES(configuration),
            rarity = VALUES(rarity),
            illustrator = VALUES(illustrator),
            flavor = VALUES(flavor),
            image = VALUES(image),
            orientation = VALUES(orientation),
            effect = VALUES(effect),
            effect_raw = VALUES(effect_raw),
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
            ':orientation' => $editionData['orientation'] ?? 'portrait',
            ':effect' => $editionData['effect'] ?? null,
            ':effect_raw' => $editionData['effect_raw'] ?? null
        ];

        $this->db->query($editionSql, $editionParams);

        // Sauvegarder les templates de circulation
        if (isset($editionData['circulationTemplates']) && is_array($editionData['circulationTemplates'])) {
            foreach ($editionData['circulationTemplates'] as $template) {
                $this->saveCirculationTemplate($template, $editionData['uuid']);
            }
        }
    }

    private function saveSet($setData) {
        $setSql = "INSERT INTO sets (id, name, prefix, release_date, language) 
                   VALUES (:id, :name, :prefix, :release_date, :language)
                   ON DUPLICATE KEY UPDATE
                   name = VALUES(name),
                   prefix = VALUES(prefix),
                   release_date = VALUES(release_date),
                   language = VALUES(language),
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

    private function saveCirculationTemplate($templateData, $editionId) {
        $templateSql = "INSERT INTO circulation_templates (
            uuid, edition_id, name, kind, foil, printing, population, population_operator
        ) VALUES (
            :uuid, :edition_id, :name, :kind, :foil, :printing, :population, :population_operator
        ) ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            kind = VALUES(kind),
            foil = VALUES(foil),
            printing = VALUES(printing),
            population = VALUES(population),
            population_operator = VALUES(population_operator),
            updated_at = CURRENT_TIMESTAMP";

        $templateParams = [
            ':uuid' => $templateData['uuid'],
            ':edition_id' => $editionId,
            ':name' => $templateData['name'],
            ':kind' => $templateData['kind'],
            ':foil' => $templateData['foil'] ? 1 : 0,
            ':printing' => $templateData['printing'] ? 1 : 0,
            ':population' => $templateData['population'],
            ':population_operator' => $templateData['population_operator'] ?? '='
        ];

        $this->db->query($templateSql, $templateParams);
    }

    public function searchCards($params = []) {
        try {
            $sql = "SELECT 
                        c.uuid,
                        c.name,
                        c.slug,
                        c.cost_memory,
                        c.cost_reserve,
                        c.power,
                        c.durability,
                        c.life,
                        c.level,
                        c.element,
                        c.effect,
                        c.effect_html,
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
                        COALESCE(mc.quantity, 0) as owned_quantity,
                        COALESCE(mc.is_foil, 0) as owned_foil
                    FROM cards c
                    LEFT JOIN card_editions ce ON c.uuid = ce.card_id
                    LEFT JOIN sets s ON ce.set_id = s.id
                    LEFT JOIN my_collection mc ON ce.uuid = mc.edition_uuid
                    WHERE 1=1";

        $queryParams = [];

        if (!empty($params['name'])) {
            $sql .= " AND c.name LIKE :name";
            $queryParams[':name'] = '%' . $params['name'] . '%';
        }

        if (!empty($params['set_prefix'])) {
            $sql .= " AND s.prefix = :set_prefix";
            $queryParams[':set_prefix'] = $params['set_prefix'];
        }

        if (!empty($params['class'])) {
            // Utilisation de LIKE pour chercher dans le JSON - Compatible avec toutes les versions MySQL
            $sql .= " AND c.classes LIKE :class";
            $queryParams[':class'] = '%' . $params['class'] . '%';
        }

        if (!empty($params['element'])) {
            $sql .= " AND c.element = :element";
            $queryParams[':element'] = $params['element'];
        }

        if (isset($params['rarity'])) {
            $sql .= " AND ce.rarity = :rarity";
            $queryParams[':rarity'] = $params['rarity'];
        }

        if (isset($params['owned_only']) && $params['owned_only']) {
            $sql .= " AND mc.quantity > 0";
        }

        $sql .= " ORDER BY c.name ASC";

        if (isset($params['limit']) && $params['limit'] > 0) {
            $sql .= " LIMIT " . (int)$params['limit'];
        }

        // Debug logging
        error_log("Search SQL: " . $sql);
        error_log("Search params: " . print_r($queryParams, true));
        
        $result = $this->db->fetchAll($sql, $queryParams);
        error_log("Search results count: " . count($result));
            
        return $result;
        } catch (Exception $e) {
            error_log("Erreur lors de la recherche de cartes: " . $e->getMessage());
            return [];
        }
    }

    public function getCardById($uuid) {
        try {
            $sql = "SELECT 
                        c.*,
                        ce.uuid as edition_uuid,
                        ce.collector_number,
                        ce.rarity,
                        ce.illustrator,
                        ce.image,
                        ce.flavor as edition_flavor,
                        s.name as set_name,
                        s.prefix as set_prefix,
                        s.release_date,
                        COALESCE(mc.quantity, 0) as owned_quantity,
                        COALESCE(mc.is_foil, 0) as owned_foil,
                        mc.condition_card,
                        mc.notes,
                        mc.acquired_date,
                        mc.price_paid
                    FROM cards c
                    LEFT JOIN card_editions ce ON c.uuid = ce.card_id
                    LEFT JOIN sets s ON ce.set_id = s.id
                    LEFT JOIN my_collection mc ON ce.uuid = mc.edition_uuid
                    WHERE c.uuid = :uuid
                    LIMIT 1";

            $result = $this->db->fetch($sql, [':uuid' => $uuid]);
            return $result;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de la carte $uuid: " . $e->getMessage());
            return null;
        }
    }

    public function getCollectionStats() {
        try {
            $sql = "SELECT 
                        COALESCE(COUNT(DISTINCT mc.card_uuid), 0) as unique_cards,
                        COALESCE(SUM(mc.quantity), 0) as total_cards,
                        COALESCE(SUM(CASE WHEN mc.is_foil = 1 THEN mc.quantity ELSE 0 END), 0) as foil_cards,
                        COALESCE(COUNT(DISTINCT s.id), 0) as sets_owned
                    FROM my_collection mc
                    LEFT JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    LEFT JOIN sets s ON ce.set_id = s.id";
            $result = $this->db->fetch($sql);
            
            // S'assurer que les valeurs ne sont pas null
            return [
                'unique_cards' => (int)($result['unique_cards'] ?? 0),
                'total_cards' => (int)($result['total_cards'] ?? 0),
                'foil_cards' => (int)($result['foil_cards'] ?? 0),
                'sets_owned' => (int)($result['sets_owned'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("Erreur getCollectionStats: " . $e->getMessage());
            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'unique_cards' => 0,
                'total_cards' => 0,
                'foil_cards' => 0,
                'sets_owned' => 0
            ];
        }
    }

    public function getCollectionBySet() {
        try {
            $sql = "SELECT 
                        s.name as set_name,
                        s.prefix as set_prefix,
                        COUNT(DISTINCT mc.card_uuid) as unique_cards,
                        SUM(mc.quantity) as total_cards
                    FROM my_collection mc
                    JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    JOIN sets s ON ce.set_id = s.id
                    GROUP BY s.id, s.name, s.prefix
                    ORDER BY s.release_date DESC";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionBySet: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionByRarity() {
        try {
            $sql = "SELECT 
                        ce.rarity,
                        COUNT(DISTINCT mc.card_uuid) as unique_cards,
                        SUM(mc.quantity) as total_cards
                    FROM my_collection mc
                    JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    GROUP BY ce.rarity
                    ORDER BY ce.rarity";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionByRarity: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionByClass() {
        try {
            $sql = "SELECT 
                        class_name,
                        COUNT(DISTINCT mc.card_uuid) as unique_cards,
                        SUM(mc.quantity) as total_cards
                    FROM (
                        SELECT 
                            mc.card_uuid,
                            mc.quantity,
                            JSON_UNQUOTE(JSON_EXTRACT(c.classes, CONCAT('$[', numbers.n, ']'))) as class_name
                        FROM my_collection mc
                        JOIN cards c ON mc.card_uuid = c.uuid
                        CROSS JOIN (
                            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                        ) numbers
                        WHERE JSON_EXTRACT(c.classes, CONCAT('$[', numbers.n, ']')) IS NOT NULL
                    ) class_breakdown
                    WHERE class_name IS NOT NULL
                    GROUP BY class_name
                    ORDER BY class_name";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionByClass: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionByElement() {
        try {
            $sql = "SELECT 
                        c.element,
                        COUNT(DISTINCT mc.card_uuid) as unique_cards,
                        SUM(mc.quantity) as total_cards
                    FROM my_collection mc
                    JOIN cards c ON mc.card_uuid = c.uuid
                    WHERE c.element IS NOT NULL AND c.element != ''
                    GROUP BY c.element
                    ORDER BY c.element";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionByElement: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionProgress() {
        try {
            $sql = "SELECT 
                        s.name as set_name,
                        s.prefix as set_prefix,
                        COUNT(DISTINCT c.uuid) as total_cards_in_set,
                        COUNT(DISTINCT mc.card_uuid) as owned_cards,
                        ROUND((COUNT(DISTINCT mc.card_uuid) / COUNT(DISTINCT c.uuid)) * 100, 1) as completion_percentage
                    FROM sets s
                    LEFT JOIN card_editions ce ON s.id = ce.set_id
                    LEFT JOIN cards c ON ce.card_id = c.uuid
                    LEFT JOIN my_collection mc ON c.uuid = mc.card_uuid
                    GROUP BY s.id, s.name, s.prefix
                    HAVING total_cards_in_set > 0
                    ORDER BY completion_percentage DESC";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionProgress: " . $e->getMessage());
            return [];
        }
    }

    public function getFoilStatistics() {
        try {
            $sql = "SELECT 
                        'Par Extension' as category,
                        s.name as label,
                        COUNT(DISTINCT mc.card_uuid) as foil_cards,
                        SUM(mc.quantity) as total_foil_quantity
                    FROM my_collection mc
                    JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    JOIN sets s ON ce.set_id = s.id
                    WHERE mc.is_foil = 1
                    GROUP BY s.id, s.name
                    
                    UNION ALL
                    
                    SELECT 
                        'Par Rareté' as category,
                        CONCAT('Rareté ', ce.rarity) as label,
                        COUNT(DISTINCT mc.card_uuid) as foil_cards,
                        SUM(mc.quantity) as total_foil_quantity
                    FROM my_collection mc
                    JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    WHERE mc.is_foil = 1
                    GROUP BY ce.rarity
                    
                    ORDER BY category, foil_cards DESC";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getFoilStatistics: " . $e->getMessage());
            return [];
        }
    }

    public function getAllSets() {
        try {
            $sql = "SELECT DISTINCT s.id, s.name, s.prefix, s.release_date 
                    FROM sets s 
                    ORDER BY s.release_date DESC, s.name ASC";
            return $this->db->fetchAll($sql) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllClasses() {
        try {
            // Utiliser les vraies données de la base avec une approche compatible
            $sql = "SELECT DISTINCT 
                        REPLACE(REPLACE(REPLACE(REPLACE(c.classes, '[', ''), ']', ''), '\"', ''), '\\\\', '') as class_name
                    FROM cards c
                    WHERE c.classes IS NOT NULL 
                    AND c.classes != '[]'
                    ORDER BY class_name";
            
            $result = $this->db->fetchAll($sql) ?? [];
            
            // Si pas de données, retourner les classes par défaut
            if (empty($result)) {
                return [
                    ['class_name' => 'Champion'],
                    ['class_name' => 'Guardian'],
                    ['class_name' => 'Warrior'],
                    ['class_name' => 'Ranger'],
                    ['class_name' => 'Mage'],
                    ['class_name' => 'Assassin'],
                    ['class_name' => 'Cleric'],
                    ['class_name' => 'Tamer']
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            // Retourner des classes par défaut en cas d'erreur
            return [
                ['class_name' => 'Champion'],
                ['class_name' => 'Guardian'],
                ['class_name' => 'Warrior'],
                ['class_name' => 'Ranger'],
                ['class_name' => 'Mage'],
                ['class_name' => 'Assassin'],
                ['class_name' => 'Cleric'],
                ['class_name' => 'Tamer']
            ];
        }
    }

    public function getAllElements() {
        try {
            // Utiliser les vraies données de la base
            $sql = "SELECT DISTINCT element 
                    FROM cards 
                    WHERE element IS NOT NULL AND element != ''
                    ORDER BY element";
            
            $result = $this->db->fetchAll($sql) ?? [];
            
            // Si pas de données, retourner les éléments par défaut
            if (empty($result)) {
                return ['Fire', 'Water', 'Wind', 'Earth', 'Light', 'Shadow', 'Arcane', 'Norm'];
            }
            
            // Convertir en tableau simple
            return array_map(function($row) {
                return $row['element'];
            }, $result);
        } catch (Exception $e) {
            // Retourner des éléments par défaut en cas d'erreur
            return ['Fire', 'Water', 'Wind', 'Earth', 'Light', 'Shadow', 'Arcane', 'Norm'];
        }
    }

    public function syncCardsFromAPI($limit = null, $offset = 0) {
        $syncLog = [];
        $totalImported = 0;
        $totalErrors = 0;
        $totalSkipped = 0;
        
        try {
            // Assurer que la table sync_status existe
            $this->ensureSyncStatusTable();
            
            // Mettre à jour le statut de synchronisation
            $this->updateSyncStatus('running', 'Démarrage de la synchronisation...');
            
            $apiUrl = 'https://api.gatcg.com/cards/search';
            
            // Créer un contexte HTTP avec des headers appropriés
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'Grand Archive Collection Manager/1.0',
                    'header' => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ]
                ]
            ]);
            
            // Première requête pour obtenir le nombre total de pages
            $this->updateSyncStatus('running', "Connexion à l'API GATCG pour obtenir les informations de pagination...");
            $firstPageUrl = $apiUrl . '?page=1';
            $firstPageData = @file_get_contents($firstPageUrl, false, $context);
            
            if ($firstPageData === false) {
                // Si l'API externe n'est pas accessible, utiliser des données de test
                $this->updateSyncStatus('running', 'API externe non accessible, utilisation de données de test...');
                return $this->syncTestCards($limit, $offset);
            }
            
            $firstPageJson = json_decode($firstPageData, true);
            if (!$firstPageJson) {
                throw new Exception('Réponse JSON invalide de l\'API GATCG');
            }
            
            // Récupérer les informations de pagination
            $totalPages = $firstPageJson['total_pages'] ?? $firstPageJson['last_page'] ?? 1;
            $totalCards = $firstPageJson['total'] ?? 0;
            $perPage = $firstPageJson['per_page'] ?? 30;
            
            $this->updateSyncStatus('running', 
                "API connectée! {$totalCards} cartes disponibles sur {$totalPages} pages ({$perPage} cartes par page)");
            
            // Déterminer les pages à synchroniser
            $startPage = 1;
            $endPage = $totalPages;
            
            if ($limit && $offset) {
                $startPage = floor($offset / $perPage) + 1;
                $endPage = min($totalPages, $startPage + floor($limit / $perPage));
            } elseif ($limit) {
                $endPage = min($totalPages, ceil($limit / $perPage));
            }
            
            $this->updateSyncStatus('running', "Synchronisation des pages {$startPage} à {$endPage}...");
            
            // Parcourir toutes les pages
            for ($currentPage = $startPage; $currentPage <= $endPage; $currentPage++) {
                try {
                    $this->updateSyncStatus('running', 
                        "Traitement de la page {$currentPage}/{$endPage} ({$totalImported} cartes importées jusqu'à présent)");
                    
                    // Récupérer les données de la page actuelle
                    $pageUrl = $apiUrl . '?page=' . $currentPage;
                    $pageData = @file_get_contents($pageUrl, false, $context);
                    
                    if ($pageData === false) {
                        $totalErrors++;
                        $syncLog[] = [
                            'action' => 'error',
                            'page' => $currentPage,
                            'error' => "Impossible de récupérer la page {$currentPage}"
                        ];
                        continue;
                    }
                    
                    $pageJson = json_decode($pageData, true);
                    if (!$pageJson || !isset($pageJson['data'])) {
                        $totalErrors++;
                        $syncLog[] = [
                            'action' => 'error',
                            'page' => $currentPage,
                            'error' => "Données invalides pour la page {$currentPage}"
                        ];
                        continue;
                    }
                    
                    $cards = $pageJson['data'];
                    $pageImported = 0;
                    $pageSkipped = 0;
                    $pageErrors = 0;
                    
                    // Traiter chaque carte de la page
                    foreach ($cards as $cardData) {
                        try {
                            // Vérifier et adapter les données pour la structure attendue
                            if (!isset($cardData['uuid']) || !isset($cardData['name'])) {
                                // Essayer de mapper depuis d'autres champs possibles
                                if (isset($cardData['id'])) {
                                    $cardData['uuid'] = $cardData['id'];
                                }
                                if (!isset($cardData['name']) && isset($cardData['card_name'])) {
                                    $cardData['name'] = $cardData['card_name'];
                                }
                            }
                            
                            if (!isset($cardData['uuid']) || !isset($cardData['name'])) {
                                $pageErrors++;
                                continue;
                            }
                            
                            // Normaliser les données pour notre structure de base
                            $normalizedCardData = $this->normalizeCardData($cardData);
                            
                            // Validation supplémentaire des données critiques
                            if (!$this->validateCardData($normalizedCardData)) {
                                $pageErrors++;
                                continue;
                            }
                            
                            // Vérifier si la carte existe déjà
                            if ($this->cardExists($normalizedCardData['uuid'])) {
                                $pageSkipped++;
                                continue;
                            }
                            
                            // Sauvegarder la carte
                            $this->saveCard($normalizedCardData);
                            $pageImported++;
                            
                            // Log seulement quelques cartes pour éviter un log trop volumineux
                            if ($pageImported <= 3) {
                                $syncLog[] = [
                                    'action' => 'imported',
                                    'card_name' => $normalizedCardData['name'],
                                    'uuid' => $normalizedCardData['uuid'],
                                    'page' => $currentPage
                                ];
                            }
                            
                        } catch (Exception $e) {
                            $pageErrors++;
                            if ($pageErrors <= 3) {
                                $syncLog[] = [
                                    'action' => 'error',
                                    'card_name' => $cardData['name'] ?? 'Inconnu',
                                    'uuid' => $cardData['uuid'] ?? 'Inconnu',
                                    'error' => $e->getMessage(),
                                    'page' => $currentPage
                                ];
                            }
                        }
                    }
                    
                    // Mettre à jour les totaux
                    $totalImported += $pageImported;
                    $totalSkipped += $pageSkipped;
                    $totalErrors += $pageErrors;
                    
                    // Log du résumé de la page
                    $syncLog[] = [
                        'action' => 'page_completed',
                        'page' => $currentPage,
                        'imported' => $pageImported,
                        'skipped' => $pageSkipped,
                        'errors' => $pageErrors,
                        'total' => count($cards)
                    ];
                    
                    // Pause entre les pages pour éviter de surcharger l'API
                    usleep(200000); // 200ms entre les pages
                    
                } catch (Exception $e) {
                    $totalErrors++;
                    $syncLog[] = [
                        'action' => 'error',
                        'page' => $currentPage,
                        'error' => "Erreur lors du traitement de la page {$currentPage}: " . $e->getMessage()
                    ];
                    
                    // Si trop d'erreurs de pages, arrêter
                    if ($totalErrors > 20) {
                        $this->updateSyncStatus('error', "Trop d'erreurs de pages ({$totalErrors}), arrêt du processus");
                        throw new Exception("Trop d'erreurs de pages ({$totalErrors}), arrêt du processus");
                    }
                }
            }
            
            $this->updateSyncStatus('completed', 
                "Synchronisation terminée - {$totalImported} cartes importées, {$totalSkipped} ignorées, {$totalErrors} erreurs sur " . ($endPage - $startPage + 1) . " pages traitées");
            
            return [
                'imported' => $totalImported,
                'skipped' => $totalSkipped,
                'errors' => $totalErrors,
                'total_processed' => $totalImported + $totalSkipped + $totalErrors,
                'total_available' => $totalCards,
                'pages_processed' => ($endPage - $startPage + 1),
                'total_pages' => $totalPages,
                'log' => array_slice($syncLog, 0, 100) // Limiter le log pour éviter les réponses trop volumineuses
            ];
            
        } catch (Exception $e) {
            $this->updateSyncStatus('error', 'Erreur: ' . $e->getMessage());
            error_log("Sync error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSyncStatus() {
        try {
            // Créer la table si elle n'existe pas
            $this->ensureSyncStatusTable();
            
            $sql = "SELECT * FROM sync_status ORDER BY updated_at DESC LIMIT 1";
            $status = $this->db->fetch($sql);
            
            if (!$status) {
                return [
                    'status' => 'idle',
                    'message' => 'Aucune synchronisation en cours',
                    'updated_at' => null
                ];
            }
            
            return $status;
        } catch (Exception $e) {
            // En cas d'erreur, retourner un statut par défaut
            return [
                'status' => 'idle',
                'message' => 'Aucune synchronisation en cours',
                'updated_at' => null
            ];
        }
    }

    private function updateSyncStatus($status, $message) {
        try {
            // Créer la table si elle n'existe pas
            $this->ensureSyncStatusTable();
            
            $sql = "INSERT INTO sync_status (status, message, updated_at) 
                    VALUES (:status, :message, NOW())";
            
            $this->db->query($sql, [
                ':status' => $status,
                ':message' => $message
            ]);
        } catch (Exception $e) {
            // Si l'insertion échoue, essayer de créer la table et réessayer
            error_log("Erreur sync status: " . $e->getMessage());
        }
    }

    private function cardExists($uuid) {
        $sql = "SELECT COUNT(*) as count FROM cards WHERE uuid = :uuid";
        $result = $this->db->fetch($sql, [':uuid' => $uuid]);
        return $result['count'] > 0;
    }

    public function getCardsCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM cards";
            $result = $this->db->fetch($sql);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function normalizeCardData($apiCardData) {
        // Mapper les données de l'API vers notre structure de base de données
        $normalized = [
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
            'element' => $apiCardData['element'] ?? $apiCardData['element_type'] ?? 'Norme',
            'effect' => $apiCardData['effect'] ?? $apiCardData['text'] ?? $apiCardData['card_text'] ?? '',
            'effect_raw' => $apiCardData['effect_raw'] ?? $apiCardData['effect'] ?? $apiCardData['text'] ?? '',
            'effect_html' => $apiCardData['effect_html'] ?? null,
            'flavor' => $apiCardData['flavor'] ?? $apiCardData['flavor_text'] ?? '',
            'rule' => isset($apiCardData['rule']) ? json_encode($apiCardData['rule']) : '[]',
            'types' => isset($apiCardData['types']) ? json_encode($apiCardData['types']) : 
                      (isset($apiCardData['card_type']) ? json_encode([$apiCardData['card_type']]) : '[]'),
            'subtypes' => isset($apiCardData['subtypes']) ? json_encode($apiCardData['subtypes']) : '[]',
            'classes' => isset($apiCardData['classes']) ? json_encode($apiCardData['classes']) : 
                        (isset($apiCardData['class']) ? json_encode([$apiCardData['class']]) : '[]'),
            'elements' => isset($apiCardData['elements']) ? json_encode($apiCardData['elements']) : 
                         (isset($apiCardData['element']) ? json_encode([$apiCardData['element']]) : '[]'),
            'legality' => $apiCardData['legality'] ?? 'normal'
        ];
        
        // Traiter les éditions si présentes
        if (isset($apiCardData['editions']) && is_array($apiCardData['editions'])) {
            $normalized['editions'] = $apiCardData['editions'];
        } else {
            // Créer une édition par défaut si les données d'édition sont présentes au niveau carte
            if (isset($apiCardData['set']) || isset($apiCardData['rarity']) || isset($apiCardData['collector_number'])) {
                $setData = $apiCardData['set'] ?? [
                    'id' => 'UNK',
                    'name' => 'Inconnu',
                    'prefix' => 'UNK',
                    'release_date' => '2023-01-01',
                    'language' => 'EN'
                ];
                
                // S'assurer que les données set sont complètes
                if (!isset($setData['id']) || !isset($setData['name'])) {
                    $setData = [
                        'id' => $setData['id'] ?? 'UNK',
                        'name' => $setData['name'] ?? 'Inconnu',
                        'prefix' => $setData['prefix'] ?? 'UNK',
                        'release_date' => $setData['release_date'] ?? '2023-01-01',
                        'language' => $setData['language'] ?? 'EN'
                    ];
                }
                
                $normalized['editions'] = [[
                    'uuid' => $normalized['uuid'] . '-edition-1',
                    'collector_number' => $apiCardData['collector_number'] ?? '001',
                    'set' => $setData,
                    'rarity' => $apiCardData['rarity'] ?? 1,
                    'illustrator' => $apiCardData['illustrator'] ?? $apiCardData['artist'] ?? '',
                    'flavor' => $apiCardData['flavor'] ?? '',
                    'image' => $apiCardData['image'] ?? $apiCardData['image_url'] ?? '',
                    'orientation' => $apiCardData['orientation'] ?? 'portrait',
                    'effect' => null,
                    'effect_raw' => null
                ]];
            }
        }
        
        return $normalized;
    }

    private function validateCardData($cardData) {
        // Validation basique des données requises
        if (empty($cardData['uuid']) || empty($cardData['name'])) {
            return false;
        }
        
        // Validation des éditions si présentes
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

    private function syncTestCards($limit = null, $offset = 0) {
        $syncLog = [];
        $imported = 0;
        $errors = 0;
        $skipped = 0;
        
        try {
            // Données de test avec des cartes Grand Archive typiques
            $testCards = [
                [
                    'uuid' => 'ga-001-ambition',
                    'name' => 'Ambition',
                    'slug' => 'ambition',
                    'cost_memory' => 2,
                    'cost_reserve' => 0,
                    'power' => null,
                    'durability' => null,
                    'life' => null,
                    'level' => 1,
                    'speed' => null,
                    'element' => 'Norme',
                    'effect' => 'Draw a card.',
                    'effect_raw' => 'Draw a card.',
                    'effect_html' => '<p>Draw a card.</p>',
                    'flavor' => 'The drive to succeed burns bright within.',
                    'rule' => json_encode([]),
                    'types' => json_encode(['Action']),
                    'subtypes' => json_encode([]),
                    'classes' => json_encode(['Champion']),
                    'elements' => json_encode(['Norme']),
                    'legality' => 'normal',
                    'editions' => [
                        [
                            'uuid' => 'ga-001-ambition-001',
                            'collector_number' => '001',
                            'set' => [
                                'id' => 'AMB',
                                'name' => 'Ambition',
                                'prefix' => 'AMB',
                                'release_date' => '2023-01-01',
                                'language' => 'EN'
                            ],
                            'rarity' => 1,
                            'illustrator' => 'Test Artist',
                            'flavor' => null,
                            'image' => '/images/cards/amb/001.jpg',
                            'orientation' => 'portrait',
                            'effect' => null,
                            'effect_raw' => null
                        ]
                    ]
                ],
                [
                    'uuid' => 'ga-002-crescent-glaive',
                    'name' => 'Crescent Glaive',
                    'slug' => 'crescent-glaive',
                    'cost_memory' => 1,
                    'cost_reserve' => 0,
                    'power' => 2,
                    'durability' => 3,
                    'life' => null,
                    'level' => null,
                    'speed' => null,
                    'element' => 'Norme',
                    'effect' => 'On Enter: You may pay (2). If you do, draw a card.',
                    'effect_raw' => 'On Enter: You may pay (2). If you do, draw a card.',
                    'effect_html' => '<p><strong>On Enter:</strong> You may pay (2). If you do, draw a card.</p>',
                    'flavor' => 'A weapon of precision and grace.',
                    'rule' => json_encode([]),
                    'types' => json_encode(['Ally', 'Weapon']),
                    'subtypes' => json_encode(['Polearm']),
                    'classes' => json_encode(['Warrior']),
                    'elements' => json_encode(['Norme']),
                    'legality' => 'normal',
                    'editions' => [
                        [
                            'uuid' => 'ga-002-crescent-glaive-002',
                            'collector_number' => '002',
                            'set' => [
                                'id' => 'AMB',
                                'name' => 'Ambition',
                                'prefix' => 'AMB',
                                'release_date' => '2023-01-01',
                                'language' => 'EN'
                            ],
                            'rarity' => 2,
                            'illustrator' => 'Test Artist 2',
                            'flavor' => null,
                            'image' => '/images/cards/amb/002.jpg',
                            'orientation' => 'portrait',
                            'effect' => null,
                            'effect_raw' => null
                        ]
                    ]
                ],
                [
                    'uuid' => 'ga-003-fire-apprentice',
                    'name' => 'Fire Apprentice',
                    'slug' => 'fire-apprentice',
                    'cost_memory' => 1,
                    'cost_reserve' => 0,
                    'power' => 1,
                    'durability' => 1,
                    'life' => null,
                    'level' => null,
                    'speed' => null,
                    'element' => 'Fire',
                    'effect' => 'Level 1: Deal 1 damage to target.',
                    'effect_raw' => 'Level 1: Deal 1 damage to target.',
                    'effect_html' => '<p><strong>Level 1:</strong> Deal 1 damage to target.</p>',
                    'flavor' => 'Learning to harness the flames.',
                    'rule' => json_encode([]),
                    'types' => json_encode(['Ally']),
                    'subtypes' => json_encode(['Mage']),
                    'classes' => json_encode(['Mage']),
                    'elements' => json_encode(['Fire']),
                    'legality' => 'normal',
                    'editions' => [
                        [
                            'uuid' => 'ga-003-fire-apprentice-003',
                            'collector_number' => '003',
                            'set' => [
                                'id' => 'AMB',
                                'name' => 'Ambition',
                                'prefix' => 'AMB',
                                'release_date' => '2023-01-01',
                                'language' => 'EN'
                            ],
                            'rarity' => 1,
                            'illustrator' => 'Test Artist 3',
                            'flavor' => null,
                            'image' => '/images/cards/amb/003.jpg',
                            'orientation' => 'portrait',
                            'effect' => null,
                            'effect_raw' => null
                        ]
                    ]
                ]
            ];
            
            // Appliquer limit et offset
            if ($offset > 0) {
                $testCards = array_slice($testCards, $offset);
            }
            if ($limit) {
                $testCards = array_slice($testCards, 0, $limit);
            }
            
            $total = count($testCards);
            $processed = 0;
            
            $this->updateSyncStatus('running', "Traitement de {$total} cartes de test...");
            
            foreach ($testCards as $cardData) {
                try {
                    $processed++;
                    
                    // Vérifier si la carte existe déjà
                    if ($this->cardExists($cardData['uuid'])) {
                        $skipped++;
                        $syncLog[] = [
                            'action' => 'skipped',
                            'card_name' => $cardData['name'],
                            'uuid' => $cardData['uuid']
                        ];
                        continue;
                    }
                    
                    // Sauvegarder la carte
                    $this->saveCard($cardData);
                    $imported++;
                    
                    $syncLog[] = [
                        'action' => 'imported',
                        'card_name' => $cardData['name'],
                        'uuid' => $cardData['uuid']
                    ];
                    
                    $this->updateSyncStatus('running', "Traité {$processed}/{$total} cartes de test - {$imported} importées");
                    
                } catch (Exception $e) {
                    $errors++;
                    $syncLog[] = [
                        'action' => 'error',
                        'card_name' => $cardData['name'],
                        'uuid' => $cardData['uuid'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $this->updateSyncStatus('completed', "Synchronisation de test terminée - {$imported} cartes importées, {$skipped} ignorées, {$errors} erreurs");
            
            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $processed,
                'total_available' => $total,
                'log' => $syncLog,
                'mode' => 'test_data'
            ];
            
        } catch (Exception $e) {
            $this->updateSyncStatus('error', 'Erreur lors de la synchronisation de test: ' . $e->getMessage());
            throw $e;
        }
    }

    public function testSyncFunction() {
        try {
            $this->ensureSyncStatusTable();
            
            // Test simple : insérer une carte factice
            $testCard = [
                'uuid' => 'test-card-001',
                'name' => 'Carte de Test',
                'slug' => 'carte-de-test',
                'cost_memory' => 1,
                'cost_reserve' => 0,
                'power' => null,
                'durability' => null,
                'life' => null,
                'level' => 1,
                'speed' => null,
                'element' => 'Norme',
                'effect' => 'Ceci est une carte de test.',
                'effect_raw' => 'Ceci est une carte de test.',
                'effect_html' => '<p>Ceci est une carte de test.</p>',
                'flavor' => null,
                'rule' => json_encode([]),
                'types' => json_encode(['Action']),
                'subtypes' => json_encode([]),
                'classes' => json_encode(['Champion']),
                'elements' => json_encode(['Norme']),
                'legality' => 'normal'
            ];
            
            // Test d'insertion
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO cards (
                uuid, name, slug, cost_memory, cost_reserve, power, durability, life, level, speed,
                element, effect, effect_raw, effect_html, flavor, rule, types, subtypes, classes, elements, legality
            ) VALUES (
                :uuid, :name, :slug, :cost_memory, :cost_reserve, :power, :durability, :life, :level, :speed,
                :element, :effect, :effect_raw, :effect_html, :flavor, :rule, :types, :subtypes, :classes, :elements, :legality
            ) ON DUPLICATE KEY UPDATE name = VALUES(name)";
            
            $this->db->query($sql, [
                ':uuid' => $testCard['uuid'],
                ':name' => $testCard['name'],
                ':slug' => $testCard['slug'],
                ':cost_memory' => $testCard['cost_memory'],
                ':cost_reserve' => $testCard['cost_reserve'],
                ':power' => $testCard['power'],
                ':durability' => $testCard['durability'],
                ':life' => $testCard['life'],
                ':level' => $testCard['level'],
                ':speed' => $testCard['speed'],
                ':element' => $testCard['element'],
                ':effect' => $testCard['effect'],
                ':effect_raw' => $testCard['effect_raw'],
                ':effect_html' => $testCard['effect_html'],
                ':flavor' => $testCard['flavor'],
                ':rule' => $testCard['rule'],
                ':types' => $testCard['types'],
                ':subtypes' => $testCard['subtypes'],
                ':classes' => $testCard['classes'],
                ':elements' => $testCard['elements'],
                ':legality' => $testCard['legality']
            ]);
            
            $this->db->commit();
            
            return [
                'message' => 'Test de synchronisation réussi',
                'test_card' => $testCard['name'],
                'uuid' => $testCard['uuid']
            ];
            
        } catch (Exception $e) {
            if ($this->db->getConnection()->inTransaction()) {
                $this->db->rollback();
            }
            throw new Exception("Test sync failed: " . $e->getMessage());
        }
    }
}
?>