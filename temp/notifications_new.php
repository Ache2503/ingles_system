<?php
session_start();

// Configuración de base de datos
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    exit;
}

// Sistema de notificaciones simplificado
class NotificationSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getNotifications($userId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function markAsRead($userId, $notificationId = null) {
        try {
            if ($notificationId) {
                $stmt = $this->pdo->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE user_id = ? AND notification_id = ?
                ");
                return $stmt->execute([$userId, $notificationId]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE user_id = ?
                ");
                return $stmt->execute([$userId]);
            }
        } catch (Exception $e) {
            return false;
        }
    }
}

// API para manejar notificaciones vía AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $notification = new NotificationSystem($pdo);
    $userId = $_SESSION['user_id'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'get_unread':
                $count = $notification->getUnreadCount($userId);
                echo json_encode([
                    'success' => true,
                    'count' => $count
                ]);
                break;
                
            case 'get_notifications':
                $notifications = $notification->getNotifications($userId);
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;
                
            case 'mark_read':
                $notificationId = $_POST['notification_id'] ?? null;
                $result = $notification->markAsRead($userId, $notificationId);
                echo json_encode([
                    'success' => $result
                ]);
                break;
                
            case 'mark_all_read':
                $result = $notification->markAsRead($userId);
                echo json_encode([
                    'success' => $result
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Acción no válida'
                ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Si no es una petición AJAX, mostrar la página de notificaciones
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$notification = new NotificationSystem($pdo);
$userId = $_SESSION['user_id'];
$notifications = $notification->getNotifications($userId, 50);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Sistema de Inglés</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .notification { padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; background: #f8f9fa; }
        .notification.unread { background: #e3f2fd; }
        .notification h4 { margin: 0 0 5px 0; }
        .notification p { margin: 5px 0; }
        .notification small { color: #666; }
        .btn { padding: 8px 16px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📬 Notificaciones</h1>
    
    <div>
        <button class="btn" onclick="markAllAsRead()">Marcar todas como leídas</button>
        <button class="btn" onclick="location.href='index.php'">Volver al inicio</button>
    </div>
    
    <div id="notifications-container">
        <?php if (empty($notifications)): ?>
            <p>No tienes notificaciones.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" 
                     data-id="<?php echo $notif['notification_id']; ?>">
                    <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p><?php echo htmlspecialchars($notif['message']); ?></p>
                    <small><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                    <?php if (!$notif['is_read']): ?>
                        <button class="btn" onclick="markAsRead(<?php echo $notif['notification_id']; ?>)">
                            Marcar como leída
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
    async function markAsRead(notificationId) {
        try {
            const response = await fetch('notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=mark_read&notification_id=${notificationId}`
            });
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    async function markAllAsRead() {
        try {
            const response = await fetch('notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all_read'
            });
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    </script>
</body>
</html>
