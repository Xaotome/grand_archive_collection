-- Script de migration pour ajouter le système d'utilisateurs
-- Exécuter ce script pour migrer une base existante vers le nouveau système multi-utilisateurs

-- Créer la table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Créer la table des sessions utilisateurs
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Ajouter la colonne user_id à my_collection si elle n'existe pas déjà
ALTER TABLE my_collection 
ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER id;

-- Ajouter la contrainte de clé étrangère pour user_id dans my_collection
ALTER TABLE my_collection 
ADD CONSTRAINT fk_collection_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Supprimer l'ancienne contrainte unique et ajouter la nouvelle avec user_id
ALTER TABLE my_collection 
DROP INDEX unique_collection_entry;

ALTER TABLE my_collection 
ADD CONSTRAINT unique_collection_entry 
UNIQUE KEY (user_id, card_uuid, edition_uuid, is_foil, is_csr);

-- Ajouter l'index pour user_id dans my_collection
ALTER TABLE my_collection 
ADD INDEX idx_user_collection (user_id);

-- Ajouter la colonne user_id à wishlist si elle n'existe pas déjà
ALTER TABLE wishlist 
ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER id;

-- Ajouter la contrainte de clé étrangère pour user_id dans wishlist
ALTER TABLE wishlist 
ADD CONSTRAINT fk_wishlist_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Supprimer l'ancienne contrainte unique et ajouter la nouvelle avec user_id dans wishlist
ALTER TABLE wishlist 
DROP INDEX unique_wishlist_entry;

ALTER TABLE wishlist 
ADD CONSTRAINT unique_wishlist_entry 
UNIQUE KEY (user_id, card_uuid, edition_uuid);

-- Ajouter l'index pour user_id dans wishlist
ALTER TABLE wishlist 
ADD INDEX idx_user_wishlist (user_id);

-- Recréer les vues avec support multi-utilisateurs
DROP VIEW IF EXISTS collection_stats;
CREATE VIEW collection_stats AS
SELECT 
    user_id,
    COUNT(*) as total_cards,
    COUNT(DISTINCT card_uuid) as unique_cards,
    SUM(CASE WHEN is_foil = TRUE THEN quantity ELSE 0 END) as foil_cards,
    COUNT(DISTINCT 
        (SELECT set_id FROM card_editions WHERE uuid = my_collection.edition_uuid)
    ) as sets_owned,
    AVG(quantity) as avg_quantity_per_card
FROM my_collection
GROUP BY user_id;

DROP VIEW IF EXISTS collection_by_set;
CREATE VIEW collection_by_set AS
SELECT 
    mc.user_id,
    s.name as set_name,
    s.prefix as set_prefix,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards,
    SUM(CASE WHEN mc.is_foil = TRUE THEN mc.quantity ELSE 0 END) as foil_cards
FROM my_collection mc
JOIN card_editions ce ON mc.edition_uuid = ce.uuid
JOIN sets s ON ce.set_id = s.id
GROUP BY mc.user_id, s.id, s.name, s.prefix
ORDER BY mc.user_id, total_cards DESC;

DROP VIEW IF EXISTS collection_by_rarity;
CREATE VIEW collection_by_rarity AS
SELECT 
    mc.user_id,
    ce.rarity,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards,
    SUM(CASE WHEN mc.is_foil = TRUE THEN mc.quantity ELSE 0 END) as foil_cards
FROM my_collection mc
JOIN card_editions ce ON mc.edition_uuid = ce.uuid
GROUP BY mc.user_id, ce.rarity
ORDER BY mc.user_id, ce.rarity;

DROP VIEW IF EXISTS collection_by_class;
CREATE VIEW collection_by_class AS
SELECT 
    mc.user_id,
    class_name,
    COUNT(DISTINCT mc.card_uuid) as unique_cards,
    SUM(mc.quantity) as total_cards
FROM my_collection mc
JOIN cards c ON mc.card_uuid = c.uuid
JOIN JSON_TABLE(
    c.classes, '$[*]' 
    COLUMNS (class_name VARCHAR(100) PATH '$')
) jt
GROUP BY mc.user_id, class_name
ORDER BY mc.user_id, total_cards DESC;

-- Créer un utilisateur par défaut pour les données existantes (optionnel)
INSERT IGNORE INTO users (id, username, email, password_hash) 
VALUES (1, 'admin', 'admin@localhost', '$2y$10$defaultpasswordhash');

-- Mettre à jour toutes les entrées existantes pour les lier à l'utilisateur par défaut
UPDATE my_collection SET user_id = 1 WHERE user_id = 0 OR user_id IS NULL;
UPDATE wishlist SET user_id = 1 WHERE user_id = 0 OR user_id IS NULL;

-- Nettoyer les sessions expirées (procédure de maintenance)
DELIMITER //
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM user_sessions WHERE expires_at < NOW();
//
DELIMITER ;

-- Afficher le résultat de la migration
SELECT 'Migration terminée avec succès' as status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_sessions FROM user_sessions;
SELECT COUNT(*) as collection_entries FROM my_collection;
SELECT COUNT(*) as wishlist_entries FROM wishlist;