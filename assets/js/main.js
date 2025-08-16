// Utilitaires et fonctions globales
class Utils {
    static formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    static formatPrice(price, currency = 'EUR') {
        if (!price) return '';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency
        }).format(price);
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static createElement(tag, className = '', content = '') {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (content) element.innerHTML = content;
        return element;
    }

    static sanitizeHtml(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }
}

// Gestionnaire de thème
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'dark';
        this.applyTheme();
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        localStorage.setItem('theme', this.currentTheme);
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme();
    }
}

// Gestionnaire de menu mobile
class MobileMenuManager {
    constructor() {
        this.menuToggle = document.getElementById('mobile-menu-toggle');
        this.mainNav = document.getElementById('main-nav');
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        if (this.menuToggle) {
            this.menuToggle.addEventListener('click', () => {
                this.toggleMenu();
            });
        }
        
        // Fermer le menu si on clique sur un bouton de navigation
        if (this.mainNav) {
            this.mainNav.addEventListener('click', (e) => {
                if (e.target.classList.contains('nav-btn')) {
                    this.closeMenu();
                }
            });
        }
        
        // Fermer le menu si on clique en dehors (sur mobile)
        document.addEventListener('click', (e) => {
            if (this.isOpen && 
                !this.mainNav.contains(e.target) && 
                !this.menuToggle.contains(e.target)) {
                this.closeMenu();
            }
        });
        
        // Gérer le redimensionnement de la fenêtre
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isOpen) {
                this.closeMenu();
            }
        });
    }
    
    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }
    
    openMenu() {
        this.mainNav.classList.add('mobile-open');
        this.menuToggle.innerHTML = '<i class="fas fa-times"></i>';
        this.isOpen = true;
    }
    
    closeMenu() {
        this.mainNav.classList.remove('mobile-open');
        this.menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        this.isOpen = false;
    }
}

// Gestionnaire de raccourcis clavier
class KeyboardManager {
    constructor() {
        this.shortcuts = {
            'Escape': () => this.handleEscape(),
            'KeyF': (e) => this.handleSearch(e),
            'KeyN': (e) => this.handleNew(e),
            'KeyS': (e) => this.handleSave(e),
            // Navigation avec les flèches
            'ArrowUp': (e) => this.handleArrowNavigation(e, 'up'),
            'ArrowDown': (e) => this.handleArrowNavigation(e, 'down'),
            'ArrowLeft': (e) => this.handleArrowNavigation(e, 'left'),
            'ArrowRight': (e) => this.handleArrowNavigation(e, 'right'),
            'Enter': (e) => this.handleEnter(e)
        };
        
        this.currentCardIndex = -1;
        this.currentCards = [];
        this.cardsPerRow = 4; // Par défaut, sera calculé dynamiquement
        
        // Variables pour la navigation dans la modal
        this.modalCardsList = [];
        this.modalCurrentIndex = -1;
        
        this.init();
    }

    init() {
        document.addEventListener('keydown', (e) => {
            const shortcut = this.shortcuts[e.code];
            if (shortcut) {
                shortcut(e);
            }
        });
        
        // Observer les changements de cartes pour mettre à jour la navigation
        this.observeCardChanges();
        
        // Calculer les cartes par ligne au redimensionnement
        window.addEventListener('resize', () => {
            this.calculateCardsPerRow();
        });
    }

    observeCardChanges() {
        const observer = new MutationObserver(() => {
            this.updateCardsList();
        });
        
        // Observer les changements dans la grille de cartes (collection et recherche)
        const observeContainer = (containerSelector) => {
            const container = document.querySelector(containerSelector);
            if (container) {
                observer.observe(container, { childList: true, subtree: true });
            }
        };
        
        // Observer les différents conteneurs de cartes
        observeContainer('.cards-grid'); // Vue collection
        observeContainer('#search-results'); // Vue recherche si elle existe
        
        // Observer aussi les changements de vue
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-btn')) {
                // Réinitialiser la sélection lors du changement de vue
                setTimeout(() => {
                    this.resetSelection();
                    this.updateCardsList();
                }, 100);
            }
        });
    }

    resetSelection() {
        // Désélectionner la carte actuelle
        if (this.currentCardIndex >= 0 && this.currentCards[this.currentCardIndex]) {
            this.currentCards[this.currentCardIndex].classList.remove('keyboard-selected');
        }
        this.currentCardIndex = -1;
    }

    updateCardsList() {
        const cardElements = document.querySelectorAll('.card-item:not(.no-results):not(.loading)');
        this.currentCards = Array.from(cardElements);
        
        // Calculer le nombre de cartes par ligne
        this.calculateCardsPerRow();
        
        // Si aucune carte n'est sélectionnée et qu'il y a des cartes, ne pas sélectionner automatiquement
        if (this.currentCardIndex >= this.currentCards.length) {
            this.currentCardIndex = -1;
        }
    }

    calculateCardsPerRow() {
        if (this.currentCards.length < 2) {
            this.cardsPerRow = 1;
            return;
        }
        
        const firstCard = this.currentCards[0];
        const secondCard = this.currentCards[1];
        
        if (firstCard && secondCard) {
            const firstRect = firstCard.getBoundingClientRect();
            const secondRect = secondCard.getBoundingClientRect();
            
            // Si la deuxième carte est sur la même ligne que la première
            if (Math.abs(firstRect.top - secondRect.top) < 10) {
                // Compter combien de cartes sont sur la première ligne
                let cardsInFirstRow = 1;
                for (let i = 1; i < this.currentCards.length; i++) {
                    const cardRect = this.currentCards[i].getBoundingClientRect();
                    if (Math.abs(firstRect.top - cardRect.top) < 10) {
                        cardsInFirstRow++;
                    } else {
                        break;
                    }
                }
                this.cardsPerRow = cardsInFirstRow;
            } else {
                this.cardsPerRow = 1; // Mode liste
            }
        }
    }

    handleArrowNavigation(e, direction) {
        // Ne pas naviguer si on est dans un input ou textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Vérifier si une modal est ouverte
        const modal = document.getElementById('card-modal');
        if (modal && modal.style.display === 'block') {
            // Navigation dans la modal (seulement gauche/droite)
            if (direction === 'left' || direction === 'right') {
                this.handleModalNavigation(e, direction);
            }
            return;
        }
        
        this.updateCardsList();
        
        if (this.currentCards.length === 0) {
            return;
        }
        
        e.preventDefault();
        
        let newIndex = this.currentCardIndex;
        
        switch (direction) {
            case 'right':
                if (this.currentCardIndex === -1) {
                    newIndex = 0; // Commencer par la première carte
                } else {
                    newIndex = Math.min(this.currentCardIndex + 1, this.currentCards.length - 1);
                }
                break;
                
            case 'left':
                if (this.currentCardIndex === -1) {
                    newIndex = 0;
                } else {
                    newIndex = Math.max(this.currentCardIndex - 1, 0);
                }
                break;
                
            case 'down':
                if (this.currentCardIndex === -1) {
                    newIndex = 0;
                } else {
                    newIndex = Math.min(this.currentCardIndex + this.cardsPerRow, this.currentCards.length - 1);
                }
                break;
                
            case 'up':
                if (this.currentCardIndex === -1) {
                    newIndex = 0;
                } else {
                    newIndex = Math.max(this.currentCardIndex - this.cardsPerRow, 0);
                }
                break;
        }
        
        this.selectCard(newIndex);
    }

    handleEnter(e) {
        // Ne pas agir si on est dans un input ou textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Si une carte est sélectionnée, l'ouvrir
        if (this.currentCardIndex >= 0 && this.currentCards[this.currentCardIndex]) {
            e.preventDefault();
            this.currentCards[this.currentCardIndex].click();
        }
    }

    selectCard(index) {
        // Désélectionner la carte actuelle
        if (this.currentCardIndex >= 0 && this.currentCards[this.currentCardIndex]) {
            this.currentCards[this.currentCardIndex].classList.remove('keyboard-selected');
        }
        
        // Sélectionner la nouvelle carte
        this.currentCardIndex = index;
        const selectedCard = this.currentCards[this.currentCardIndex];
        
        if (selectedCard) {
            selectedCard.classList.add('keyboard-selected');
            
            // Faire défiler pour voir la carte sélectionnée
            selectedCard.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    handleModalNavigation(e, direction) {
        e.preventDefault();
        
        // Mettre à jour la liste des cartes pour la modal si nécessaire
        if (this.modalCardsList.length === 0) {
            this.modalCardsList = Array.from(this.currentCards);
            // Trouver l'index de la carte actuellement affichée dans la modal
            this.modalCurrentIndex = this.currentCardIndex >= 0 ? this.currentCardIndex : 0;
        }
        
        if (this.modalCardsList.length === 0) {
            return;
        }
        
        let newIndex = this.modalCurrentIndex;
        
        if (direction === 'right') {
            newIndex = (this.modalCurrentIndex + 1) % this.modalCardsList.length;
        } else if (direction === 'left') {
            newIndex = (this.modalCurrentIndex - 1 + this.modalCardsList.length) % this.modalCardsList.length;
        }
        
        this.modalCurrentIndex = newIndex;
        
        // Mettre à jour l'indicateur de navigation
        this.updateModalNavigationIndicator();
        
        // Ouvrir la nouvelle carte dans la modal
        const targetCard = this.modalCardsList[newIndex];
        if (targetCard && window.collectionManager) {
            // Obtenir les données de la carte à partir de l'élément DOM
            const cardUuid = targetCard.dataset.cardUuid;
            const editionUuid = targetCard.dataset.editionUuid;
            
            if (cardUuid) {
                // Fermer proprement la modal actuelle et ouvrir la nouvelle
                this.switchModalCard(cardUuid, editionUuid, targetCard);
            }
        }
    }

    switchModalCard(cardUuid, editionUuid, cardElement) {
        // Extraire les données de la carte depuis l'élément DOM ou depuis les données stockées
        const cardData = this.extractCardDataFromElement(cardElement);
        
        // Ouvrir la nouvelle carte sans fermer/rouvrir la modal
        if (window.collectionManager && window.collectionManager.openCardModal) {
            window.collectionManager.openCardModal(cardUuid, editionUuid, cardData);
        }
    }

    extractCardDataFromElement(cardElement) {
        // Extraire les données de base depuis l'élément DOM
        const cardName = cardElement.querySelector('.card-name')?.textContent || '';
        const cardSet = cardElement.querySelector('.card-set')?.textContent || '';
        const cardRarity = cardElement.querySelector('.card-rarity')?.textContent || '';
        const cardElementType = cardElement.querySelector('.card-element')?.textContent || '';
        const cardClasses = cardElement.querySelector('.card-classes')?.textContent || '';
        const cardImage = cardElement.querySelector('img')?.src || '';
        
        // Construire un objet de données basique
        return {
            uuid: cardElement.dataset.cardUuid,
            edition_uuid: cardElement.dataset.editionUuid,
            name: cardName,
            set_prefix: cardSet,
            rarity: cardRarity,
            element: cardElementType,
            classes: cardClasses,
            image: cardImage,
            // Ces données seront récupérées par l'API dans openCardModal
        };
    }

    // Réinitialiser la navigation modal quand on ferme la modal
    resetModalNavigation() {
        this.modalCardsList = [];
        this.modalCurrentIndex = -1;
    }

    // Méthode publique pour initialiser la navigation modal depuis l'extérieur
    initModalNavigation(cardIndex = -1) {
        this.modalCardsList = Array.from(this.currentCards);
        this.modalCurrentIndex = cardIndex >= 0 ? cardIndex : this.currentCardIndex;
        this.addModalNavigationIndicators();
    }

    addModalNavigationIndicators() {
        const modal = document.getElementById('card-modal');
        if (!modal) return;

        // Supprimer les anciens indicateurs s'ils existent
        const existingIndicator = modal.querySelector('.modal-nav-indicator');
        const existingInstructions = modal.querySelector('.modal-nav-instructions');
        if (existingIndicator) existingIndicator.remove();
        if (existingInstructions) existingInstructions.remove();

        // Ajouter l'indicateur de position si on a plusieurs cartes
        if (this.modalCardsList.length > 1) {
            const indicator = document.createElement('div');
            indicator.className = 'modal-nav-indicator';
            indicator.textContent = `${this.modalCurrentIndex + 1} / ${this.modalCardsList.length}`;
            modal.appendChild(indicator);

            // Ajouter les instructions de navigation
            const instructions = document.createElement('div');
            instructions.className = 'modal-nav-instructions';
            instructions.innerHTML = `
                <kbd>←</kbd> Carte précédente 
                <kbd>→</kbd> Carte suivante 
                <kbd>Échap</kbd> Fermer
            `;
            modal.appendChild(instructions);
        }
    }

    updateModalNavigationIndicator() {
        const indicator = document.querySelector('.modal-nav-indicator');
        if (indicator && this.modalCardsList.length > 1) {
            indicator.textContent = `${this.modalCurrentIndex + 1} / ${this.modalCardsList.length}`;
        }
    }

    handleEscape() {
        // Fermer la modal si ouverte
        const modal = document.getElementById('card-modal');
        if (modal && modal.style.display === 'block') {
            window.collectionManager.closeModal();
            this.resetModalNavigation(); // Réinitialiser la navigation modal
            return;
        }
        
        // Désélectionner la carte actuelle
        if (this.currentCardIndex >= 0 && this.currentCards[this.currentCardIndex]) {
            this.currentCards[this.currentCardIndex].classList.remove('keyboard-selected');
            this.currentCardIndex = -1;
        }
    }

    handleSearch(e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            const searchInput = document.getElementById('collection-search') || 
                               document.getElementById('card-name');
            if (searchInput) {
                searchInput.focus();
            }
        }
    }

    handleNew(e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            // Basculer vers la vue recherche
            window.collectionManager.switchView('search');
        }
    }

    handleSave(e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            // Exporter la collection
            this.exportCollection();
        }
    }

    async exportCollection() {
        try {
            const data = await api.exportCollection('csv');
            const blob = new Blob([data], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `collection-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            api.showNotification('Collection exportée avec succès', 'success');
        } catch (error) {
            console.error('Erreur lors de l\'export:', error);
            api.showNotification('Erreur lors de l\'export', 'error');
        }
    }
}

// Gestionnaire de mise à jour automatique
class UpdateManager {
    constructor() {
        this.updateInterval = 5 * 60 * 1000; // 5 minutes
        this.lastUpdate = Date.now();
        this.init();
    }

    init() {
        // Vérifier les mises à jour périodiquement
        setInterval(() => {
            this.checkForUpdates();
        }, this.updateInterval);

        // Vérifier lors de la reprise de focus
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && Date.now() - this.lastUpdate > this.updateInterval) {
                this.checkForUpdates();
            }
        });
    }

    async checkForUpdates() {
        try {
            // Ici on pourrait vérifier s'il y a de nouvelles cartes dans l'API
            // ou d'autres mises à jour
            this.lastUpdate = Date.now();
        } catch (error) {
            console.error('Erreur lors de la vérification des mises à jour:', error);
        }
    }
}

// Gestionnaire d'importation/exportation
class ImportExportManager {
    constructor() {
        this.init();
    }

    init() {
        this.createImportExportButtons();
    }

    createImportExportButtons() {
        // Ajouter des boutons d'import/export si nécessaire
        const toolbar = document.querySelector('.toolbar');
        if (!toolbar) return;

        const exportBtn = Utils.createElement('button', 'btn-secondary', 
            '<i class="fas fa-download"></i> Exporter');
        exportBtn.addEventListener('click', () => this.showExportModal());

        const importBtn = Utils.createElement('button', 'btn-secondary', 
            '<i class="fas fa-upload"></i> Importer');
        importBtn.addEventListener('click', () => this.showImportModal());

        // toolbar.appendChild(exportBtn);
        // toolbar.appendChild(importBtn);
    }

    showExportModal() {
        const modal = this.createModal('export-modal', 'Exporter la collection', `
            <div class="export-options">
                <h3>Format d'exportation</h3>
                <div class="format-options">
                    <label>
                        <input type="radio" name="export-format" value="json" checked>
                        JSON (recommandé)
                    </label>
                    <label>
                        <input type="radio" name="export-format" value="csv">
                        CSV (Excel compatible)
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="btn-primary" onclick="importExportManager.exportCollection()">
                        Exporter
                    </button>
                    <button class="btn-secondary" onclick="importExportManager.closeModal('export-modal')">
                        Annuler
                    </button>
                </div>
            </div>
        `);
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
    }

    showImportModal() {
        const modal = this.createModal('import-modal', 'Importer une collection', `
            <div class="import-options">
                <h3>Sélectionner un fichier</h3>
                <input type="file" id="import-file" accept=".json,.csv">
                <div class="import-info">
                    <p>Formats supportés: JSON, CSV</p>
                    <p>L'importation ajoutera les cartes à votre collection existante.</p>
                </div>
                <div class="modal-actions">
                    <button class="btn-primary" onclick="importExportManager.importCollection()">
                        Importer
                    </button>
                    <button class="btn-secondary" onclick="importExportManager.closeModal('import-modal')">
                        Annuler
                    </button>
                </div>
            </div>
        `);
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
    }

    createModal(id, title, content) {
        const modal = Utils.createElement('div', 'modal', `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <span class="close-modal" onclick="importExportManager.closeModal('${id}')">&times;</span>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `);
        modal.id = id;
        return modal;
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.remove();
        }
    }

    async exportCollection() {
        try {
            const format = document.querySelector('input[name="export-format"]:checked').value;
            const data = await api.exportCollection(format);
            
            let blob, filename;
            if (format === 'csv') {
                blob = new Blob([data], { type: 'text/csv' });
                filename = `collection-${new Date().toISOString().split('T')[0]}.csv`;
            } else {
                blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                filename = `collection-${new Date().toISOString().split('T')[0]}.json`;
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            this.closeModal('export-modal');
            api.showNotification('Collection exportée avec succès', 'success');
        } catch (error) {
            console.error('Erreur lors de l\'export:', error);
            api.showNotification('Erreur lors de l\'export', 'error');
        }
    }

    async importCollection() {
        try {
            const fileInput = document.getElementById('import-file');
            const file = fileInput.files[0];
            
            if (!file) {
                api.showNotification('Veuillez sélectionner un fichier', 'warning');
                return;
            }

            const text = await file.text();
            const format = file.name.endsWith('.csv') ? 'csv' : 'json';
            
            // Ici on devrait appeler l'API d'importation
            // const result = await api.importCollection(text, format);
            
            this.closeModal('import-modal');
            api.showNotification('Collection importée avec succès', 'success');
            
            // Recharger la collection
            if (window.collectionManager) {
                window.collectionManager.loadMyCollection();
            }
        } catch (error) {
            console.error('Erreur lors de l\'import:', error);
            api.showNotification('Erreur lors de l\'import', 'error');
        }
    }
}

// Gestionnaire de performance
class PerformanceManager {
    constructor() {
        this.observeImages();
        this.enableVirtualScrolling();
    }

    observeImages() {
        // Lazy loading pour les images
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        });
    }

    enableVirtualScrolling() {
        // TODO: Implémenter le virtual scrolling pour de grandes collections
    }
}

// Gestionnaire de cache avancé
class CacheManager {
    constructor() {
        this.cache = new Map();
        this.maxSize = 100;
        this.ttl = 10 * 60 * 1000; // 10 minutes
    }

    set(key, value) {
        if (this.cache.size >= this.maxSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }

        this.cache.set(key, {
            value,
            timestamp: Date.now()
        });
    }

    get(key) {
        const item = this.cache.get(key);
        if (!item) return null;

        if (Date.now() - item.timestamp > this.ttl) {
            this.cache.delete(key);
            return null;
        }

        return item.value;
    }

    clear() {
        this.cache.clear();
    }
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser les gestionnaires
    window.themeManager = new ThemeManager();
    window.mobileMenuManager = new MobileMenuManager();
    window.keyboardManager = new KeyboardManager();
    window.updateManager = new UpdateManager();
    window.importExportManager = new ImportExportManager();
    window.performanceManager = new PerformanceManager();
    window.cacheManager = new CacheManager();

    // Ajouter des utilitaires globaux
    window.utils = Utils;

    // Gérer les erreurs globales
    window.addEventListener('error', (e) => {
        console.error('Erreur globale:', e.error);
        api.showNotification('Une erreur inattendue s\'est produite', 'error');
    });

    // Gérer les erreurs de promesses non capturées
    window.addEventListener('unhandledrejection', (e) => {
        console.error('Promesse rejetée non gérée:', e.reason);
        api.showNotification('Erreur lors d\'une opération', 'error');
    });

    // Afficher un message de bienvenue
    console.log('%c🎴 Collection Grand Archive', 
        'font-size: 20px; font-weight: bold; color: #6366f1;');
    console.log('Application initialisée avec succès !');
});

// Service Worker pour le cache (optionnel)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/grand-archive-collection/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}