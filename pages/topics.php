<?php 
/**
 * Página de Temas - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener información del usuario
$userInfo = getCurrentUserInfo();

// Validar sesión (no expirada)
validateSession();

// Log de actividad
logUserActivity('view_topics', 'Usuario accedió a la página de temas');

// Obtener temas de la base de datos
$stmt = $pdo->query("SELECT * FROM topics");
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener progreso del usuario - CORRECCIÓN AQUÍ
$progressStmt = $pdo->prepare("SELECT topic_id, score FROM user_progress WHERE user_id = ?");
$progressStmt->execute([$_SESSION['user_id']]);
$userProgress = $progressStmt->fetchAll(PDO::FETCH_KEY_PAIR); // Ahora sí funciona con 2 columnas
?>

<div class="container">
    <h1>Temas de Repaso</h1>
    
    <div class="topics-grid">
        <?php foreach ($topics as $topic): ?>
            <?php 
            $progress = isset($userProgress[$topic['topic_id']]) ? $userProgress[$topic['topic_id']] : 0;
            $progressClass = getProgressClass($progress);
            ?>

            <div class="topic-card <?php echo $progressClass; ?>">
    <h3><?php echo htmlspecialchars($topic['title']); ?></h3>
    <p><?php echo htmlspecialchars($topic['description']); ?></p>
    
    <div class="progress-bar">
        <div class="progress" style="width: <?php echo $progress; ?>%"></div>
        <span><?php echo $progress; ?>%</span>
    </div>
    
    <div class="topic-links">
        <a href="topic_detail.php?topic_id=<?php echo $topic['topic_id']; ?>" class="btn btn-small">
            Explicación
        </a>
        <a href="practice.php?topic_id=<?php echo $topic['topic_id']; ?>" class="btn btn-small">
            Practicar
        </a>
    </div>
</div>
        <?php endforeach; ?>
    </div>
</div>

<?php 
function getProgressClass($progress) {
    if ($progress >= 80) return 'mastered';
    if ($progress >= 50) return 'intermediate';
    if ($progress > 0) return 'beginner';
    return 'not-started';
}

require_once __DIR__ . '/../includes/footer.php'; 
?>