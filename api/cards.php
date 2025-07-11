<?php
// Désactiver l'affichage des erreurs PHP pour éviter le HTML dans la réponse JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Gestion d'erreur globale pour éviter les 500
function handleError($message, $type = 'general_error') {
    http_response_code(200); // Éviter l'erreur 500
    echo json_encode([
        'success' => false,
        'error' => $message,
        'type' => $type
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../classes/Card.php';
    require_once __DIR__ . '/../classes/Collection.php';

    // Tentative de création des instances avec gestion d'erreur
    try {
        $card = new Card();
        $collection = new Collection();
    } catch (Exception $dbError) {
        handleError(
            'Connexion à la base de données impossible. Vérifiez que MySQL est démarré et que la base de données existe.',
            'database_connection_error'
        );
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'search';

    switch ($method) {
        case 'GET':
            handleGet($card, $collection, $action);
            break;
        case 'POST':
            handlePost($card, $collection, $action);
            break;
        case 'PUT':
            handlePut($card, $collection, $action);
            break;
        case 'DELETE':
            handleDelete($collection, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    
    // Identifier le type d'erreur
    $errorType = 'general_error';
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'base de données') !== false || 
        strpos($errorMessage, 'Connection refused') !== false ||
        strpos($errorMessage, 'No such file or directory') !== false) {
        $errorType = 'database_connection_error';
        $errorMessage = 'Connexion à la base de données impossible. Vérifiez que MySQL est démarré.';
    }
    
    handleError($errorMessage, $errorType);
}

function handleGet($card, $collection, $action) {
    switch ($action) {
        case 'search':
            $params = [
                'name' => $_GET['name'] ?? '',
                'set_prefix' => $_GET['set_prefix'] ?? '',
                'class' => $_GET['class'] ?? '',
                'element' => $_GET['element'] ?? '',
                'rarity' => isset($_GET['rarity']) ? (int)$_GET['rarity'] : null,
                'owned_only' => isset($_GET['owned_only']) ? (bool)$_GET['owned_only'] : false,
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : null
            ];
            
            
            $results = $card->searchCards($params);
            error_log("API Search - Params: " . print_r($params, true));
            error_log("API Search - Results count: " . count($results));
            echo json_encode(['success' => true, 'data' => $results]);
            break;

        case 'get_card':
            $uuid = $_GET['uuid'] ?? '';
            if (empty($uuid)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'UUID requis']);
                return;
            }
            
            $cardData = $card->getCardById($uuid);
            if (!$cardData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Carte non trouvée']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $cardData]);
            break;

        case 'collection':
            $filters = [
                'name' => $_GET['name'] ?? '',
                'set_prefix' => $_GET['set'] ?? '',
                'class' => $_GET['class'] ?? '',
                'element' => $_GET['element'] ?? '',
                'rarity' => isset($_GET['rarity']) ? (int)$_GET['rarity'] : null,
                'is_foil' => isset($_GET['foil']) ? (bool)$_GET['foil'] : null,
                'condition' => $_GET['condition'] ?? '',
                'order_by' => $_GET['order_by'] ?? 'c.name',
                'order_dir' => $_GET['order_dir'] ?? 'ASC',
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : null
            ];
            
            $myCollection = $collection->getMyCollection($filters);
            error_log("API Collection - Filters: " . print_r($filters, true));
            error_log("API Collection - Results count: " . count($myCollection));
            echo json_encode(['success' => true, 'data' => $myCollection]);
            break;

        case 'stats':
            try {
                $stats = $card->getCollectionStats();
                $bySet = $card->getCollectionBySet();
                $byRarity = $card->getCollectionByRarity();
                $byClass = $card->getCollectionByClass();
                $byElement = $card->getCollectionByElement();
                $progress = $card->getCollectionProgress();
                $foilStats = $card->getFoilStatistics();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'overall' => $stats,
                        'by_set' => $bySet,
                        'by_rarity' => $byRarity,
                        'by_class' => $byClass,
                        'by_element' => $byElement,
                        'progress' => $progress,
                        'foil_stats' => $foilStats
                    ]
                ]);
            } catch (Exception $e) {
                error_log("API Stats error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Erreur lors du chargement des statistiques: ' . $e->getMessage()
                ]);
            }
            break;

        case 'sets':
            $sets = $card->getAllSets();
            echo json_encode(['success' => true, 'data' => $sets]);
            break;

        case 'classes':
            $classes = $card->getAllClasses();
            echo json_encode(['success' => true, 'data' => $classes]);
            break;

        case 'elements':
            $elements = $card->getAllElements();
            echo json_encode(['success' => true, 'data' => $elements]);
            break;

        case 'collection_classes':
            $classes = $collection->getCollectionClasses();
            echo json_encode(['success' => true, 'data' => $classes]);
            break;

        case 'collection_elements':
            $elements = $collection->getCollectionElements();
            echo json_encode(['success' => true, 'data' => $elements]);
            break;

        case 'collection_sets':
            $sets = $collection->getCollectionSets();
            echo json_encode(['success' => true, 'data' => $sets]);
            break;

        case 'recent':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $recent = $collection->getRecentlyAdded($limit);
            echo json_encode(['success' => true, 'data' => $recent]);
            break;

        case 'export':
            $format = $_GET['format'] ?? 'json';
            $data = $collection->exportCollection($format);
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="collection.csv"');
                echo $data;
            } else {
                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'sync_cards':
            try {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $result = $card->syncCardsFromAPI($limit, $offset);
                echo json_encode(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                error_log("Sync cards error: " . $e->getMessage());
                http_response_code(200); // Éviter l'erreur 500
                echo json_encode([
                    'success' => false, 
                    'error' => $e->getMessage(),
                    'type' => 'database_connection_error',
                    'suggestion' => 'Vérifiez que MySQL est démarré et que la base de données existe'
                ]);
            }
            break;

        case 'test_sync':
            try {
                // Test simple : insérer une carte factice
                $testResult = $card->testSyncFunction();
                echo json_encode(['success' => true, 'data' => $testResult]);
            } catch (Exception $e) {
                error_log("Test sync error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'sync_status':
            $status = $card->getSyncStatus();
            echo json_encode(['success' => true, 'data' => $status]);
            break;

        case 'cards_count':
            $count = $card->getCardsCount();
            echo json_encode(['success' => true, 'data' => ['total' => $count]]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

function handlePost($card, $collection, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'add_to_collection':
            $cardUuid = $input['card_uuid'] ?? '';
            $editionUuid = $input['edition_uuid'] ?? '';
            $quantity = $input['quantity'] ?? 1;
            $isFoil = $input['is_foil'] ?? false;
            $options = $input['options'] ?? [];
            
            if (empty($cardUuid) || empty($editionUuid)) {
                http_response_code(400);
                echo json_encode(['error' => 'card_uuid et edition_uuid requis']);
                return;
            }
            
            $success = $collection->addToCollection($cardUuid, $editionUuid, $quantity, $isFoil, $options);
            echo json_encode(['success' => $success]);
            break;

        case 'import_card':
            if (empty($input['card_data'])) {
                http_response_code(400);
                echo json_encode(['error' => 'card_data requis']);
                return;
            }
            
            $success = $card->saveCard($input['card_data']);
            echo json_encode(['success' => $success]);
            break;

        case 'fetch_from_api':
            $setPrefix = $input['set_prefix'] ?? '';
            $collectorNumber = $input['collector_number'] ?? '';
            
            if (empty($setPrefix) || empty($collectorNumber)) {
                http_response_code(400);
                echo json_encode(['error' => 'set_prefix et collector_number requis']);
                return;
            }
            
            $apiUrl = "https://api.gatcg.com/cards/{$setPrefix}/{$collectorNumber}";
            $cardData = file_get_contents($apiUrl);
            
            if ($cardData === false) {
                http_response_code(404);
                echo json_encode(['error' => 'Carte non trouvée dans l\'API']);
                return;
            }
            
            $cardJson = json_decode($cardData, true);
            $success = $card->saveCard($cardJson);
            
            echo json_encode(['success' => $success, 'data' => $cardJson]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

function handlePut($card, $collection, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_quantity':
            $cardUuid = $input['card_uuid'] ?? '';
            $editionUuid = $input['edition_uuid'] ?? '';
            $isFoil = $input['is_foil'] ?? false;
            $isCsr = $input['is_csr'] ?? null; // null = auto-détection
            $newQuantity = $input['quantity'] ?? 0;
            
            if (empty($cardUuid) || empty($editionUuid)) {
                http_response_code(400);
                echo json_encode(['error' => 'card_uuid et edition_uuid requis']);
                return;
            }
            
            $success = $collection->updateQuantity($cardUuid, $editionUuid, $isFoil, $newQuantity, $isCsr);
            echo json_encode(['success' => $success]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

function handleDelete($collection, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'remove_from_collection':
            $cardUuid = $input['card_uuid'] ?? '';
            $editionUuid = $input['edition_uuid'] ?? '';
            $isFoil = $input['is_foil'] ?? false;
            $quantity = $input['quantity'] ?? null;
            
            if (empty($cardUuid) || empty($editionUuid)) {
                http_response_code(400);
                echo json_encode(['error' => 'card_uuid et edition_uuid requis']);
                return;
            }
            
            $success = $collection->removeFromCollection($cardUuid, $editionUuid, $isFoil, $quantity);
            echo json_encode(['success' => $success]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}
?>