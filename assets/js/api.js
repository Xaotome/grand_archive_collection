class API {
    constructor() {
        this.baseUrl = './api';
        this.gatcgApiUrl = 'https://api.gatcg.com';
        this.justTCGApiUrl = 'https://api.justtcg.com';
        this.justTCGApiKey = 'tcg_170ff302dbe74270a31655b1256fe621';
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    // Recherche de cartes
    async searchCards(params = {}) {
        const queryParams = new URLSearchParams();
        Object.keys(params).forEach(key => {
            if (params[key] !== undefined && params[key] !== '') {
                queryParams.append(key, params[key]);
            }
        });

        return this.request(`/cards.php?action=search&${queryParams.toString()}`);
    }

    // Récupérer une carte par UUID
    async getCard(uuid) {
        return this.request(`/cards.php?action=get_card&uuid=${uuid}`);
    }

    // Récupérer ma collection
    async getMyCollection(filters = {}) {
        const queryParams = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key] !== undefined && filters[key] !== '') {
                queryParams.append(key, filters[key]);
            }
        });

        return this.request(`/cards.php?action=collection&${queryParams.toString()}`);
    }

    // Ajouter à la collection
    async addToCollection(cardUuid, editionUuid, quantity = 1, isFoil = false, options = {}) {
        return this.request('/cards.php?action=add_to_collection', {
            method: 'POST',
            body: JSON.stringify({
                card_uuid: cardUuid,
                edition_uuid: editionUuid,
                quantity: quantity,
                is_foil: isFoil,
                options: options
            })
        });
    }

    // Mettre à jour la quantité
    async updateQuantity(cardUuid, editionUuid, isFoil = false, quantity = 0, isCsr = null) {
        return this.request('/cards.php?action=update_quantity', {
            method: 'PUT',
            body: JSON.stringify({
                card_uuid: cardUuid,
                edition_uuid: editionUuid,
                is_foil: isFoil,
                quantity: quantity,
                is_csr: isCsr
            })
        });
    }

    // Supprimer de la collection
    async removeFromCollection(cardUuid, editionUuid, isFoil = false, quantity = null) {
        return this.request('/cards.php?action=remove_from_collection', {
            method: 'DELETE',
            body: JSON.stringify({
                card_uuid: cardUuid,
                edition_uuid: editionUuid,
                is_foil: isFoil,
                quantity: quantity
            })
        });
    }

    // Récupérer les statistiques
    async getStats() {
        return this.request('/cards.php?action=stats');
    }

    // Récupérer toutes les extensions
    async getSets() {
        return this.request('/cards.php?action=sets');
    }

    // Récupérer toutes les classes
    async getClasses() {
        return this.request('/cards.php?action=classes');
    }

    // Récupérer tous les éléments
    async getElements() {
        return this.request('/cards.php?action=elements');
    }

    // Récupérer les classes présentes dans la collection
    async getCollectionClasses() {
        return this.request('/cards.php?action=collection_classes');
    }

    // Récupérer les éléments présents dans la collection
    async getCollectionElements() {
        return this.request('/cards.php?action=collection_elements');
    }

    // Récupérer les extensions présentes dans la collection
    async getCollectionSets() {
        return this.request('/cards.php?action=collection_sets');
    }

    // Récupérer les cartes récemment ajoutées
    async getRecentlyAdded(limit = 10) {
        return this.request(`/cards.php?action=recent&limit=${limit}`);
    }

    // Exporter la collection
    async exportCollection(format = 'json') {
        if (format === 'csv') {
            const response = await fetch(`${this.baseUrl}/cards.php?action=export&format=csv`);
            return response.text();
        } else {
            return this.request(`/cards.php?action=export&format=${format}`);
        }
    }

    // Récupérer une carte depuis l'API Grand Archive
    async fetchCardFromGATCG(setPrefix, collectorNumber) {
        try {
            const response = await fetch(`${this.gatcgApiUrl}/cards/${setPrefix}/${collectorNumber}`);
            
            if (!response.ok) {
                throw new Error(`Carte non trouvée: ${setPrefix}/${collectorNumber}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la récupération depuis GATCG API:', error);
            throw error;
        }
    }

    // Importer une carte depuis l'API et l'ajouter à la base
    async importCardFromAPI(setPrefix, collectorNumber) {
        return this.request('/cards.php?action=fetch_from_api', {
            method: 'POST',
            body: JSON.stringify({
                set_prefix: setPrefix,
                collector_number: collectorNumber
            })
        });
    }

    // Rechercher des cartes dans l'API GATCG (optionnel - pour découverte)
    async searchGATCGCards(query) {
        try {
            const response = await fetch(`${this.gatcgApiUrl}/cards?name=${encodeURIComponent(query)}`);
            
            if (!response.ok) {
                throw new Error('Erreur lors de la recherche dans l\'API GATCG');
            }

            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la recherche GATCG:', error);
            throw error;
        }
    }

    // Synchroniser toutes les cartes depuis l'API GATCG
    async syncAllCards(limit = null, offset = 0) {
        const params = new URLSearchParams();
        if (limit) params.append('limit', limit);
        if (offset) params.append('offset', offset);
        
        return this.request(`/cards.php?action=sync_cards&${params.toString()}`);
    }

    // Obtenir le statut de synchronisation
    async getSyncStatus() {
        return this.request('/cards.php?action=sync_status');
    }

    // Obtenir le nombre total de cartes
    async getCardsCount() {
        return this.request('/cards.php?action=cards_count');
    }

    // === JUSTTCG API METHODS ===

    // Effectuer une requête vers l'API JustTCG via le proxy
    async justTCGRequest(endpoint, options = {}) {
        const proxyUrl = `${this.baseUrl}/justtcg_proxy.php?endpoint=${encodeURIComponent(endpoint)}`;

        try {
            const response = await fetch(proxyUrl);
            
            if (!response.ok) {
                throw new Error(`JustTCG API error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('JustTCG API Request Error:', error);
            throw error;
        }
    }

    // Rechercher une carte spécifique par nom dans JustTCG
    async searchJustTCGCardByName(cardName, gameKey = 'grand-archive') {
        try {
            const cacheKey = `justtcg-search-${gameKey}-${cardName}`;
            return await this.cachedRequest(cacheKey, async () => {
                const endpoint = `/cards/search?game=${gameKey}&name=${encodeURIComponent(cardName)}&limit=10`;
                const result = await this.justTCGRequest(endpoint);
                return result;
            });
        } catch (error) {
            console.warn('JustTCG API indisponible:', error.message);
            // Retourner silencieusement un résultat vide plutôt qu'une erreur
            return { cards: [] };
        }
    }

    // Récupérer les détails complets d'une carte depuis JustTCG
    async getJustTCGCardDetails(cardId) {
        try {
            const cacheKey = `justtcg-card-${cardId}`;
            return await this.cachedRequest(cacheKey, async () => {
                const endpoint = `/cards/${cardId}`;
                const result = await this.justTCGRequest(endpoint);
                return result;
            });
        } catch (error) {
            console.warn('JustTCG détails indisponibles:', error.message);
            return null;
        }
    }

    // Récupérer les prix d'une carte depuis JustTCG
    async getJustTCGCardPrices(cardId) {
        try {
            const cacheKey = `justtcg-prices-${cardId}`;
            return await this.cachedRequest(cacheKey, async () => {
                const endpoint = `/cards/${cardId}/prices`;
                const result = await this.justTCGRequest(endpoint);
                return result;
            });
        } catch (error) {
            console.warn('JustTCG prix indisponibles:', error.message);
            return { prices: [] };
        }
    }

    // Enrichir les données d'une carte locale avec les informations JustTCG
    async enrichCardWithJustTCG(localCard) {
        if (!localCard || !localCard.name) {
            return localCard;
        }

        try {
            // Rechercher la carte par nom
            const searchResult = await this.searchJustTCGCardByName(localCard.name);
            
            if (!searchResult.cards || searchResult.cards.length === 0) {
                console.log(`Carte non trouvée sur JustTCG: ${localCard.name}`);
                return { ...localCard, justTCGData: null };
            }

            // Prendre la première carte correspondante
            const justTCGCard = searchResult.cards[0];
            
            // Récupérer les détails complets et les prix
            const [cardDetails, cardPrices] = await Promise.all([
                this.getJustTCGCardDetails(justTCGCard.id),
                this.getJustTCGCardPrices(justTCGCard.id)
            ]);

            return {
                ...localCard,
                justTCGData: {
                    id: justTCGCard.id,
                    details: cardDetails,
                    prices: cardPrices.prices || [],
                    marketUrl: `https://justtcg.com/cards/${justTCGCard.id}`,
                    lastUpdated: new Date().toISOString()
                }
            };

        } catch (error) {
            console.error('Erreur enrichissement JustTCG:', error);
            return { ...localCard, justTCGData: null };
        }
    }

    // Utilitaires pour gérer les images
    getCardImageUrl(imagePath) {
        if (!imagePath) {
            return '/grand-archive-collection/assets/images/card-back.jpg';
        }
        
        if (imagePath.startsWith('http')) {
            return imagePath;
        }
        
        return `${this.gatcgApiUrl}${imagePath}`;
    }

    // Traitement des erreurs API
    handleError(error) {
        let message = 'Une erreur est survenue';
        
        if (error.message) {
            message = error.message;
        } else if (typeof error === 'string') {
            message = error;
        }

        // Créer une notification d'erreur
        this.showNotification(message, 'error');
        console.error('API Error:', error);
    }

    // Système de notifications simple
    showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        // Ajouter les styles si pas déjà fait
        if (!document.querySelector('#notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 16px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    max-width: 400px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                }
                .notification-info { background: #3b82f6; }
                .notification-success { background: #10b981; }
                .notification-warning { background: #f59e0b; }
                .notification-error { background: #ef4444; }
                .notification.show { transform: translateX(0); }
                .notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    line-height: 1;
                }
            `;
            document.head.appendChild(styles);
        }

        // Ajouter au DOM
        document.body.appendChild(notification);

        // Animer l'entrée
        setTimeout(() => notification.classList.add('show'), 10);

        // Gérer la fermeture
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });

        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Debounce pour limiter les appels API
    debounce(func, wait) {
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

    // Cache simple pour éviter les requêtes répétées

    async cachedRequest(key, requestFunction) {
        const now = Date.now();
        const cached = this.cache.get(key);

        if (cached && (now - cached.timestamp) < this.cacheTimeout) {
            return cached.data;
        }

        const data = await requestFunction();
        this.cache.set(key, { data, timestamp: now });
        return data;
    }
}

// Instance globale de l'API
window.api = new API();