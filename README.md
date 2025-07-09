# 🎴 Grand Archive Collection Manager

Une application web moderne pour gérer votre collection de cartes Grand Archive avec un design **liquid glass** immersif et des fonctionnalités avancées.

![Grand Archive Collection](https://img.shields.io/badge/Version-1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Fonctionnalités

### 🎯 **Gestion de collection**
- **Visualisation** : Affichage en grille avec images des cartes
- **Recherche avancée** : Filtres par nom, extension, classe et élément
- **Détails complets** : Modal avec informations détaillées de chaque carte
- **Quantité** : Gestion des quantités normales et foil
- **Filtres intelligents** : Affichage uniquement des options disponibles dans votre collection

### 📊 **Statistiques détaillées**
- Nombre total de cartes et cartes uniques
- Répartition par extension et rareté
- Statistiques des cartes foil
- Progression de collection par extension
- Graphiques interactifs avec Chart.js

### 🔄 **Synchronisation automatique**
- Import depuis l'API officielle Grand Archive
- Synchronisation par lots ou complète
- Gestion des reprises de synchronisation
- Logs détaillés des opérations

### 🎨 **Design moderne**
- **Liquid Glass** : Effet glassmorphism avec transparence
- **Animations fluides** : Transitions et effets visuels
- **Responsive** : Adapté mobile, tablette et desktop
- **Accessible** : Contrastes WCAG 2.1 AA/AAA conformes
- **Particules** : Effets de particules flottantes

## 🛠️ Installation

### Prérequis
- **PHP** 7.4 ou supérieur
- **MySQL** 5.7 ou supérieur (ou MariaDB 10.2+)
- **Serveur web** Apache/Nginx
- **Extensions PHP** : PDO, PDO_MySQL, JSON, cURL

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone https://github.com/votre-repo/grand-archive-collection.git
cd grand-archive-collection
```

2. **Configuration de la base de données**
```bash
# Créer la base de données
mysql -u root -p < sql/schema.sql
```

3. **Configuration**
```bash
# Éditer la configuration (si nécessaire)
vim config/database.php
```

4. **Initialisation**
```bash
# Accéder à l'interface web
http://localhost/grand-archive-collection/
```

5. **Première synchronisation**
- Aller dans l'onglet "Synchronisation"
- Cliquer sur "Démarrer la synchronisation"
- Attendre la fin du processus

## 🔧 Configuration

### Base de données
Le fichier `config/database.php` contient les paramètres de connexion :

```php
private $host = 'localhost';
private $database = 'grand_archive_collection';
private $username = 'root';
private $password = '';
```

### API Grand Archive
L'application utilise l'API officielle :
- **URL** : `https://api.gatcg.com`
- **Limite** : 30 cartes par page
- **Total** : ~48 pages (1440 cartes)

## 📁 Structure du projet

```
grand-archive-collection/
├── api/                    # API endpoints
│   └── cards.php          # Gestion des cartes et collections
├── assets/                # Ressources front-end
│   ├── css/
│   │   ├── style.css      # Styles principaux
│   │   └── liquid-glass.css # Système liquid glass
│   ├── images/            # Images statiques
│   └── js/
│       ├── api.js         # Client API
│       ├── collection.js  # Gestion collection
│       ├── liquid-glass.js # Animations
│       └── main.js        # Script principal
├── classes/               # Classes PHP
│   ├── Card.php          # Gestion des cartes
│   └── Collection.php    # Gestion collection
├── config/               # Configuration
│   └── database.php     # Configuration DB
├── sql/                 # Scripts SQL
│   └── schema.sql       # Structure DB
├── index.php           # Page principale
├── init.php            # Initialisation
├── setup.php           # Configuration
└── sw.js              # Service Worker
```

## 🎯 Utilisation

### Ajouter des cartes
1. **Synchronisation** : Utiliser l'onglet "Synchronisation" pour importer
2. **Recherche** : Rechercher des cartes dans l'onglet "Recherche"
3. **Ajout** : Cliquer sur une carte → Ajuster la quantité → Confirmer

### Filtres collection
- **Extension** : Filtrer par set/expansion
- **Classe** : Filtrer par classe de carte (Mage, Warrior, etc.)
- **Élément** : Filtrer par élément (Fire, Water, etc.)
- **Recherche** : Recherche textuelle par nom

### Statistiques
- **Vue globale** : Nombre total, uniques, foil
- **Graphiques** : Répartition par extension, rareté, élément
- **Progression** : Pourcentage de completion par set

## 🎨 Design System

### Liquid Glass
Le système de design utilise :
- **Glassmorphism** : Transparence et flou
- **Animations** : Transitions fluides
- **Particules** : Effets de fond animés
- **Couleurs** : Palette sombre avec accents colorés

### Responsive
- **Desktop** : Expérience complète
- **Tablette** : Layout adapté
- **Mobile** : Interface optimisée

### Accessibilité
- **Contrastes** : WCAG 2.1 AA/AAA
- **Navigation** : Support clavier
- **Focus** : Indicateurs visuels
- **Textes** : Tailles optimisées

## 🔌 API

### Endpoints principaux

```php
GET  /api/cards.php?action=search          # Rechercher cartes
GET  /api/cards.php?action=collection      # Ma collection
GET  /api/cards.php?action=stats           # Statistiques
POST /api/cards.php?action=add_to_collection # Ajouter carte
PUT  /api/cards.php?action=update_quantity   # Modifier quantité
```

### Filtres supportés
- `name` : Nom de la carte
- `set` : Extension
- `class` : Classe
- `element` : Élément
- `rarity` : Rareté

## 🚀 Développement

### Contribuer
1. Fork le projet
2. Créer une branche feature
3. Commit les changements
4. Push vers la branche
5. Créer une Pull Request

### Standards
- **PHP** : PSR-4 pour l'autoloading
- **JavaScript** : ES6+ avec modules
- **CSS** : BEM methodology
- **Git** : Conventional commits

## 🐛 Dépannage

### Problèmes courants

**Cartes non synchronisées**
```bash
# Vérifier la connexion API
curl https://api.gatcg.com/cards
```

**Erreur base de données**
```bash
# Vérifier les permissions
SHOW GRANTS FOR 'root'@'localhost';
```

**Filtres non fonctionnels**
```bash
# Vérifier les logs PHP
tail -f /var/log/php/error.log
```

## 📝 Changelog

### Version 1.0.0
- ✅ Gestion complète de collection
- ✅ Synchronisation API Grand Archive
- ✅ Design liquid glass
- ✅ Statistiques avancées
- ✅ Filtres intelligents
- ✅ Responsive design
- ✅ Accessibilité optimisée

## 📄 License

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🤝 Support

Pour toute question ou support :
- 📧 Email : support@exemple.com
- 🐛 Issues : GitHub Issues
- 📖 Documentation : Ce README

---

**Développé avec ❤️ pour la communauté Grand Archive**

> 🎴 *"Collect, organize, and showcase your Grand Archive cards with style!"*