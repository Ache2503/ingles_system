<?php
/**
 * Sistema de Envío de Notificaciones
 * Permite enviar notificaciones masivas y individuales a usuarios
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar permisos de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/auth/login.php');
    exit;
}

$message = '';
$error = '';

// Procesar envío de notificación
if ($_POST['action'] ?? '' === 'send_notification') {
    try {
        $type = $_POST['notification_type'] ?? 'individual';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';
        $recipients = $_POST['recipients'] ?? [];
        
        if (empty($title) || empty($content)) {
            throw new Exception("El título y contenido son obligatorios");
        }
        
        $sentCount = 0;
        
        if ($type === 'all_users') {
            // Enviar a todos los usuarios
            $stmt = $pdo->query("SELECT user_id FROM users WHERE role != 'admin'");
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($type === 'active_users') {
            // Enviar a usuarios activos (últimos 7 días)
            $stmt = $pdo->query("
                SELECT DISTINCT u.user_id 
                FROM users u 
                JOIN user_progress up ON u.user_id = up.user_id 
                WHERE u.role != 'admin' 
                AND up.last_reviewed >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Enviar notificaciones
        foreach ($recipients as $userId) {
            if (sendNotification($userId, $title, $content, $priority)) {
                $sentCount++;
            }
        }
        
        $message = "✅ Notificación enviada exitosamente a {$sentCount} usuario(s)";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Procesar plantilla predefinida
if ($_POST['action'] ?? '' === 'send_template') {
    try {
        $templateId = $_POST['template_id'] ?? '';
        $templates = getNotificationTemplates();
        
        if (!isset($templates[$templateId])) {
            throw new Exception("Plantilla no encontrada");
        }
        
        $template = $templates[$templateId];
        $recipients = [];
        
        // Determinar destinatarios según la plantilla
        switch ($templateId) {
            case 'welcome':
                // Usuarios registrados en los últimos 3 días
                $stmt = $pdo->query("
                    SELECT user_id FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                    AND role != 'admin'
                ");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'inactive':
                // Usuarios inactivos (más de 7 días sin actividad)
                $stmt = $pdo->query("
                    SELECT u.user_id FROM users u
                    LEFT JOIN user_progress up ON u.user_id = up.user_id
                    WHERE u.role != 'admin'
                    AND (up.last_reviewed IS NULL OR up.last_reviewed < DATE_SUB(NOW(), INTERVAL 7 DAY))
                ");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'achievement':
                // Usuarios con logros recientes
                $stmt = $pdo->query("
                    SELECT DISTINCT user_id FROM user_achievements 
                    WHERE earned_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                ");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
        }
        
        $sentCount = 0;
        foreach ($recipients as $userId) {
            if (sendNotification($userId, $template['title'], $template['content'], $template['priority'])) {
                $sentCount++;
            }
        }
        
        $message = "✅ Plantilla '{$template['title']}' enviada a {$sentCount} usuario(s)";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Obtener estadísticas de notificaciones
$notificationStats = [
    'total_sent' => 0,
    'total_read' => 0,
    'today_sent' => 0,
    'pending_notifications' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
    $notificationStats['total_sent'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 1");
    $notificationStats['total_read'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE DATE(created_at) = CURDATE()");
    $notificationStats['today_sent'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $notificationStats['pending_notifications'] = $stmt->fetchColumn();
} catch (Exception $e) {
    // Crear tabla si no existe
    createNotificationsTable();
}

// Obtener usuarios para selector
$users = $pdo->query("
    SELECT user_id, username, email, created_at 
    FROM users 
    WHERE role != 'admin' 
    ORDER BY username
")->fetchAll(PDO::FETCH_ASSOC);

// Obtener notificaciones recientes
$recentNotifications = $pdo->query("
    SELECT n.*, u.username 
    FROM notifications n
    JOIN users u ON n.user_id = u.user_id
    ORDER BY n.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Funciones auxiliares
function sendNotification($userId, $title, $content, $priority = 'normal') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, content, priority, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$userId, $title, $content, $priority]);
    } catch (Exception $e) {
        return false;
    }
}

function createNotificationsTable() {
    global $pdo;
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            INDEX idx_user_created (user_id, created_at),
            INDEX idx_read_status (is_read),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
}

function getNotificationTemplates() {
    return [
        'welcome' => [
            'title' => '¡Bienvenido al Sistema de Inglés!',
            'content' => 'Gracias por registrarte. Comienza tu aprendizaje explorando nuestros temas de gramática y vocabulario. ¡Buena suerte!',
            'priority' => 'normal'
        ],
        'inactive' => [
            'title' => '¡Te extrañamos! Continúa tu aprendizaje',
            'content' => 'Hemos notado que no has practicado últimamente. ¡Vuelve y continúa mejorando tu inglés! Hay nuevos temas esperándote.',
            'priority' => 'normal'
        ],
        'achievement' => [
            'title' => '🏆 ¡Felicitaciones por tu logro!',
            'content' => 'Has desbloqueado un nuevo logro. Sigue así y continúa progresando en tu aprendizaje del inglés.',
            'priority' => 'high'
        ],
        'maintenance' => [
            'title' => '🔧 Mantenimiento Programado',
            'content' => 'El sistema estará en mantenimiento mañana de 2:00 AM a 4:00 AM. Disculpa las molestias.',
            'priority' => 'urgent'
        ],
        'new_content' => [
            'title' => '📚 Nuevo Contenido Disponible',
            'content' => 'Hemos agregado nuevos temas y ejercicios. ¡Explora el contenido nuevo y sigue aprendiendo!',
            'priority' => 'normal'
        ]
    ];
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
.notifications-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.notification-form {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.recipients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 1rem;
    border-radius: 5px;
}

.recipient-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 3px;
}

.recipient-item input {
    margin-right: 0.5rem;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.template-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}

.template-card h4 {
    margin: 0 0 1rem 0;
    color: #333;
}

.template-card p {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.notifications-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.notifications-table table {
    width: 100%;
    border-collapse: collapse;
}

.notifications-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

.notifications-table td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    margin: 0.25rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-info { background: #17a2b8; color: white; }
.btn-warning { background: #ffc107; color: #212529; }

.btn:hover { opacity: 0.9; transform: translateY(-1px); }

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}

.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-low { background: #d1ecf1; color: #0c5460; }
.badge-normal { background: #d4edda; color: #155724; }
.badge-high { background: #fff3cd; color: #856404; }
.badge-urgent { background: #f8d7da; color: #721c24; }

.tabs {
    display: flex;
    border-bottom: 2px solid #eee;
    margin-bottom: 2rem;
}

.tab {
    padding: 1rem 2rem;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab.active {
    border-bottom-color: #007bff;
    color: #007bff;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>

<div class="notifications-container">
    <h1>📧 Sistema de Notificaciones</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $notificationStats['total_sent'] ?></div>
            <div>Total Enviadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $notificationStats['total_read'] ?></div>
            <div>Total Leídas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $notificationStats['today_sent'] ?></div>
            <div>Enviadas Hoy</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $notificationStats['pending_notifications'] ?></div>
            <div>Pendientes</div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active" onclick="showTab('custom')">✍️ Notificación Personalizada</div>
        <div class="tab" onclick="showTab('templates')">📋 Plantillas</div>
        <div class="tab" onclick="showTab('history')">📜 Historial</div>
    </div>
    
    <!-- Notificación Personalizada -->
    <div id="custom" class="tab-content active">
        <div class="notification-form">
            <h2>✍️ Crear Notificación Personalizada</h2>
            <form method="POST">
                <input type="hidden" name="action" value="send_notification">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Título:</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Prioridad:</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low">Baja</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="content">Contenido:</label>
                    <textarea id="content" name="content" class="form-control" required 
                              placeholder="Escribe el contenido de la notificación..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Destinatarios:</label>
                    <div style="margin-bottom: 1rem;">
                        <label>
                            <input type="radio" name="notification_type" value="all_users" checked>
                            Todos los usuarios
                        </label>
                        <label style="margin-left: 2rem;">
                            <input type="radio" name="notification_type" value="active_users">
                            Solo usuarios activos (últimos 7 días)
                        </label>
                        <label style="margin-left: 2rem;">
                            <input type="radio" name="notification_type" value="individual">
                            Usuarios específicos
                        </label>
                    </div>
                    
                    <div id="individual-recipients" class="recipients-grid" style="display: none;">
                        <?php foreach ($users as $user): ?>
                        <div class="recipient-item">
                            <input type="checkbox" name="recipients[]" value="<?= $user['user_id'] ?>">
                            <span><?= htmlspecialchars($user['username']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">📧 Enviar Notificación</button>
            </form>
        </div>
    </div>
    
    <!-- Plantillas -->
    <div id="templates" class="tab-content">
        <h2>📋 Plantillas Predefinidas</h2>
        <div class="templates-grid">
            <?php foreach (getNotificationTemplates() as $templateId => $template): ?>
            <div class="template-card">
                <h4><?= htmlspecialchars($template['title']) ?></h4>
                <p><?= htmlspecialchars($template['content']) ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge badge-<?= $template['priority'] ?>">
                        <?= ucfirst($template['priority']) ?>
                    </span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="send_template">
                        <input type="hidden" name="template_id" value="<?= $templateId ?>">
                        <button type="submit" class="btn btn-primary btn-sm">📧 Enviar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Historial -->
    <div id="history" class="tab-content">
        <div class="notifications-table">
            <h2 style="padding: 1rem;">📜 Notificaciones Recientes</h2>
            
            <?php if (empty($recentNotifications)): ?>
            <div style="padding: 2rem; text-align: center; color: #666;">
                No hay notificaciones recientes.
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Título</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentNotifications as $notification): ?>
                    <tr>
                        <td><?= htmlspecialchars($notification['username']) ?></td>
                        <td><?= htmlspecialchars($notification['title']) ?></td>
                        <td>
                            <span class="badge badge-<?= $notification['priority'] ?>">
                                <?= ucfirst($notification['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $notification['is_read'] ? '✅ Leída' : '📧 Pendiente' ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Enlaces de navegación -->
    <div style="margin-top: 2rem; text-align: center;">
        <a href="analytics.php" class="btn btn-info">📊 Volver a Analytics</a>
        <a href="index.php" class="btn btn-primary">🏠 Panel de Admin</a>
    </div>
</div>

<script>
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remover clase active de todos los tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostrar el contenido seleccionado
    document.getElementById(tabName).classList.add('active');
    
    // Activar el tab seleccionado
    event.target.classList.add('active');
}

// Mostrar/ocultar selector de usuarios individuales
document.querySelectorAll('input[name="notification_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const individualDiv = document.getElementById('individual-recipients');
        if (this.value === 'individual') {
            individualDiv.style.display = 'grid';
        } else {
            individualDiv.style.display = 'none';
        }
    });
});

// Validación del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const type = document.querySelector('input[name="notification_type"]:checked').value;
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    if (!title || !content) {
        e.preventDefault();
        alert('Por favor completa el título y contenido de la notificación.');
        return;
    }
    
    if (type === 'individual') {
        const selectedUsers = document.querySelectorAll('input[name="recipients[]"]:checked');
        if (selectedUsers.length === 0) {
            e.preventDefault();
            alert('Por favor selecciona al menos un usuario para enviar la notificación.');
            return;
        }
    }
    
    // Confirmar envío
    const recipientText = type === 'all_users' ? 'todos los usuarios' : 
                         type === 'active_users' ? 'usuarios activos' : 
                         `${document.querySelectorAll('input[name="recipients[]"]:checked').length} usuario(s) seleccionado(s)`;
    
    if (!confirm(`¿Estás seguro de enviar esta notificación a ${recipientText}?`)) {
        e.preventDefault();
    }
});

// Auto-refresh cada 60 segundos para el historial
setInterval(() => {
    if (document.getElementById('history').classList.contains('active')) {
        window.location.reload();
    }
}, 60000);
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
