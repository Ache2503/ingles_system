<?php
/**
 * pages/topic_detail.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('topic_detail', 'Usuario accedió a topic_detail.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['topic_id'])) {
    header('Location: pages/topics.php');
    exit;
}

$topicId = $_GET['topic_id'];
$userId = $_SESSION['user_id'] ?? null;

// Obtener información detallada del tema
$stmt = $pdo->prepare("SELECT * FROM topics WHERE topic_id = ?");
$stmt->execute([$topicId]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    header('Location: pages/topics.php');
    exit;
}

// Obtener estadísticas del tema
$questionsCountStmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE topic_id = ?");
$questionsCountStmt->execute([$topicId]);
$questionsCount = $questionsCountStmt->fetchColumn();

// Obtener progreso del usuario si está logueado
$userProgress = 0;
$attempts = 0;
$bestScore = 0;
$lastAttempt = null;
$masteryLevel = 'not_started';

if ($userId) {
    $progressStmt = $pdo->prepare("
        SELECT score, mastery_level, last_reviewed 
        FROM user_progress 
        WHERE user_id = ? AND topic_id = ?
    ");
    $progressStmt->execute([$userId, $topicId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($progress) {
        $userProgress = $progress['score'];
        $masteryLevel = $progress['mastery_level'];
        $lastAttempt = $progress['last_reviewed'];
    }
    
    // Obtener mejor puntuación y número de intentos de quiz_history
    $bestScoreStmt = $pdo->prepare("
        SELECT MAX(score) as best_score, COUNT(*) as total_attempts
        FROM quiz_history 
        WHERE user_id = ? AND topic_id = ?
    ");
    $bestScoreStmt->execute([$userId, $topicId]);
    $scoreData = $bestScoreStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($scoreData) {
        $bestScore = $scoreData['best_score'] ?: 0;
        $attempts = $scoreData['total_attempts'] ?: 0;
    }
}

// Obtener algunas preguntas de muestra
$sampleQuestionsStmt = $pdo->prepare("
    SELECT question_text, option_a, option_b, option_c, option_d 
    FROM questions 
    WHERE topic_id = ? 
    LIMIT 3
");
$sampleQuestionsStmt->execute([$topicId]);
$sampleQuestions = $sampleQuestionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?php echo htmlspecialchars($topic['title']); ?></h2>
                    <span class="badge badge-<?php echo $topic['difficulty_level'] === 'beginner' ? 'success' : ($topic['difficulty_level'] === 'intermediate' ? 'warning' : 'danger'); ?>">
                        <?php echo ucfirst($topic['difficulty_level']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="lead"><?php echo htmlspecialchars($topic['description']); ?></p>
                    
                    <div class="topic-stats row">
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3 bg-light rounded">
                                <h4 class="text-primary"><?php echo $questionsCount; ?></h4>
                                <small class="text-muted">Preguntas</small>
                            </div>
                        </div>
                        <?php if ($userId): ?>
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3 bg-light rounded">
                                <h4 class="text-success"><?php echo $bestScore; ?>%</h4>
                                <small class="text-muted">Mejor Puntuación</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3 bg-light rounded">
                                <h4 class="text-info"><?php echo $attempts; ?></h4>
                                <small class="text-muted">Intentos</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($userId && $masteryLevel !== 'not_started'): ?>
                    <div class="mt-4">
                        <h5>Tu Progreso</h5>
                        <div class="progress mb-2" style="height: 30px;">
                            <?php 
                            $progressWidth = min($bestScore, 100);
                            $progressColor = $bestScore >= 80 ? 'success' : ($bestScore >= 60 ? 'warning' : 'danger');
                            ?>
                            <div class="progress-bar bg-<?php echo $progressColor; ?>" style="width: <?php echo $progressWidth; ?>%">
                                <?php echo $bestScore; ?>%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Nivel:</strong> 
                                    <span class="badge badge-<?php echo $masteryLevel === 'mastered' ? 'success' : ($masteryLevel === 'proficient' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $masteryLevel)); ?>
                                    </span>
                                </small>
                            </div>
                            <?php if ($lastAttempt): ?>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Último intento:</strong> <?php echo date('d/m/Y H:i', strtotime($lastAttempt)); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h5>Ejemplos de Preguntas</h5>
                        <?php foreach ($sampleQuestions as $idx => $question): ?>
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <small class="text-muted">Pregunta <?php echo $idx + 1; ?>:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                <div class="row">
                                    <div class="col-6"><small>a) <?php echo htmlspecialchars($question['option_a']); ?></small></div>
                                    <div class="col-6"><small>b) <?php echo htmlspecialchars($question['option_b']); ?></small></div>
                                    <div class="col-6"><small>c) <?php echo htmlspecialchars($question['option_c']); ?></small></div>
                                    <div class="col-6"><small>d) <?php echo htmlspecialchars($question['option_d']); ?></small></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="practice.php?topic_id=<?php echo $topicId; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-play"></i> 
                            <?php echo $attempts > 0 ? 'Continuar Práctica' : 'Comenzar Práctica'; ?>
                        </a>
                        <a href="pages/topics.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left"></i> Volver a Temas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if ($userId): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recomendaciones</h5>
                </div>
                <div class="card-body">
                    <?php if ($bestScore < 60): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Consejo:</strong> Te recomendamos repasar el material antes de continuar.
                        </div>
                    <?php elseif ($bestScore >= 80): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-trophy"></i>
                            <strong>¡Excelente!</strong> Has dominado este tema. ¿Qué tal intentar un tema más avanzado?
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-target"></i>
                            <strong>Casi ahí:</strong> Un poco más de práctica y dominarás este tema.
                        </div>
                    <?php endif; ?>
                    
                    <h6>Temas Relacionados</h6>
                    <?php
                    $relatedStmt = $pdo->prepare("
                        SELECT topic_id, title, difficulty_level 
                        FROM topics 
                        WHERE topic_id != ? AND difficulty_level = ? 
                        LIMIT 3
                    ");
                    $relatedStmt->execute([$topicId, $topic['difficulty_level']]);
                    $relatedTopics = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($relatedTopics): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($relatedTopics as $relatedTopic): ?>
                            <a href="topic_detail.php?topic_id=<?php echo $relatedTopic['topic_id']; ?>" 
                               class="list-group-item list-group-item-action py-2">
                                <small><?php echo htmlspecialchars($relatedTopic['title']); ?></small>
                                <span class="badge badge-secondary badge-sm float-right">
                                    <?php echo ucfirst($relatedTopic['difficulty_level']); ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><small>No hay temas relacionados disponibles.</small></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5>¿Quieres seguir tu progreso?</h5>
                    <p class="text-muted">Regístrate para guardar tu progreso y obtener recomendaciones personalizadas.</p>
                    <a href="auth/register.php" class="btn btn-primary">Registrarse</a>
                    <a href="auth/login.php" class="btn btn-outline-primary">Iniciar Sesión</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stat-card {
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.topic-stats .col-md-4 {
    margin-bottom: 15px;
}
.badge-sm {
    font-size: 0.7em;
}
.progress {
    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>