// Función para añadir botones de favoritos dinámicamente
function addBookmarkButtons() {
    // Buscar elementos que pueden ser añadidos a favoritos
    const topicCards = document.querySelectorAll('[data-topic-id]');
    const verbCards = document.querySelectorAll('[data-verb-id]');
    const questionCards = document.querySelectorAll('[data-question-id]');
    
    // Añadir botones a temas
    topicCards.forEach(card => {
        const topicId = card.dataset.topicId;
        addBookmarkButton(card, 'topic', topicId);
    });
    
    // Añadir botones a verbos
    verbCards.forEach(card => {
        const verbId = card.dataset.verbId;
        addBookmarkButton(card, 'verb', verbId);
    });
    
    // Añadir botones a preguntas
    questionCards.forEach(card => {
        const questionId = card.dataset.questionId;
        addBookmarkButton(card, 'question', questionId);
    });
}

function addBookmarkButton(element, contentType, contentId) {
    // Verificar si ya existe un botón de favorito
    if (element.querySelector('.bookmark-btn')) {
        return;
    }
    
    // Crear botón de favorito
    const bookmarkBtn = document.createElement('button');
    bookmarkBtn.className = 'bookmark-btn';
    bookmarkBtn.innerHTML = '⭐';
    bookmarkBtn.title = 'Añadir a favoritos';
    bookmarkBtn.dataset.contentType = contentType;
    bookmarkBtn.dataset.contentId = contentId;
    
    // Estilos del botón
    Object.assign(bookmarkBtn.style, {
        position: 'absolute',
        top: '10px',
        right: '10px',
        background: 'rgba(255, 255, 255, 0.9)',
        border: 'none',
        borderRadius: '50%',
        width: '35px',
        height: '35px',
        fontSize: '16px',
        cursor: 'pointer',
        transition: 'all 0.3s ease',
        zIndex: '10',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
    });
    
    // Asegurar que el elemento padre tenga posición relativa
    if (getComputedStyle(element).position === 'static') {
        element.style.position = 'relative';
    }
    
    // Evento click
    bookmarkBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        e.preventDefault();
        
        const isBookmarked = bookmarkBtn.classList.contains('bookmarked');
        
        if (isBookmarked) {
            // Remover de favoritos (requiere ID del bookmark)
            showNotification('Función de eliminar disponible en la página de favoritos', 'info');
        } else {
            // Añadir a favoritos
            const success = await addBookmark(contentType, contentId);
            if (success) {
                bookmarkBtn.classList.add('bookmarked');
                bookmarkBtn.innerHTML = '⭐';
                bookmarkBtn.style.background = 'rgba(255, 193, 7, 0.9)';
                bookmarkBtn.style.color = 'white';
                bookmarkBtn.title = 'En favoritos';
                
                // Efecto de animación
                bookmarkBtn.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    bookmarkBtn.style.transform = 'scale(1)';
                }, 200);
            }
        }
    });
    
    // Hover effects
    bookmarkBtn.addEventListener('mouseenter', () => {
        if (!bookmarkBtn.classList.contains('bookmarked')) {
            bookmarkBtn.style.background = 'rgba(255, 193, 7, 0.9)';
            bookmarkBtn.style.transform = 'scale(1.1)';
        }
    });
    
    bookmarkBtn.addEventListener('mouseleave', () => {
        if (!bookmarkBtn.classList.contains('bookmarked')) {
            bookmarkBtn.style.background = 'rgba(255, 255, 255, 0.9)';
            bookmarkBtn.style.transform = 'scale(1)';
        }
    });
    
    // Verificar si ya está en favoritos
    checkIfBookmarked(bookmarkBtn, contentType, contentId);
    
    element.appendChild(bookmarkBtn);
}

async function checkIfBookmarked(button, contentType, contentId) {
    try {
        const response = await fetch('bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=check_bookmark&content_type=${contentType}&content_id=${contentId}`
        });
        
        const result = await response.json();
        
        if (result.bookmarked) {
            button.classList.add('bookmarked');
            button.innerHTML = '⭐';
            button.style.background = 'rgba(255, 193, 7, 0.9)';
            button.style.color = 'white';
            button.title = 'En favoritos';
        }
    } catch (error) {
        console.log('No se pudo verificar el estado de favorito');
    }
}

async function addBookmark(contentType, contentId, notes = '') {
    try {
        const response = await fetch('bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_bookmark&content_type=${contentType}&content_id=${contentId}&notes=${encodeURIComponent(notes)}`
        });
        
        const result = await response.json();
        showNotification(result.message, result.success ? 'success' : 'error');
        return result.success;
    } catch (error) {
        showNotification('Error de conexión', 'error');
        return false;
    }
}

function showNotification(message, type = 'info') {
    // Verificar si ya existe una función showNotification global
    if (window.showNotification && typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    
    // Crear notificación simple si no existe la función global
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    
    // Colores según el tipo
    switch(type) {
        case 'success':
            notification.style.background = '#28a745';
            break;
        case 'error':
            notification.style.background = '#dc3545';
            break;
        case 'warning':
            notification.style.background = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.background = '#17a2b8';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', () => {
    // Solo añadir botones si el usuario está logueado
    if (document.body.dataset.userId) {
        addBookmarkButtons();
        
        // Re-ejecutar si se añade contenido dinámicamente
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    addBookmarkButtons();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});

// Estilos CSS para los botones de favoritos
const bookmarkStyles = document.createElement('style');
bookmarkStyles.textContent = `
    .bookmark-btn {
        transition: all 0.3s ease !important;
    }
    
    .bookmark-btn:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
    }
    
    .bookmark-btn.bookmarked {
        background: rgba(255, 193, 7, 0.9) !important;
        color: white !important;
    }
    
    .bookmark-btn.bookmarked:hover {
        background: rgba(255, 193, 7, 1) !important;
    }
    
    /* Asegurar que los contenedores de cartas tengan posición relativa */
    .topic-card, .verb-card, .question-card, .card, .item {
        position: relative !important;
    }
`;

document.head.appendChild(bookmarkStyles);
