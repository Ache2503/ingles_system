// Sistema de notificaciones en tiempo real
class NotificationManager {
    constructor() {
        this.updateInterval = 30000; // 30 segundos
        this.badge = document.getElementById('notification-badge');
        this.link = document.getElementById('notifications-link');
        this.init();
    }
    
    init() {
        if (this.badge && this.link) {
            this.updateNotifications();
            setInterval(() => this.updateNotifications(), this.updateInterval);
        }
    }
    
    async updateNotifications() {
        try {
            const response = await fetch('/ingles/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_unread'
            });
            
            if (response.ok) {
                const text = await response.text();
                
                // Verificar si la respuesta es JSON válido
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        this.updateBadge(data.count);
                    } else {
                        console.log('API error:', data.message);
                    }
                } catch (jsonError) {
                    console.log('Response is not valid JSON:', text.substring(0, 200));
                    console.log('JSON parse error:', jsonError);
                }
            } else {
                console.log('HTTP error:', response.status, response.statusText);
            }
        } catch (error) {
            console.log('Network error updating notifications:', error);
        }
    }
    
    updateBadge(count) {
        if (count > 0) {
            this.badge.textContent = count > 99 ? '99+' : count;
            this.badge.style.display = 'inline';
            this.link.title = `Tienes ${count} notificación${count > 1 ? 'es' : ''} sin leer`;
        } else {
            this.badge.style.display = 'none';
            this.link.title = 'Notificaciones';
        }
    }
}

// Sistema de gamificación
class GamificationManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.checkAchievements();
    }
    
    async checkAchievements() {
        try {
            const response = await fetch('/ingles/api/check_achievements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (response.ok) {
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    if (data.success && data.new_achievements && data.new_achievements.length > 0) {
                        this.showAchievementNotification(data.new_achievements);
                    }
                } catch (jsonError) {
                    console.log('Achievement API response not valid JSON:', text.substring(0, 200));
                    console.log('JSON parse error:', jsonError);
                }
            } else {
                console.log('Achievement API HTTP error:', response.status);
            }
        } catch (error) {
            console.log('Network error checking achievements:', error);
        }
    }
    
    showAchievementNotification(achievements) {
        achievements.forEach(achievement => {
            this.showToast(
                `🏆 ¡Nuevo Logro!`,
                `Has obtenido: ${achievement.achievement_name}`,
                'achievement'
            );
        });
    }
    
    showToast(title, message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-header">
                <strong>${title}</strong>
                <button type="button" class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            minWidth: '300px',
            backgroundColor: type === 'achievement' ? '#28a745' : '#007bff',
            color: 'white',
            padding: '1rem',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
            zIndex: '9999',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }
}

// Función global para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.textContent = message;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '1rem 1.5rem',
        borderRadius: '8px',
        color: 'white',
        fontWeight: '500',
        zIndex: '1000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        maxWidth: '300px'
    });
    
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Función global para actualizar progreso del usuario
async function updateUserProgress(activityType, activityData = {}) {
    try {
        const response = await fetch('/ingles/api/update_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: activityType,
                data: activityData
            })
        });
        
        if (response.ok) {
            const text = await response.text();
            
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    // Verificar nuevos logros
                    if (window.gamificationManager) {
                        window.gamificationManager.checkAchievements();
                    }
                    
                    // Actualizar notificaciones
                    if (window.notificationManager) {
                        window.notificationManager.updateNotifications();
                    }
                } else {
                    console.log('Progress update failed:', result.message);
                }
            } catch (jsonError) {
                console.log('Progress API response not valid JSON:', text.substring(0, 200));
                console.log('JSON parse error:', jsonError);
            }
        } else {
            console.log('Progress API HTTP error:', response.status);
        }
    } catch (error) {
        console.log('Network error updating progress:', error);
    }
}

// Hacer funciones globales
window.showNotification = showNotification;
window.updateUserProgress = updateUserProgress;

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.dataset.userId) {
        window.notificationManager = new NotificationManager();
        window.gamificationManager = new GamificationManager();
        
        // Registrar login diario
        updateUserProgress('daily_login');
    }
});
