<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Grand Archive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1><i class="fas fa-magic"></i> Ma Collection Grand Archive</h1>
                <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="nav" id="main-nav">
                <button class="nav-btn active" data-view="collection">
                    <i class="fas fa-th-large"></i> Collection
                </button>
                <button class="nav-btn" data-view="search">
                    <i class="fas fa-search"></i> Recherche
                </button>
                <button class="nav-btn" data-view="stats">
                    <i class="fas fa-chart-bar"></i> Statistiques
                </button>
                <button class="nav-btn" data-view="sync">
                    <i class="fas fa-sync"></i> Synchronisation
                </button>
            </nav>
        </header>

        <main class="main">
            <!-- Vue Collection -->
            <div id="collection-view" class="view active">
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="collection-search" placeholder="Rechercher dans ma collection...">
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
                <div id="collection-cards" class="cards-grid">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Chargement de votre collection...</p>
                    </div>
                </div>
            </div>

            <!-- Vue Recherche -->
            <div id="search-view" class="view">
                <div class="search-panel">
                    <h2>Rechercher des cartes</h2>
                    <form id="search-form" class="search-form">
                        <div class="form-group">
                            <label for="card-name">Nom de la carte</label>
                            <input type="text" id="card-name" name="name" placeholder="Rechercher par nom...">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-set">Extension</label>
                                <select id="card-set" name="set">
                                    <option value="">Toutes les extensions</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="card-class">Classe</label>
                                <select id="card-class" name="class">
                                    <option value="">Toutes les classes</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </form>
                </div>
                <div id="search-results" class="cards-grid">
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>Effectuez une recherche pour découvrir des cartes</p>
                    </div>
                </div>
            </div>

            <!-- Vue Statistiques -->
            <div id="stats-view" class="view">
                <div class="stats-container">
                    <h2>Statistiques de ma collection</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fas fa-layer-group"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="total-cards">0</span>
                                <span class="stat-label">Cartes totales</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-star"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="unique-cards">0</span>
                                <span class="stat-label">Cartes uniques</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-gem"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="foil-cards">0</span>
                                <span class="stat-label">Cartes foil</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-box"></i>
                            <div class="stat-info">
                                <span class="stat-number" id="sets-owned">0</span>
                                <span class="stat-label">Extensions</span>
                            </div>
                        </div>
                    </div>
                    <div class="charts-container">
                        <div class="chart-card">
                            <h3>Répartition par extension</h3>
                            <canvas id="sets-chart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Répartition par classe</h3>
                            <canvas id="classes-chart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Répartition par rareté</h3>
                            <canvas id="rarity-chart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Répartition par élément</h3>
                            <canvas id="elements-chart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Progression par extension</h3>
                            <canvas id="progress-chart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Statistiques foil</h3>
                            <canvas id="foil-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vue Synchronisation -->
            <div id="sync-view" class="view">
                <div class="sync-container">
                    <h2>Synchronisation des cartes</h2>
                    <div class="sync-info">
                        <div class="sync-status-card">
                            <i class="fas fa-database"></i>
                            <div class="info">
                                <span class="label">Cartes en base</span>
                                <span class="value" id="cards-in-db">-</span>
                            </div>
                        </div>
                        <div class="sync-status-card">
                            <i class="fas fa-cloud"></i>
                            <div class="info">
                                <span class="label">Statut</span>
                                <span class="value" id="sync-status">-</span>
                            </div>
                        </div>
                        <div class="sync-status-card">
                            <i class="fas fa-download"></i>
                            <div class="info">
                                <span class="label">Dernière sync</span>
                                <span class="value" id="last-sync-time">Jamais</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sync-controls">
                        <div class="sync-options">
                            <div class="form-group">
                                <label for="sync-limit">Limite de pages à traiter (optionnel)</label>
                                <input type="number" id="sync-limit" placeholder="Ex: 5 pages" min="1" max="48" value="">
                                <small>Laisser vide pour traiter toutes les 48 pages (~1440 cartes)</small>
                            </div>
                            <div class="form-group">
                                <label for="sync-offset">Page de départ (pour reprendre une sync)</label>
                                <input type="number" id="sync-offset" value="1" min="1" max="48">
                                <small>Commencer à partir de cette page (1 à 48)</small>
                            </div>
                        </div>
                        
                        <div class="sync-mode">
                            <label>
                                <input type="checkbox" id="sync-all-mode"> 
                                Mode synchronisation complète (récupère TOUTES les cartes disponibles)
                            </label>
                        </div>
                        
                        <button id="start-sync-btn" class="btn-primary sync-btn">
                            <i class="fas fa-sync"></i> Démarrer la synchronisation
                        </button>
                        
                        <button id="check-status-btn" class="btn-secondary">
                            <i class="fas fa-refresh"></i> Vérifier le statut
                        </button>
                        
                        <button id="test-sync-btn" class="btn-secondary">
                            <i class="fas fa-vial"></i> Test de synchronisation
                        </button>
                    </div>
                    
                    <div class="sync-progress" id="sync-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill"></div>
                        </div>
                        <div class="progress-text" id="progress-text">Préparation...</div>
                    </div>
                    
                    <div class="sync-info-box">
                        <h3><i class="fas fa-info-circle"></i> Information</h3>
                        <p><strong>API GATCG :</strong> L'API retourne 30 cartes par page sur un total de 48 pages (≈1440 cartes).</p>
                        <p><strong>Mode complet :</strong> Parcourt automatiquement toutes les pages pour récupérer l'ensemble des cartes disponibles.</p>
                        <p><strong>Mode par lot :</strong> Utilisez la limite pour traiter un nombre spécifique de pages (utile pour les tests).</p>
                        <p>Si l'API officielle n'est pas accessible, le système utilisera automatiquement des données de test.</p>
                    </div>
                    
                    <div class="sync-log" id="sync-log">
                        <h3>Journal de synchronisation</h3>
                        <div class="log-content" id="log-content">
                            <p class="log-entry">Aucune synchronisation effectuée</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal pour les détails de carte -->
    <div id="card-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-body">
                <div class="card-image">
                    <img id="modal-card-image" src="" alt="">
                </div>
                <div class="card-details">
                    <h2 id="modal-card-name"></h2>
                    <div class="card-info">
                        <p><strong>Extension:</strong> <span id="modal-card-set"></span></p>
                        <p><strong>Numéro:</strong> <span id="modal-card-number"></span></p>
                        <p><strong>Rareté:</strong> <span id="modal-card-rarity"></span></p>
                        <p><strong>Classe:</strong> <span id="modal-card-class"></span></p>
                        <p><strong>Type:</strong> <span id="modal-card-type"></span></p>
                    </div>
                    <div class="card-effect">
                        <h3>Effet</h3>
                        <div id="modal-card-effect"></div>
                    </div>
                    <div class="collection-actions">
                        <h3>Dans ma collection</h3>
                        <div class="quantity-controls">
                            <button class="quantity-btn" data-action="decrease">-</button>
                            <span class="quantity" id="card-quantity">0</span>
                            <button class="quantity-btn" data-action="increase">+</button>
                        </div>
                        <div class="foil-controls">
                            <label>
                                <input type="checkbox" id="card-foil"> Version foil
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="assets/js/api.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/collection.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>