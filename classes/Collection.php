<?php
require_once __DIR__ . '/../config/database.php';

class Collection {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addToCollection($cardUuid, $editionUuid, $quantity = 1, $isFoil = false, $options = [], $userId = null) {
        try {
            if ($userId === null) {
                throw new Exception("ID utilisateur requis");
            }
            
            $sql = "INSERT INTO my_collection (
                user_id, card_uuid, edition_uuid, quantity, is_foil, condition_card, notes, acquired_date, price_paid
            ) VALUES (
                :user_id, :card_uuid, :edition_uuid, :quantity, :is_foil, :condition_card, :notes, :acquired_date, :price_paid
            ) ON DUPLICATE KEY UPDATE
                quantity = quantity + VALUES(quantity),
                condition_card = COALESCE(VALUES(condition_card), condition_card),
                notes = COALESCE(VALUES(notes), notes),
                acquired_date = COALESCE(VALUES(acquired_date), acquired_date),
                price_paid = COALESCE(VALUES(price_paid), price_paid),
                updated_at = CURRENT_TIMESTAMP";

            $params = [
                ':user_id' => $userId,
                ':card_uuid' => $cardUuid,
                ':edition_uuid' => $editionUuid,
                ':quantity' => $quantity,
                ':is_foil' => $isFoil ? 1 : 0,
                ':condition_card' => $options['condition'] ?? 'NEAR_MINT',
                ':notes' => $options['notes'] ?? null,
                ':acquired_date' => $options['acquired_date'] ?? null,
                ':price_paid' => $options['price_paid'] ?? null
            ];

            $this->db->query($sql, $params);
            return true;

        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'ajout à la collection: " . $e->getMessage());
        }
    }


    public function updateQuantity($cardUuid, $editionUuid, $isFoil = false, $newQuantity = 0, $isCsr = null, $userId = null) {
        try {
            // Vérifier si la colonne is_csr existe
            $csrExists = $this->columnExists('my_collection', 'is_csr');
            
            // Auto-détection CSR si colonne existe et non spécifié
            if ($csrExists && $isCsr === null) {
                $isCsr = $this->isCardCSR($editionUuid);
            } elseif (!$csrExists) {
                $isCsr = false; // Forcer à false si colonne n'existe pas
            }
            
            if ($userId === null) {
                throw new Exception("ID utilisateur requis");
            }
            
            if ($newQuantity <= 0) {
                return $this->removeFromCollection($cardUuid, $editionUuid, $isFoil, $isCsr, $userId);
            }

            if ($csrExists) {
                $sql = "INSERT INTO my_collection (
                    user_id, card_uuid, edition_uuid, quantity, is_foil, is_csr
                ) VALUES (
                    :user_id, :card_uuid, :edition_uuid, :quantity, :is_foil, :is_csr
                ) ON DUPLICATE KEY UPDATE
                    quantity = VALUES(quantity),
                    updated_at = CURRENT_TIMESTAMP";

                $params = [
                    ':user_id' => $userId,
                    ':card_uuid' => $cardUuid,
                    ':edition_uuid' => $editionUuid,
                    ':quantity' => $newQuantity,
                    ':is_foil' => $isFoil ? 1 : 0,
                    ':is_csr' => $isCsr ? 1 : 0
                ];
            } else {
                // Version legacy sans is_csr
                $sql = "INSERT INTO my_collection (
                    user_id, card_uuid, edition_uuid, quantity, is_foil
                ) VALUES (
                    :user_id, :card_uuid, :edition_uuid, :quantity, :is_foil
                ) ON DUPLICATE KEY UPDATE
                    quantity = VALUES(quantity),
                    updated_at = CURRENT_TIMESTAMP";

                $params = [
                    ':user_id' => $userId,
                    ':card_uuid' => $cardUuid,
                    ':edition_uuid' => $editionUuid,
                    ':quantity' => $newQuantity,
                    ':is_foil' => $isFoil ? 1 : 0
                ];
            }

            $this->db->query($sql, $params);
            return true;

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour de la quantité: " . $e->getMessage());
        }
    }

    public function isCardCSR($editionUuid) {
        try {
            $sql = "SELECT rarity, image FROM card_editions WHERE uuid = :edition_uuid";
            $result = $this->db->fetch($sql, [':edition_uuid' => $editionUuid]);
            
            if ($result) {
                // Critère principal : rareté 7 (cartes signées/CSR)
                if ($result['rarity'] == 7) {
                    return true;
                }
                
                // Critère secondaire : image contient -csr (fallback)
                if (isset($result['image']) && strpos($result['image'], '-csr') !== false) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeFromCollection($cardUuid, $editionUuid, $isFoil = false, $isCsr = false, $userId = null) {
        try {
            if ($userId === null) {
                throw new Exception("ID utilisateur requis");
            }
            
            $csrExists = $this->columnExists('my_collection', 'is_csr');
            
            if ($csrExists) {
                $sql = "DELETE FROM my_collection 
                        WHERE user_id = :user_id
                        AND card_uuid = :card_uuid 
                        AND edition_uuid = :edition_uuid 
                        AND is_foil = :is_foil 
                        AND is_csr = :is_csr";
                
                $params = [
                    ':user_id' => $userId,
                    ':card_uuid' => $cardUuid,
                    ':edition_uuid' => $editionUuid,
                    ':is_foil' => $isFoil ? 1 : 0,
                    ':is_csr' => $isCsr ? 1 : 0
                ];
            } else {
                // Version legacy sans is_csr
                $sql = "DELETE FROM my_collection 
                        WHERE user_id = :user_id
                        AND card_uuid = :card_uuid 
                        AND edition_uuid = :edition_uuid 
                        AND is_foil = :is_foil";
                
                $params = [
                    ':user_id' => $userId,
                    ':card_uuid' => $cardUuid,
                    ':edition_uuid' => $editionUuid,
                    ':is_foil' => $isFoil ? 1 : 0
                ];
            }
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression: " . $e->getMessage());
        }
    }

    private function columnExists($tableName, $columnName) {
        try {
            $sql = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
            $result = $this->db->fetch($sql);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getMyCollection($filters = [], $userId = null) {
        try {
            // Vérifier si la colonne is_csr existe
            $csrExists = $this->columnExists('my_collection', 'is_csr');
            
            $csrSelect = $csrExists ? 'mc.is_csr as owned_csr,' : 'FALSE as owned_csr,';
            
            $sql = "SELECT 
                        c.uuid,
                        c.name,
                        c.slug,
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
                    WHERE 1=1";

        $params = [];
        
        if ($userId !== null) {
            $sql .= " AND mc.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if (!empty($filters['name'])) {
            $sql .= " AND c.name LIKE :name";
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['set_prefix'])) {
            $sql .= " AND s.prefix = :set_prefix";
            $params[':set_prefix'] = $filters['set_prefix'];
        }

        if (!empty($filters['class'])) {
            // Utilisation de LIKE pour chercher dans le JSON - Compatible avec toutes les versions MySQL
            $sql .= " AND c.classes LIKE :class";
            $params[':class'] = '%' . $filters['class'] . '%';
        }

        if (!empty($filters['element'])) {
            $sql .= " AND c.element = :element";
            $params[':element'] = $filters['element'];
        }

        if (isset($filters['rarity'])) {
            $sql .= " AND ce.rarity = :rarity";
            $params[':rarity'] = $filters['rarity'];
        }

        if (isset($filters['is_foil'])) {
            $sql .= " AND mc.is_foil = :is_foil";
            $params[':is_foil'] = $filters['is_foil'] ? 1 : 0;
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND mc.condition_card = :condition";
            $params[':condition'] = $filters['condition'];
        }

        $orderBy = $filters['order_by'] ?? 'c.name';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        $sql .= " ORDER BY {$orderBy} {$orderDir}";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }

        // Debug logging
        error_log("Collection SQL: " . $sql);
        error_log("Collection params: " . print_r($params, true));

        $result = $this->db->fetchAll($sql, $params);
        error_log("Collection results count: " . count($result));
        
        return $result;
        } catch (Exception $e) {
            // Si les tables n'existent pas encore, retourner un tableau vide
            return [];
        }
    }

    public function getCardInCollection($cardUuid, $editionUuid, $isFoil = false, $userId = null) {
        $sql = "SELECT * FROM my_collection 
                WHERE card_uuid = :card_uuid 
                AND edition_uuid = :edition_uuid 
                AND is_foil = :is_foil";

        $params = [
            ':card_uuid' => $cardUuid,
            ':edition_uuid' => $editionUuid,
            ':is_foil' => $isFoil ? 1 : 0
        ];
        
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        return $this->db->fetch($sql, $params);
    }

    public function getCollectionClasses($userId = null) {
        try {
            $sql = "SELECT DISTINCT 
                        REPLACE(REPLACE(REPLACE(REPLACE(c.classes, '[', ''), ']', ''), '\"', ''), '\\\\', '') as class_name
                    FROM my_collection mc
                    JOIN cards c ON mc.card_uuid = c.uuid
                    WHERE c.classes IS NOT NULL 
                    AND c.classes != '[]'";
            
            $params = [];
            if ($userId !== null) {
                $sql .= " AND mc.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY class_name";
            
            $result = $this->db->fetchAll($sql, $params) ?? [];
            
            return $result;
        } catch (Exception $e) {
            error_log("Erreur getCollectionClasses: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionElements($userId = null) {
        try {
            $sql = "SELECT DISTINCT c.element 
                    FROM my_collection mc
                    JOIN cards c ON mc.card_uuid = c.uuid
                    WHERE c.element IS NOT NULL AND c.element != ''";
            
            $params = [];
            if ($userId !== null) {
                $sql .= " AND mc.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY c.element";
            
            $result = $this->db->fetchAll($sql, $params) ?? [];
            
            // Convertir en tableau simple
            return array_map(function($row) {
                return $row['element'];
            }, $result);
        } catch (Exception $e) {
            error_log("Erreur getCollectionElements: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionSets($userId = null) {
        try {
            $sql = "SELECT DISTINCT s.id, s.name, s.prefix
                    FROM my_collection mc
                    JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                    JOIN sets s ON ce.set_id = s.id";
            
            $params = [];
            if ($userId !== null) {
                $sql .= " WHERE mc.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY s.name";
            
            return $this->db->fetchAll($sql, $params) ?? [];
        } catch (Exception $e) {
            error_log("Erreur getCollectionSets: " . $e->getMessage());
            return [];
        }
    }

    public function getCollectionValue() {
        $sql = "SELECT 
                    SUM(mc.quantity * COALESCE(mp.price, 0)) as total_value,
                    COUNT(DISTINCT mc.card_uuid) as unique_cards,
                    SUM(mc.quantity) as total_cards
                FROM my_collection mc
                LEFT JOIN market_prices mp ON mc.edition_uuid = mp.edition_uuid 
                    AND mc.is_foil = mp.is_foil
                    AND mp.date_recorded = (
                        SELECT MAX(date_recorded) 
                        FROM market_prices mp2 
                        WHERE mp2.edition_uuid = mp.edition_uuid 
                        AND mp2.is_foil = mp.is_foil
                    )";

        return $this->db->fetch($sql);
    }

    public function getRecentlyAdded($limit = 10, $userId = null) {
        $sql = "SELECT 
                    c.uuid,
                    c.name,
                    c.slug,
                    ce.uuid as edition_uuid,
                    ce.collector_number,
                    ce.rarity,
                    ce.image,
                    s.name as set_name,
                    s.prefix as set_prefix,
                    mc.quantity,
                    mc.is_foil,
                    mc.created_at as added_date
                FROM my_collection mc
                JOIN cards c ON mc.card_uuid = c.uuid
                JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                JOIN sets s ON ce.set_id = s.id";
        
        $params = [':limit' => $limit];
        if ($userId !== null) {
            $sql .= " WHERE mc.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " ORDER BY mc.created_at DESC LIMIT :limit";

        return $this->db->fetchAll($sql, $params);
    }

    public function getDuplicates() {
        $sql = "SELECT 
                    c.uuid,
                    c.name,
                    ce.uuid as edition_uuid,
                    ce.collector_number,
                    s.name as set_name,
                    s.prefix as set_prefix,
                    SUM(mc.quantity) as total_quantity
                FROM my_collection mc
                JOIN cards c ON mc.card_uuid = c.uuid
                JOIN card_editions ce ON mc.edition_uuid = ce.uuid
                JOIN sets s ON ce.set_id = s.id
                GROUP BY c.uuid, ce.uuid
                HAVING total_quantity > 1
                ORDER BY total_quantity DESC";

        return $this->db->fetchAll($sql);
    }

    public function exportCollection($format = 'json', $userId = null) {
        $collection = $this->getMyCollection([], $userId);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($collection);
            case 'json':
            default:
                return json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    private function exportToCSV($collection) {
        $csv = "Nom,Extension,Numéro,Rareté,Quantité,Foil,État,Notes,Date d'acquisition,Prix payé\n";
        
        foreach ($collection as $card) {
            $csv .= sprintf(
                '"%s","%s","%s",%d,%d,%s,"%s","%s","%s",%s' . "\n",
                str_replace('"', '""', $card['name']),
                str_replace('"', '""', $card['set_name']),
                $card['collector_number'],
                $card['rarity'],
                $card['quantity'],
                $card['is_foil'] ? 'Oui' : 'Non',
                $card['condition_card'],
                str_replace('"', '""', $card['notes'] ?? ''),
                $card['acquired_date'] ?? '',
                $card['price_paid'] ?? ''
            );
        }
        
        return $csv;
    }

    public function importCollection($data, $format = 'json') {
        try {
            $this->db->beginTransaction();
            
            switch ($format) {
                case 'json':
                    $cards = json_decode($data, true);
                    break;
                case 'csv':
                    $cards = $this->parseCSV($data);
                    break;
                default:
                    throw new Exception("Format non supporté: $format");
            }

            foreach ($cards as $cardData) {
                $this->addToCollection(
                    $cardData['card_uuid'],
                    $cardData['edition_uuid'],
                    $cardData['quantity'],
                    $cardData['is_foil'],
                    [
                        'condition' => $cardData['condition_card'] ?? 'NEAR_MINT',
                        'notes' => $cardData['notes'] ?? null,
                        'acquired_date' => $cardData['acquired_date'] ?? null,
                        'price_paid' => $cardData['price_paid'] ?? null
                    ]
                );
            }

            $this->db->commit();
            return count($cards);

        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erreur lors de l'importation: " . $e->getMessage());
        }
    }

    private function parseCSV($csvData) {
        $lines = explode("\n", $csvData);
        $header = str_getcsv(array_shift($lines));
        $cards = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            
            $data = str_getcsv($line);
            $cards[] = array_combine($header, $data);
        }

        return $cards;
    }
}
?>