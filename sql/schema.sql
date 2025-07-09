-- Création de la base de données
CREATE DATABASE IF NOT EXISTS grand_archive_collection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grand_archive_collection;

-- Table des extensions
CREATE TABLE IF NOT EXISTS sets (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    prefix VARCHAR(10) NOT NULL,
    release_date DATE,
    language VARCHAR(5) DEFAULT 'EN',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des cartes
CREATE TABLE IF NOT EXISTS cards (
    uuid VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    cost_memory INT,
    cost_reserve INT,
    power INT,
    durability INT,
    life INT,
    level INT,
    speed VARCHAR(50),
    element VARCHAR(50),
    effect TEXT,
    effect_raw TEXT,
    effect_html TEXT,
    flavor TEXT,
    rule JSON,
    types JSON,
    subtypes JSON,
    classes JSON,
    elements JSON,
    legality VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_slug (slug),
    INDEX idx_element (element)
);

-- Table des éditions de cartes
CREATE TABLE IF NOT EXISTS card_editions (
    uuid VARCHAR(50) PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    collector_number VARCHAR(10) NOT NULL,
    set_id VARCHAR(50) NOT NULL,
    configuration VARCHAR(50) DEFAULT 'default',
    rarity INT NOT NULL,
    illustrator VARCHAR(255),
    flavor TEXT,
    image VARCHAR(500),
    orientation VARCHAR(50),
    effect TEXT,
    effect_raw TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES cards(uuid) ON DELETE CASCADE,
    FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
    
    INDEX idx_collector_number (collector_number),
    INDEX idx_set_collector (set_id, collector_number),
    INDEX idx_rarity (rarity)
);

-- Table des templates de circulation (foil/non-foil)
CREATE TABLE IF NOT EXISTS circulation_templates (
    uuid VARCHAR(50) PRIMARY KEY,
    edition_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    kind ENUM('FOIL', 'NONFOIL') NOT NULL,
    foil BOOLEAN NOT NULL DEFAULT FALSE,
    printing BOOLEAN NOT NULL DEFAULT TRUE,
    population INT,
    population_operator VARCHAR(5) DEFAULT '=',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_id) REFERENCES card_editions(uuid) ON DELETE CASCADE,
    
    INDEX idx_edition_kind (edition_id, kind)
);

-- Table de ma collection personnelle
CREATE TABLE IF NOT EXISTS my_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_uuid VARCHAR(50) NOT NULL,
    edition_uuid VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    is_foil BOOLEAN NOT NULL DEFAULT FALSE,
    is_csr BOOLEAN NOT NULL DEFAULT FALSE,
    condition_card ENUM('MINT', 'NEAR_MINT', 'EXCELLENT', 'GOOD', 'LIGHT_PLAYED', 'PLAYED', 'POOR') DEFAULT 'NEAR_MINT',
    notes TEXT,
    acquired_date DATE,
    price_paid DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_uuid) REFERENCES cards(uuid) ON DELETE CASCADE,
    FOREIGN KEY (edition_uuid) REFERENCES card_editions(uuid) ON DELETE CASCADE,
    
    UNIQUE KEY unique_collection_entry (card_uuid, edition_uuid, is_foil, is_csr),
    INDEX idx_card (card_uuid),
    INDEX idx_edition (edition_uuid),
    INDEX idx_foil (is_foil),
    INDEX idx_csr (is_csr)
);

-- Table des prix de marché (optionnelle pour tracking des valeurs)
CREATE TABLE IF NOT EXISTS market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    edition_uuid VARCHAR(50) NOT NULL,
    is_foil BOOLEAN NOT NULL DEFAULT FALSE,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    source VARCHAR(100),
    date_recorded DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (edition_uuid) REFERENCES card_editions(uuid) ON DELETE CASCADE,
    
    INDEX idx_edition_foil_date (edition_uuid, is_foil, date_recorded)
);

-- Table des favoris/wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_uuid VARCHAR(50) NOT NULL,
    edition_uuid VARCHAR(50) NOT NULL,
    priority ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM',
    max_price DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_uuid) REFERENCES cards(uuid) ON DELETE CASCADE,
    FOREIGN KEY (edition_uuid) REFERENCES card_editions(uuid) ON DELETE CASCADE,
    
    UNIQUE KEY unique_wishlist_entry (card_uuid, edition_uuid),
    INDEX idx_priority (priority)
);

-- Vues utiles pour les statistiques
CREATE VIEW collection_stats AS
SELECT 
    COUNT(*) as total_cards,
    COUNT(DISTINCT card_uuid) as unique_cards,
    SUM(CASE WHEN is_foil = TRUE THEN quantity ELSE 0 END) as foil_cards,
    COUNT(DISTINCT 
        (SELECT set_id FROM card_editions WHERE uuid = my_collection.edition_uuid)
    ) as sets_owned,
    AVG(quantity) as avg_quantity_per_card
FROM my_collection;

CREATE VIEW collection_by_set AS
SELECT 
    s.name as set_name,
    s.prefix as set_prefix,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards,
    SUM(CASE WHEN mc.is_foil = TRUE THEN mc.quantity ELSE 0 END) as foil_cards
FROM my_collection mc
JOIN card_editions ce ON mc.edition_uuid = ce.uuid
JOIN sets s ON ce.set_id = s.id
GROUP BY s.id, s.name, s.prefix
ORDER BY total_cards DESC;

CREATE VIEW collection_by_rarity AS
SELECT 
    ce.rarity,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards,
    SUM(CASE WHEN mc.is_foil = TRUE THEN mc.quantity ELSE 0 END) as foil_cards
FROM my_collection mc
JOIN card_editions ce ON mc.edition_uuid = ce.uuid
GROUP BY ce.rarity
ORDER BY ce.rarity;

CREATE VIEW collection_by_class AS
SELECT 
    class_name,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards
FROM my_collection mc
JOIN cards c ON mc.card_uuid = c.uuid
JOIN JSON_TABLE(
    c.classes, '$[*]' 
    COLUMNS (class_name VARCHAR(100) PATH '$')
) jt
GROUP BY class_name
ORDER BY total_cards DESC;

-- Table de statut de synchronisation
CREATE TABLE IF NOT EXISTS sync_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status ENUM('idle', 'running', 'completed', 'error') DEFAULT 'idle',
    message TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);