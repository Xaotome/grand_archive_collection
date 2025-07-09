// ===== LIQUID GLASS ANIMATIONS SYSTEM =====

class LiquidGlassAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.initializeParticles();
        this.addViewTransitions();
        this.addInteractiveEffects();
        this.initializeScrollAnimations();
    }

    // Initialisation des particules flottantes
    initializeParticles() {
        const particlesContainer = document.querySelector('.particles-container');
        if (!particlesContainer) return;

        // Générer des particules avec des positions aléatoires
        const particles = particlesContainer.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            const delay = Math.random() * 8;
            const duration = 8 + Math.random() * 4;
            const startX = Math.random() * 100;
            
            particle.style.left = `${startX}%`;
            particle.style.animationDelay = `${delay}s`;
            particle.style.animationDuration = `${duration}s`;
        });
    }

    // Ajouter des transitions entre les vues
    addViewTransitions() {
        const navButtons = document.querySelectorAll('.nav-btn');
        const views = document.querySelectorAll('.view');

        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetView = button.dataset.view;
                this.animateViewTransition(targetView);
            });
        });
    }

    animateViewTransition(targetView) {
        const currentView = document.querySelector('.view.active');
        const newView = document.getElementById(`${targetView}-view`);

        if (currentView && newView && currentView !== newView) {
            // Animation de sortie
            currentView.classList.add('animate-fade-out');
            
            setTimeout(() => {
                currentView.classList.remove('active', 'animate-fade-out');
                newView.classList.add('active', 'animate-fade-in');
                
                // Animer les éléments de la nouvelle vue
                this.animateViewElements(newView);
            }, 300);
        }
    }

    animateViewElements(view) {
        const elements = view.querySelectorAll('.glass-container, .glass-card, .glass-button');
        elements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
            element.classList.add('animate-slide-up');
        });
    }

    // Ajouter des effets interactifs
    addInteractiveEffects() {
        // Effet de ripple sur les boutons
        document.querySelectorAll('.glass-button').forEach(button => {
            button.addEventListener('click', (e) => {
                this.createRippleEffect(e.target, e);
            });
        });

        // Effet de glow sur les cartes
        document.querySelectorAll('.glass-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('interactive-glow');
            });
            
            card.addEventListener('mouseleave', () => {
                card.classList.remove('interactive-glow');
            });
        });
    }

    createRippleEffect(element, event) {
        const rect = element.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        const ripple = document.createElement('div');
        ripple.style.position = 'absolute';
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        ripple.style.width = '0';
        ripple.style.height = '0';
        ripple.style.borderRadius = '50%';
        ripple.style.background = 'rgba(255, 255, 255, 0.3)';
        ripple.style.transform = 'translate(-50%, -50%)';
        ripple.style.animation = 'ripple 0.6s ease-out';
        ripple.style.pointerEvents = 'none';
        ripple.style.zIndex = '1000';

        element.style.position = 'relative';
        element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // Animations au scroll
    initializeScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    element.classList.add('animate-fade-in');
                    observer.unobserve(element);
                }
            });
        }, {
            threshold: 0.1
        });

        // Observer les éléments qui doivent s'animer au scroll
        document.querySelectorAll('.glass-card, .stat-card, .chart-card').forEach(element => {
            observer.observe(element);
        });
    }

    // Méthode pour animer l'apparition des cartes de collection
    animateCardsAppearance(cardElements) {
        cardElements.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-scale-in');
        });
    }

    // Méthode pour animer le changement de vue des cartes
    animateViewChange(newViewType) {
        const cardsContainer = document.querySelector('.cards-grid');
        if (!cardsContainer) return;

        cardsContainer.style.opacity = '0';
        cardsContainer.style.transform = 'translateY(20px)';

        setTimeout(() => {
            cardsContainer.style.transition = 'all 0.4s ease';
            cardsContainer.style.opacity = '1';
            cardsContainer.style.transform = 'translateY(0)';
        }, 100);
    }

    // Méthode pour animer l'ouverture/fermeture des modals
    animateModalOpen(modal) {
        const modalContent = modal.querySelector('.modal-content');
        
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        modalContent.style.transform = 'scale(0.7)';

        setTimeout(() => {
            modal.style.transition = 'opacity 0.3s ease';
            modalContent.style.transition = 'transform 0.3s ease';
            modal.style.opacity = '1';
            modalContent.style.transform = 'scale(1)';
        }, 10);
    }

    animateModalClose(modal) {
        const modalContent = modal.querySelector('.modal-content');
        
        modal.style.opacity = '0';
        modalContent.style.transform = 'scale(0.7)';

        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.transition = '';
            modalContent.style.transition = '';
        }, 300);
    }
}

// Ajouter les styles d'animation pour les ripples
const rippleStyles = `
    @keyframes ripple {
        0% {
            width: 0;
            height: 0;
            opacity: 1;
        }
        100% {
            width: 200px;
            height: 200px;
            opacity: 0;
        }
    }
    
    .animate-fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;

// Ajouter les styles au document
const styleSheet = document.createElement('style');
styleSheet.textContent = rippleStyles;
document.head.appendChild(styleSheet);

// Initialiser le système d'animations
document.addEventListener('DOMContentLoaded', () => {
    window.liquidGlass = new LiquidGlassAnimations();
});