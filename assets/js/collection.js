class CollectionManager {
    constructor() {
        this.currentView = 'collection';
        this.currentCardData = null;
        this.searchTimeout = null;
        this.charts = {};
        this.api = window.api; // Référence locale à l'API
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchView(e.target.dataset.view);
            });
        });

        // Toggle de vue (grille/liste)
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const button = e.currentTarget; // Utiliser currentTarget au lieu de target
                this.toggleViewMode(button.dataset.view);
            });
        });

        // Recherche dans la collection
        const collectionSearch = document.getElementById('collection-search');
        if (collectionSearch) {
            collectionSearch.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.filterCollection(e.target.value);
                }, 300);
            });
        }

        // Filtres de collection
        const setFilter = document.getElementById('collection-set-filter');
        const classFilter = document.getElementById('collection-class-filter');
        const elementFilter = document.getElementById('collection-element-filter');
        
        if (setFilter) {
            setFilter.addEventListener('change', () => {
                this.filterCollection(collectionSearch ? collectionSearch.value : '');
            });
        }
        
        if (classFilter) {
            classFilter.addEventListener('change', () => {
                this.filterCollection(collectionSearch ? collectionSearch.value : '');
            });
        }
        
        if (elementFilter) {
            elementFilter.addEventListener('change', () => {
                this.filterCollection(collectionSearch ? collectionSearch.value : '');
            });
        }

        // Formulaire de recherche
        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.searchCards();
            });
        }

        // Modal
        this.setupModal();
    }

    setupModal() {
        const modal = document.getElementById('card-modal');
        const closeBtn = modal.querySelector('.close-modal');
        
        closeBtn.addEventListener('click', () => {
            this.closeModal();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal();
            }
        });

        // Contrôles de quantité
        const quantityControls = modal.querySelectorAll('.quantity-btn');
        quantityControls.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                this.updateCardQuantity(action);
            });
        });

        // Contrôle foil
        const foilCheckbox = document.getElementById('card-foil');
        if (foilCheckbox) {
            foilCheckbox.addEventListener('change', (e) => {
                this.toggleFoilStatus(e.target.checked);
            });
        }
    }

    async loadInitialData() {
        try {
            // Charger les données pour les selects
            await this.loadSetsAndClasses();
            
            // Charger la collection par défaut seulement si un utilisateur est connecté
            if (this.isUserLoggedIn()) {
                await this.loadMyCollection();
            }
            
        } catch (error) {
            console.error('Erreur lors du chargement initial:', error);
            this.api.showNotification('Erreur lors du chargement des données', 'error');
        }
    }

    isUserLoggedIn() {
        // Vérifier s'il y a des éléments de collection sur la page (indicateur d'utilisateur connecté)
        return document.querySelector('#collection-cards') !== null && 
               !document.querySelector('.auth-required');
    }

    async loadSetsAndClasses() {
        try {
            const [setsResponse, classesResponse, elementsResponse, 
                   collectionSetsResponse, collectionClassesResponse, collectionElementsResponse] = await Promise.all([
                this.api.getSets(),
                this.api.getClasses(),
                this.api.getElements(),
                this.api.getCollectionSets(),
                this.api.getCollectionClasses(),
                this.api.getCollectionElements()
            ]);

            // Pour le formulaire de recherche (toutes les options)
            if (setsResponse.success) {
                this.populateSetSelect(setsResponse.data);
            }

            if (classesResponse.success) {
                this.populateClassSelect(classesResponse.data);
            }

            if (elementsResponse.success) {
                this.populateElementSelect(elementsResponse.data);
            }

            // Pour les filtres de collection (seulement les options présentes dans la collection)
            if (collectionSetsResponse.success) {
                this.populateCollectionSetFilter(collectionSetsResponse.data);
            }

            if (collectionClassesResponse.success) {
                this.populateCollectionClassFilter(collectionClassesResponse.data);
            }

            if (collectionElementsResponse.success) {
                this.populateCollectionElementFilter(collectionElementsResponse.data);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des sets/classes/elements:', error);
        }
    }

    populateSetSelect(sets) {
        const setSelect = document.getElementById('card-set');
        if (!setSelect) return;

        setSelect.innerHTML = '<option value="">Toutes les extensions</option>';
        sets.forEach(set => {
            const option = document.createElement('option');
            option.value = set.prefix;
            option.textContent = `${set.name} (${set.prefix})`;
            setSelect.appendChild(option);
        });
    }

    populateClassSelect(classes) {
        const classSelect = document.getElementById('card-class');
        if (!classSelect) return;

        classSelect.innerHTML = '<option value="">Toutes les classes</option>';
        classes.forEach(classData => {
            const option = document.createElement('option');
            option.value = classData.class_name;
            option.textContent = classData.class_name;
            classSelect.appendChild(option);
        });
    }

    populateElementSelect(elements) {
        const elementSelect = document.getElementById('card-element');
        if (!elementSelect) return;

        elementSelect.innerHTML = '<option value="">Tous les éléments</option>';
        elements.forEach(element => {
            const option = document.createElement('option');
            option.value = element;
            option.textContent = element;
            elementSelect.appendChild(option);
        });
    }

    populateCollectionSetFilter(sets) {
        const setFilter = document.getElementById('collection-set-filter');
        if (!setFilter) return;

        setFilter.innerHTML = '<option value="">Toutes extensions</option>';
        sets.forEach(set => {
            const option = document.createElement('option');
            option.value = set.prefix;
            option.textContent = `${set.name} (${set.prefix})`;
            setFilter.appendChild(option);
        });
    }

    populateCollectionClassFilter(classes) {
        const classFilter = document.getElementById('collection-class-filter');
        if (!classFilter) return;

        classFilter.innerHTML = '<option value="">Toutes classes</option>';
        classes.forEach(classData => {
            const option = document.createElement('option');
            option.value = classData.class_name;
            option.textContent = classData.class_name;
            classFilter.appendChild(option);
        });
    }

    populateCollectionElementFilter(elements) {
        const elementFilter = document.getElementById('collection-element-filter');
        if (!elementFilter) return;

        elementFilter.innerHTML = '<option value="">Tous éléments</option>';
        elements.forEach(element => {
            const option = document.createElement('option');
            option.value = element;
            option.textContent = element;
            elementFilter.appendChild(option);
        });
    }

    switchView(viewName) {
        // Mettre à jour les boutons de navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${viewName}"]`).classList.add('active');

        // Cacher toutes les vues
        document.querySelectorAll('.view').forEach(view => {
            view.classList.remove('active');
        });

        // Afficher la vue sélectionnée
        document.getElementById(`${viewName}-view`).classList.add('active');
        this.currentView = viewName;

        // Charger les données spécifiques à la vue
        switch (viewName) {
            case 'collection':
                if (this.isUserLoggedIn()) {
                    this.loadMyCollection();
                }
                break;
            case 'search':
                // Pas de chargement initial pour la recherche
                break;
            case 'stats':
                if (this.isUserLoggedIn()) {
                    this.loadStats();
                }
                break;
            case 'sync':
                if (this.isUserLoggedIn()) {
                    this.loadSyncView();
                }
                break;
        }
    }

    toggleViewMode(mode) {
        // Vérifier que le mode est valide
        if (!mode || (mode !== 'grid' && mode !== 'list')) {
            console.error('Mode de vue invalide:', mode);
            return;
        }

        // Retirer la classe active de tous les boutons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Ajouter la classe active au bouton sélectionné
        const activeButton = document.querySelector(`[data-view="${mode}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        } else {
            console.error('Bouton de vue non trouvé pour le mode:', mode);
            return;
        }

        // Changer la vue de la grille
        const cardsGrid = document.getElementById('collection-cards');
        if (cardsGrid) {
            if (mode === 'list') {
                cardsGrid.classList.add('list-view');
            } else {
                cardsGrid.classList.remove('list-view');
            }
        } else {
            console.error('Conteneur collection-cards non trouvé');
        }
    }

    async loadMyCollection() {
        try {
            this.showLoading('collection-cards');
            
            const response = await this.api.getMyCollection();
            
            if (response.success) {
                this.displayCards(response.data, 'collection-cards');
            } else {
                throw new Error('Erreur lors du chargement de la collection');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('collection-cards', 'Erreur lors du chargement de votre collection');
        }
    }

    async filterCollection(query = '') {
        try {
            const filters = { name: query };
            
            // Ajouter les filtres de classe, élément et extension
            const setFilter = document.getElementById('collection-set-filter');
            const classFilter = document.getElementById('collection-class-filter');
            const elementFilter = document.getElementById('collection-element-filter');
            
            if (setFilter && setFilter.value) {
                filters.set = setFilter.value;
            }
            
            if (classFilter && classFilter.value) {
                filters.class = classFilter.value;
            }
            
            if (elementFilter && elementFilter.value) {
                filters.element = elementFilter.value;
            }
            
            console.log('Filtres appliqués:', filters); // Debug
            const response = await this.api.getMyCollection(filters);
            console.log('Réponse API collection:', response); // Debug
            
            if (response.success) {
                this.displayCards(response.data, 'collection-cards');
            } else {
                console.error('Erreur API collection:', response.error);
            }
        } catch (error) {
            console.error('Erreur lors du filtrage:', error);
        }
    }

    async searchCards() {
        try {
            this.showLoading('search-results');
            
            const form = document.getElementById('search-form');
            const formData = new FormData(form);
            
            // Récupérer les valeurs directement depuis les éléments du formulaire
            const nameInput = document.getElementById('card-name');
            const setSelect = document.getElementById('card-set');
            const classSelect = document.getElementById('card-class');
            const elementSelect = document.getElementById('card-element');
            
            const params = {
                name: nameInput ? nameInput.value.trim() : '',
                set_prefix: setSelect ? setSelect.value : '',
                class: classSelect ? classSelect.value : '',
                element: elementSelect ? elementSelect.value : ''
            };

            console.log('Paramètres de recherche:', params); // Debug

            const response = await this.api.searchCards(params);
            console.log('Réponse recherche:', response); // Debug
            
            if (response && response.success) {
                console.log('Nombre de résultats:', response.data.length); // Debug
                this.displayCards(response.data, 'search-results');
            } else {
                console.error('Erreur de recherche:', response?.error || 'Erreur inconnue');
                throw new Error(response?.error || 'Erreur lors de la recherche');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('search-results', 'Erreur lors de la recherche: ' + error.message);
        }
    }

    displayCards(cards, containerId) {
        const container = document.getElementById(containerId);
        
        if (!cards || cards.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Aucune carte trouvée</p>
                    ${container.id === 'search-results' ? 
                        '<small>Si c\'est votre première utilisation, allez dans "Synchronisation" pour importer les cartes depuis l\'API.</small>' 
                        : ''}
                </div>
            `;
            return;
        }

        // Déterminer le contexte (collection ou recherche)
        const isSearchView = containerId === 'search-results';
        container.innerHTML = cards.map(card => this.createCardHTML(card, isSearchView)).join('');
        
        // Ajouter les événements aux cartes
        container.querySelectorAll('.card-item').forEach((cardEl, index) => {
            cardEl.addEventListener('click', () => {
                const cardUuid = cardEl.dataset.cardUuid;
                const editionUuid = cardEl.dataset.editionUuid;
                // Passer les données complètes de la carte pour préserver l'image CSR
                const cardData = cards[index];
                this.openCardModal(cardUuid, editionUuid, cardData);
            });

            // Améliorer l'accessibilité tactile pour mobile
            cardEl.addEventListener('touchstart', (e) => {
                cardEl.style.transform = 'scale(0.98)';
            }, { passive: true });

            cardEl.addEventListener('touchend', (e) => {
                setTimeout(() => {
                    cardEl.style.transform = '';
                }, 150);
            }, { passive: true });
        });
    }

    createCardHTML(card, showOwnershipStatus = false) {
        const imageUrl = this.api.getCardImageUrl(card.image);
        const rarityClass = `rarity-${card.rarity}`;
        const quantityBadge = card.owned_quantity > 0 ? 
            `<div class="quantity-badge">${card.owned_quantity}</div>` : '';
        // Déterminer le type de carte et l'indicateur approprié
        let cardTypeClass = '';
        let typeIndicator = '';
        
        // Conversion des valeurs de la base (0/1) en booléens
        const isFoil = Boolean(parseInt(card.owned_foil));
        const isCsr = Boolean(parseInt(card.owned_csr));
        
        // Debug pour CSR
        if (card.rarity === 7 || isCsr) {
            console.log('CSR Debug:', {
                name: card.name,
                rarity: card.rarity,
                owned_csr: card.owned_csr,
                isCsr: isCsr
            });
        }
        
        if (isCsr) {
            cardTypeClass = 'csr-card';
            typeIndicator = '<div class="card-type-badge csr">CSR</div>';
        } else if (isFoil) {
            cardTypeClass = 'foil-card';
            typeIndicator = '<div class="card-type-badge foil">FOIL</div>';
        }

        // Debug pour vérifier les UUIDs
        if (!card.uuid) {
            console.warn('Carte sans UUID:', card);
        }

        return `
            <div class="card-item ${cardTypeClass}" data-card-uuid="${card.uuid || ''}" data-edition-uuid="${card.edition_uuid || ''}" data-foil="${isFoil ? 'true' : 'false'}" data-csr="${isCsr ? 'true' : 'false'}">
                <div class="card-image">
                    <img src="${imageUrl}" alt="${card.name}" loading="lazy">
                    ${quantityBadge}
                    ${typeIndicator}
                </div>
                <div class="card-info">
                    <div class="card-name">${card.name}</div>
                    <span class="card-set">${card.set_name} (${card.set_prefix})</span>
                    <span class="card-rarity ${rarityClass}">${this.getRarityName(card.rarity)}</span>
                    ${card.element ? `<span class="card-element">${card.element}</span>` : ''}
                    ${card.classes ? `<span class="card-classes">${Array.isArray(card.classes) ? card.classes.join(', ') : card.classes}</span>` : ''}
                </div>
                ${showOwnershipStatus ? `
                <div class="card-actions">
                    ${card.owned_quantity > 0 ? `<span class="owned-indicator">✓ Possédée (${card.owned_quantity})</span>` : `<span class="not-owned">Non possédée</span>`}
                </div>` : ''}
            </div>
        `;
    }

    getRarityName(rarity) {
        const rarityNames = {
            1: 'Commune',
            2: 'Rare',
            3: 'Super Rare',
            4: 'Ultra Rare',
            5: 'Legendary'
        };
        return rarityNames[rarity] || 'Inconnue';
    }

    async openCardModal(cardUuid, editionUuid, cardData = null) {
        try {
            console.log('Ouverture modal pour carte:', cardUuid, 'édition:', editionUuid);
            
            if (!cardUuid) {
                throw new Error('UUID de carte manquant');
            }
            
            // Si on a déjà les données de la carte (depuis la recherche), on les utilise
            // pour préserver l'image CSR correcte
            if (cardData) {
                this.currentCardData = cardData;
                this.populateModal(cardData);
                this.showModal();
            } else {
                const response = await this.api.getCard(cardUuid);
                console.log('Réponse API getCard:', response);
                
                if (response && response.success) {
                    this.currentCardData = response.data;
                    this.populateModal(response.data);
                    this.showModal();
                } else {
                    const errorMsg = response?.error || 'Réponse invalide de l\'API';
                    throw new Error(errorMsg);
                }
            }
        } catch (error) {
            console.error('Erreur lors de l\'ouverture de la modal:', error);
            this.api.showNotification('Erreur lors du chargement des détails de la carte: ' + error.message, 'error');
        }
    }

    populateModal(card) {
        console.log('Données de la carte pour la modal:', card); // Debug
        
        const modal = document.getElementById('card-modal');
        const imageUrl = this.api.getCardImageUrl(card.image);

        // Image et nom
        document.getElementById('modal-card-image').src = imageUrl;
        document.getElementById('modal-card-name').textContent = card.name;

        // Informations
        document.getElementById('modal-card-set').textContent = `${card.set_name || 'Inconnu'} (${card.set_prefix || 'UNK'})`;
        document.getElementById('modal-card-number').textContent = card.collector_number || 'N/A';
        document.getElementById('modal-card-rarity').textContent = this.getRarityName(card.rarity);
        
        // Gestion sécurisée des tableaux JSON
        try {
            const classes = card.classes ? JSON.parse(card.classes) : [];
            document.getElementById('modal-card-class').textContent = 
                Array.isArray(classes) ? (classes.join(', ') || 'Aucune') : (card.classes || 'Aucune');
        } catch (e) {
            document.getElementById('modal-card-class').textContent = card.classes || 'Aucune';
        }
        
        try {
            const types = card.types ? JSON.parse(card.types) : [];
            document.getElementById('modal-card-type').textContent = 
                Array.isArray(types) ? (types.join(', ') || 'Inconnu') : (card.types || 'Inconnu');
        } catch (e) {
            document.getElementById('modal-card-type').textContent = card.types || 'Inconnu';
        }

        // Effet avec parsing des badges
        const effectEl = document.getElementById('modal-card-effect');
        
        if (card.effect_html) {
            effectEl.innerHTML = this.parseEffectBadges(card.effect_html);
        } else if (card.effect) {
            effectEl.innerHTML = this.parseEffectBadges(card.effect);
        } else {
            effectEl.textContent = 'Aucun effet';
        }

        // Quantité dans la collection
        document.getElementById('card-quantity').textContent = card.owned_quantity || 0;
        document.getElementById('card-foil').checked = Boolean(parseInt(card.owned_foil));
    }

    showModal() {
        document.getElementById('card-modal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        document.getElementById('card-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
        this.currentCardData = null;
    }

    /**
     * Parse les effets de cartes et transforme les éléments entre crochets en badges stylisés
     * @param {string} effectText - Le texte d'effet à parser
     * @returns {string} - Le texte avec les badges HTML
     */
    parseEffectBadges(effectText) {
        if (!effectText || typeof effectText !== 'string') {
            return effectText || '';
        }

        let processedText = effectText;

        // 1. Convertir les badges de l'API Grand Archive (effect__bubble) vers notre style
        if (effectText.includes('effect__bubble')) {
            // Remplacer les <span class="effect__bubble">...</span> par nos badges custom
            processedText = processedText.replace(
                /<span class="effect__bubble">([^<]+)<\/span>/g, 
                '<span class="effect-badge">$1</span>'
            );
        }
        
        // 1.5. Convertir les labels de l'API Grand Archive (effect__label) vers du gras
        if (effectText.includes('effect__label')) {
            // Remplacer les <span class="effect__label">...</span> par <strong>
            processedText = processedText.replace(
                /<span class="effect__label">([^<]+)<\/span>/g, 
                '<strong>$1</strong>'
            );
        }
        
        // 2. Traiter les données en texte brut (escape HTML si nécessaire)
        else if (!effectText.includes('<') || !effectText.includes('>')) {
            processedText = this.escapeHtml(effectText);
        }

        // 3. Convertir les crochets [...]  en badges (pour données locales)
        const bracketRegex = /\[([^\]]+)\]/g;
        const matches = processedText.match(bracketRegex);
        
        if (matches) {
            processedText = processedText.replace(bracketRegex, (match, content) => {
                return `<span class="effect-badge">${content.trim()}</span>`;
            });
        }

        // 4. Convertir les textes **gras** en balises <strong>
        processedText = this.parseBoldText(processedText);

        // 5. Convertir les champs spéciaux en icônes et styles
        processedText = this.parseSpecialFields(processedText);

        // 6. Formater automatiquement avec des sauts de ligne intelligents
        processedText = this.formatEffectText(processedText);

        return processedText;
    }

    /**
     * Parse les champs spéciaux et les convertit en icônes et styles appropriés
     * @param {string} text - Le texte à parser
     * @returns {string} - Le texte avec les icônes et styles
     */
    parseSpecialFields(text) {
        if (!text || typeof text !== 'string') {
            return text || '';
        }

        let processed = text;

        // 1. Convertir [POWER] en icône épée
        processed = processed.replace(
            /\[POWER\]/gi,
            '<span class="effect__icon effect__icon--sword" title="Power"></span>'
        );

        // 2. Convertir [REST] en icône repos
        processed = processed.replace(
            /\[REST\]/gi,
            '<span class="effect__icon effect__icon--rest" title="Rest"></span>'
        );

        // 3. Convertir [LIFE] en icône cœur
        processed = processed.replace(
            /\[LIFE\]/gi,
            '<span class="effect__icon effect__icon--heart" title="Life"></span>'
        );

        // 4. Convertir les chiffres entre parenthèses (X) en coûts stylisés
        processed = processed.replace(
            /\((\d+)\)/g,
            '<span class="effect-cost">$1</span>'
        );

        return processed;
    }

    /**
     * Parse les textes entre ** pour les convertir en gras
     * @param {string} text - Le texte à parser
     * @returns {string} - Le texte avec les balises <strong>
     */
    parseBoldText(text) {
        if (!text || typeof text !== 'string') {
            return text || '';
        }

        // Regex pour capturer le texte entre **texte**
        // Utilise un lookahead/lookbehind négatif pour éviter les conflits avec du HTML existant
        const boldRegex = /\*\*([^*]+(?:\*(?!\*)[^*]*)*)\*\*/g;
        
        // Remplacer tous les **texte** par <strong>texte</strong> avec espaces
        const processed = text.replace(boldRegex, (match, content) => {
            // S'assurer que le contenu n'est pas vide et nettoyer les espaces
            const cleanContent = content.trim();
            if (cleanContent) {
                return ` <strong>${cleanContent}</strong> `;
            }
            return match; // Retourner le texte original si contenu vide
        });

        return processed;
    }

    /**
     * Formate automatiquement le texte d'effet avec des sauts de ligne intelligents
     * @param {string} text - Le texte à formater
     * @returns {string} - Le texte formaté avec <br> aux endroits appropriés
     */
    formatEffectText(text) {
        if (!text || typeof text !== 'string') {
            return text;
        }

        let formatted = text;

        // RÈGLES SPÉCIALES POUR CLASS BONUS
        // Règle 1: Class Bonus + phrase suivante = même ligne (pas de saut)
        // Règle 2: Class Bonus + Class Bonus = juste un espace (pas de saut)
        
        // Patterns pour identifier les points de saut de ligne appropriés
        const lineBreakPatterns = [
            // Après une phrase se terminant par un point, suivie d'un badge (sauf Class Bonus)
            /(\.)(\s*)(<span class="effect-badge">(?!Class Bonus))/g,
            
            // Après un badge suivi d'une phrase complète qui commence par une majuscule
            // EXCEPTION: Ne pas couper si c'est Class Bonus suivi d'une phrase
            /(<span class="effect-badge">(?!Class Bonus)[^<]*<\/span>)(\s*)([A-Z][^<]*?[.!?])(\s*)(<span class="effect-badge">)/g,
            
            // Après une parenthèse fermante suivie d'un badge (sauf Class Bonus) ou d'une phrase
            /(\))(\s*)(<span class="effect-badge">(?!Class Bonus)|[A-Z])/g,
            
            // Après certains badges spécifiques qui marquent souvent de nouvelles sections
            // EXCEPTION: Exclure Class Bonus de cette règle
            /(<span class="effect-badge">(?:On Enter|On Exit|Level \d+|Memory \d+\+?|Activate)<\/span>)(\s*)/g,
            
            // Après les reminder texts (texte entre parenthèses long)
            /(effect__reminder">[^<]*<\/span>)(\s*)(<span class="effect-badge">|[A-Z])/g,
            
            // Après une phrase se terminant par un point, suivie d'un Class Bonus = GARDER sur même ligne
            // (Cette règle inverse la première pour Class Bonus)
            
            // Après un badge NON-Class Bonus, suivi d'un Class Bonus = saut de ligne
            /(<span class="effect-badge">(?!Class Bonus)[^<]*<\/span>)(\s*)([.!?]?)(\s*)(<span class="effect-badge">Class Bonus<\/span>)/g,
        ];

        // Appliquer chaque pattern avec gestion spéciale pour Class Bonus
        lineBreakPatterns.forEach((pattern, index) => {
            if (pattern.source.includes('(?:On Enter|On Exit|')) {
                // Pattern pour badges de début de section (sauf Class Bonus)
                formatted = formatted.replace(pattern, (match, badge, space) => {
                    return badge + '<br><br>';
                });
            } else if (pattern.source.includes('effect__reminder')) {
                // Pattern pour les reminder texts
                formatted = formatted.replace(pattern, '$1<br>$3');
            } else if (pattern.source.includes('(?!Class Bonus)|[A-Z]')) {
                // Pattern pour après parenthèse (sauf Class Bonus)
                formatted = formatted.replace(pattern, '$1<br>$3');
            } else if (pattern.source.includes('(?!Class Bonus)[^<]*<\/span>') && pattern.source.includes('[A-Z][^<]*?[.!?]')) {
                // Pattern complexe avec phrase complète (sauf Class Bonus)
                formatted = formatted.replace(pattern, '$1<br>$4$5');
            } else if (pattern.source.includes('(?!Class Bonus)') && pattern.source.includes('(\\.)')) {
                // Pattern simple après point (sauf Class Bonus)
                formatted = formatted.replace(pattern, '$1<br>$3');
            } else if (pattern.source.includes('(?!Class Bonus)[^<]*<\/span>.*Class Bonus')) {
                // Pattern spécial: badge non-Class Bonus suivi de Class Bonus
                formatted = formatted.replace(pattern, '$1$3<br>$5');
            }
        });

        // RÈGLES SPÉCIALES POST-TRAITEMENT POUR CLASS BONUS
        
        // Règle 1: Si Class Bonus est suivi d'un <br> puis d'une phrase normale, supprimer le <br>
        formatted = formatted.replace(
            /(<span class="effect-badge">Class Bonus<\/span>)\s*<br>\s*([a-z])/gi,
            '$1 $2'
        );

        // Règle 2: Si deux Class Bonus se suivent avec <br>, remplacer par un espace
        formatted = formatted.replace(
            /(<span class="effect-badge">Class Bonus<\/span>)\s*<br>\s*(<span class="effect-badge">Class Bonus<\/span>)/g,
            '$1 $2'
        );

        // Règle 3: Class Bonus en fin de phrase + Class Bonus = même ligne
        formatted = formatted.replace(
            /(Class Bonus<\/span>[^<]*?[.!?])\s*<br>\s*(<span class="effect-badge">Class Bonus<\/span>)/g,
            '$1 $2'
        );

        // Nettoyer les espaces multiples et <br> en trop
        formatted = formatted
            .replace(/\s*<br>\s*<br>\s*/g, '<br><br>') // Max 2 <br> consécutifs
            .replace(/\s*<br>\s*/g, '<br>') // Nettoyer les espaces autour des <br>
            .replace(/<br>+$/g, '') // Supprimer les <br> en fin
            .replace(/^<br>+/g, '') // Supprimer les <br> au début
            .trim();

        return formatted;
    }

    /**
     * Échappe les caractères HTML pour éviter les injections XSS
     * @param {string} text - Le texte à échapper
     * @returns {string} - Le texte échappé
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async updateCardQuantity(action) {
        if (!this.currentCardData) return;

        const quantityEl = document.getElementById('card-quantity');
        let currentQuantity = parseInt(quantityEl.textContent) || 0;
        let newQuantity = currentQuantity;

        if (action === 'increase') {
            newQuantity = currentQuantity + 1;
        } else if (action === 'decrease') {
            newQuantity = Math.max(0, currentQuantity - 1);
        }

        try {
            const isFoil = document.getElementById('card-foil').checked;
            // Détecter si c'est une carte CSR basée sur la rareté
            const isCsr = this.currentCardData.rarity === 7;
            
            const response = await this.api.updateQuantity(
                this.currentCardData.uuid,
                this.currentCardData.edition_uuid,
                isFoil,
                newQuantity,
                isCsr
            );

            if (response.success) {
                quantityEl.textContent = newQuantity;
                this.api.showNotification(
                    newQuantity > 0 ? 'Quantité mise à jour' : 'Carte retirée de la collection',
                    'success'
                );
                
                // Recharger la collection si nous sommes sur cette vue
                if (this.currentView === 'collection') {
                    this.loadMyCollection();
                }
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour:', error);
            this.api.showNotification('Erreur lors de la mise à jour', 'error');
        }
    }

    async toggleFoilStatus(isFoil) {
        if (!this.currentCardData) return;

        try {
            const quantity = parseInt(document.getElementById('card-quantity').textContent) || 0;
            
            if (quantity > 0) {
                // D'abord supprimer l'ancienne entrée (foil ou non-foil)
                const oldFoilStatus = this.currentCardData.owned_foil || false;
                
                if (oldFoilStatus !== isFoil) {
                    // Supprimer l'ancienne entrée
                    await this.api.updateQuantity(
                        this.currentCardData.uuid,
                        this.currentCardData.edition_uuid,
                        oldFoilStatus,
                        0 // Quantité 0 = suppression
                    );
                }
                
                // Créer/mettre à jour la nouvelle entrée
                const response = await this.api.updateQuantity(
                    this.currentCardData.uuid,
                    this.currentCardData.edition_uuid,
                    isFoil,
                    quantity
                );

                if (response.success) {
                    this.api.showNotification('Statut foil mis à jour', 'success');
                    
                    // Mettre à jour les données locales
                    this.currentCardData.owned_foil = isFoil;
                    
                    // Recharger la collection
                    if (this.currentView === 'collection') {
                        this.loadMyCollection();
                    }
                }
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du statut foil:', error);
            this.api.showNotification('Erreur lors de la mise à jour', 'error');
        }
    }

    async loadStats() {
        try {
            const response = await this.api.getStats();
            
            if (response.success) {
                this.displayStats(response.data);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
            this.api.showNotification('Erreur lors du chargement des statistiques', 'error');
        }
    }

    displayStats(stats) {
        // Statistiques générales
        if (stats.overall) {
            document.getElementById('total-cards').textContent = stats.overall.total_cards || 0;
            document.getElementById('unique-cards').textContent = stats.overall.unique_cards || 0;
            document.getElementById('foil-cards').textContent = stats.overall.foil_cards || 0;
            document.getElementById('sets-owned').textContent = stats.overall.sets_owned || 0;
        }

        // Graphiques
        this.createCharts(stats);
    }

    createCharts(stats) {
        // Graphique par extension
        if (stats.by_set && stats.by_set.length > 0) {
            this.createSetChart(stats.by_set);
        }

        // Graphique par classe
        if (stats.by_class && stats.by_class.length > 0) {
            this.createClassChart(stats.by_class);
        }

        // Graphique par rareté
        if (stats.by_rarity && stats.by_rarity.length > 0) {
            this.createRarityChart(stats.by_rarity);
        }

        // Graphique par élément
        if (stats.by_element && stats.by_element.length > 0) {
            this.createElementChart(stats.by_element);
        }

        // Graphique de progression
        if (stats.progress && stats.progress.length > 0) {
            this.createProgressChart(stats.progress);
        }

        // Graphique foil
        if (stats.foil_stats && stats.foil_stats.length > 0) {
            this.createFoilChart(stats.foil_stats);
        }
    }

    createSetChart(setData) {
        const ctx = document.getElementById('sets-chart');
        if (!ctx) return;

        if (this.charts.sets) {
            this.charts.sets.destroy();
        }

        this.charts.sets = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: setData.map(s => s.set_name),
                datasets: [{
                    data: setData.map(s => s.total_cards),
                    backgroundColor: [
                        '#6366f1', '#8b5cf6', '#06b6d4', '#10b981',
                        '#f59e0b', '#ef4444', '#ec4899', '#84cc16'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                }
            }
        });
    }

    createClassChart(classData) {
        const ctx = document.getElementById('classes-chart');
        if (!ctx) return;

        if (this.charts.classes) {
            this.charts.classes.destroy();
        }

        this.charts.classes = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: classData.map(c => c.class_name),
                datasets: [{
                    label: 'Cartes par classe',
                    data: classData.map(c => c.total_cards),
                    backgroundColor: '#6366f1',
                    borderColor: '#4f46e5',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#334155'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    createRarityChart(rarityData) {
        const ctx = document.getElementById('rarity-chart');
        if (!ctx) return;

        if (this.charts.rarity) {
            this.charts.rarity.destroy();
        }

        const rarityLabels = rarityData.map(r => `Rareté ${r.rarity}`);
        
        this.charts.rarity = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: rarityLabels,
                datasets: [{
                    data: rarityData.map(r => r.total_cards),
                    backgroundColor: [
                        '#22c55e', // Rareté 1 - Vert
                        '#3b82f6', // Rareté 2 - Bleu  
                        '#a855f7', // Rareté 3 - Violet
                        '#f59e0b', // Rareté 4 - Orange
                        '#ef4444'  // Rareté 5 - Rouge
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                }
            }
        });
    }

    createElementChart(elementData) {
        const ctx = document.getElementById('elements-chart');
        if (!ctx) return;

        if (this.charts.elements) {
            this.charts.elements.destroy();
        }

        const elementColors = {
            'Fire': '#ef4444',
            'Water': '#3b82f6',
            'Wind': '#10b981',
            'Earth': '#a3a3a3',
            'Light': '#fbbf24',
            'Shadow': '#6b21a8',
            'Arcane': '#ec4899',
            'Norm': '#64748b'
        };

        this.charts.elements = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: elementData.map(e => e.element),
                datasets: [{
                    data: elementData.map(e => e.total_cards),
                    backgroundColor: elementData.map(e => elementColors[e.element] || '#64748b')
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                }
            }
        });
    }

    createProgressChart(progressData) {
        const ctx = document.getElementById('progress-chart');
        if (!ctx) return;

        if (this.charts.progress) {
            this.charts.progress.destroy();
        }

        this.charts.progress = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: progressData.map(p => p.set_name),
                datasets: [{
                    label: '% Complété',
                    data: progressData.map(p => p.completion_percentage),
                    backgroundColor: progressData.map(p => {
                        const percentage = p.completion_percentage;
                        if (percentage >= 80) return '#22c55e'; // Vert
                        if (percentage >= 50) return '#f59e0b'; // Orange
                        return '#ef4444'; // Rouge
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#334155'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    createFoilChart(foilData) {
        const ctx = document.getElementById('foil-chart');
        if (!ctx) return;

        if (this.charts.foil) {
            this.charts.foil.destroy();
        }

        // Séparer les données par catégorie
        const extensionData = foilData.filter(f => f.category === 'Par Extension');
        const rarityData = foilData.filter(f => f.category === 'Par Rareté');

        // Utiliser les données d'extension ou de rareté selon ce qui est disponible
        const dataToUse = extensionData.length > 0 ? extensionData : rarityData;

        this.charts.foil = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dataToUse.map(f => f.label),
                datasets: [{
                    label: 'Cartes Foil',
                    data: dataToUse.map(f => f.total_foil_quantity),
                    backgroundColor: '#fbbf24',
                    borderColor: '#f59e0b',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8fafc'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#334155'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    showLoading(containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Chargement...</p>
            </div>
        `;
    }

    showError(containerId, message) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }

    async loadSyncView() {
        try {
            // Charger le nombre de cartes en base
            await this.updateCardsCount();
            
            // Charger le statut de synchronisation
            await this.updateSyncStatus();
            
            // Configurer les événements de synchronisation
            this.setupSyncEventListeners();
            
        } catch (error) {
            console.error('Erreur lors du chargement de la vue sync:', error);
        }
    }

    setupSyncEventListeners() {
        // Éviter de dupliquer les événements
        const startBtn = document.getElementById('start-sync-btn');
        const checkBtn = document.getElementById('check-status-btn');
        const testBtn = document.getElementById('test-sync-btn');
        
        if (startBtn && !startBtn.hasAttribute('data-events-attached')) {
            startBtn.addEventListener('click', () => this.startSync());
            startBtn.setAttribute('data-events-attached', 'true');
        }
        
        if (checkBtn && !checkBtn.hasAttribute('data-events-attached')) {
            checkBtn.addEventListener('click', () => this.updateSyncStatus());
            checkBtn.setAttribute('data-events-attached', 'true');
        }
        
        if (testBtn && !testBtn.hasAttribute('data-events-attached')) {
            testBtn.addEventListener('click', () => this.testSync());
            testBtn.setAttribute('data-events-attached', 'true');
        }
    }

    async updateCardsCount() {
        try {
            const response = await this.api.getCardsCount();
            if (response.success) {
                document.getElementById('cards-in-db').textContent = response.data.total;
            }
        } catch (error) {
            console.error('Erreur lors de la récupération du nombre de cartes:', error);
            document.getElementById('cards-in-db').textContent = 'Erreur';
        }
    }

    async updateSyncStatus() {
        try {
            const response = await this.api.getSyncStatus();
            if (response.success) {
                const status = response.data;
                const statusEl = document.getElementById('sync-status');
                statusEl.textContent = status.message || status.status;
                
                // Mettre à jour l'interface selon le statut
                if (status.status === 'running') {
                    this.showSyncProgress(true);
                    document.getElementById('progress-text').textContent = status.message;
                } else {
                    this.showSyncProgress(false);
                }
            }
        } catch (error) {
            console.error('Erreur lors de la récupération du statut:', error);
            document.getElementById('sync-status').textContent = 'Erreur';
        }
    }

    async startSync() {
        const startBtn = document.getElementById('start-sync-btn');
        const limitInput = document.getElementById('sync-limit');
        const offsetInput = document.getElementById('sync-offset');
        const syncAllMode = document.getElementById('sync-all-mode');
        
        // Désactiver le bouton pendant la synchronisation
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronisation...';
        
        try {
            let limitPages = limitInput.value ? parseInt(limitInput.value) : null;
            let startPage = offsetInput.value ? parseInt(offsetInput.value) : 1;
            
            // Convertir les pages en format attendu par l'API (offset basé sur les cartes)
            let limit = limitPages ? limitPages * 30 : null; // 30 cartes par page
            let offset = (startPage - 1) * 30; // Décalage en cartes
            
            // Si mode synchronisation complète, ignorer la limite
            if (syncAllMode.checked) {
                limit = null;
                offset = 0;
                this.addLogEntry('info', 'Mode synchronisation complète activé - récupération de TOUTES les cartes sur 48 pages (~1440 cartes)...');
            } else if (limitPages) {
                this.addLogEntry('info', `Synchronisation par lot: ${limitPages} pages à partir de la page ${startPage}...`);
            } else {
                this.addLogEntry('info', `Synchronisation complète à partir de la page ${startPage}...`);
            }
            
            this.showSyncProgress(true);
            
            const response = await this.api.syncAllCards(limit, offset);
            
            if (response.success) {
                const result = response.data;
                
                // Afficher le type de synchronisation
                if (result.mode === 'test_data') {
                    this.addLogEntry('warning', 'API externe non accessible - données de test utilisées');
                }
                
                this.addLogEntry('success', 
                    `Synchronisation terminée: ${result.imported} importées, ${result.skipped} ignorées, ${result.errors} erreurs`
                );
                
                if (result.pages_processed && result.total_pages) {
                    this.addLogEntry('info', 
                        `Pages traitées: ${result.pages_processed}/${result.total_pages} (${result.total_available || result.total_processed} cartes au total)`
                    );
                }
                
                // Mettre à jour le compteur de cartes
                await this.updateCardsCount();
                
                // Mettre à jour l'heure de dernière sync
                document.getElementById('last-sync-time').textContent = new Date().toLocaleTimeString();
                
                // Afficher un résumé des dernières cartes importées
                if (result.log && result.log.length > 0) {
                    const importedCards = result.log.filter(entry => entry.action === 'imported');
                    const errorCards = result.log.filter(entry => entry.action === 'error');
                    
                    if (importedCards.length > 0) {
                        this.addLogEntry('info', `Dernières cartes importées: ${importedCards.slice(-5).map(c => c.card_name).join(', ')}`);
                    }
                    
                    if (errorCards.length > 0) {
                        this.addLogEntry('warning', `${errorCards.length} erreurs rencontrées`);
                        errorCards.slice(0, 3).forEach(error => {
                            this.addLogEntry('error', `Erreur: ${error.card_name} - ${error.error}`);
                        });
                    }
                }
                
                // Suggérer la suite si on était en mode par lot
                if (!syncAllMode.checked && limitPages && result.imported > 0 && result.pages_processed) {
                    const nextPage = startPage + result.pages_processed;
                    if (nextPage <= (result.total_pages || 48)) {
                        this.addLogEntry('info', `Pour continuer, définissez la page de départ à ${nextPage} et relancez`);
                        document.getElementById('sync-offset').value = nextPage;
                    } else {
                        this.addLogEntry('success', 'Synchronisation complète terminée - toutes les pages ont été traitées!');
                    }
                }
                
            } else {
                let errorMessage = `Erreur de synchronisation: ${response.error}`;
                
                // Affichage d'un message plus clair pour les erreurs de base de données
                if (response.type === 'database_connection_error') {
                    errorMessage += '\n💡 Solution: Vérifiez que MySQL est démarré et que la base de données existe.';
                    errorMessage += '\n🔧 Vous pouvez exécuter init.php ou setup.php pour créer la base de données.';
                }
                
                this.addLogEntry('error', errorMessage);
            }
        } catch (error) {
            console.error('Erreur de synchronisation:', error);
            this.addLogEntry('error', `Erreur: ${error.message}`);
        } finally {
            // Réactiver le bouton
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="fas fa-sync"></i> Démarrer la synchronisation';
            this.showSyncProgress(false);
            await this.updateSyncStatus();
        }
    }

    showSyncProgress(show) {
        const progressEl = document.getElementById('sync-progress');
        if (show) {
            progressEl.style.display = 'block';
        } else {
            progressEl.style.display = 'none';
        }
    }

    addLogEntry(type, message) {
        const logContent = document.getElementById('log-content');
        const timestamp = new Date().toLocaleTimeString();
        
        const entry = document.createElement('p');
        entry.className = `log-entry log-${type}`;
        entry.innerHTML = `<span class="log-time">[${timestamp}]</span> ${message}`;
        
        // Ajouter en haut du log
        logContent.insertBefore(entry, logContent.firstChild);
        
        // Limiter à 50 entrées
        while (logContent.children.length > 50) {
            logContent.removeChild(logContent.lastChild);
        }
    }

    async testSync() {
        const testBtn = document.getElementById('test-sync-btn');
        
        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test...';
        
        try {
            this.addLogEntry('info', 'Démarrage du test de synchronisation...');
            
            const response = await this.api.request('/cards.php?action=test_sync');
            
            if (response.success) {
                this.addLogEntry('success', `Test réussi: ${response.data.message}`);
                this.addLogEntry('info', `Carte de test créée: ${response.data.test_card}`);
                
                // Mettre à jour le compteur de cartes
                await this.updateCardsCount();
            } else {
                this.addLogEntry('error', `Test échoué: ${response.error}`);
            }
        } catch (error) {
            console.error('Erreur de test:', error);
            this.addLogEntry('error', `Erreur de test: ${error.message}`);
        } finally {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fas fa-vial"></i> Test de synchronisation';
        }
    }
}

// Initialiser le gestionnaire de collection
document.addEventListener('DOMContentLoaded', () => {
    // Attendre que l'API soit disponible
    if (typeof window.api === 'undefined') {
        // Si l'API n'est pas encore chargée, attendre un peu
        setTimeout(() => {
            window.collectionManager = new CollectionManager();
        }, 100);
    } else {
        window.collectionManager = new CollectionManager();
    }
});