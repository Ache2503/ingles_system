<?php
/**
 * pages/recommendations.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('recommendations', 'Usuario accedió a recommendations.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener datos del usuario para personalizar recomendaciones
$userStmt = $pdo->prepare("
    SELECT u.*, ug.current_level, ug.total_points, ug.study_streak
    FROM users u
    LEFT JOIN user_gamification ug ON u.user_id = ug.user_id
    WHERE u.user_id = ?
");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

// Obtener configuración del usuario
$configStmt = $pdo->prepare("
    SELECT config_key, config_value 
    FROM user_configuration 
    WHERE user_id = ?
");
$configStmt->execute([$userId]);
$userConfig = [];
while ($config = $configStmt->fetch(PDO::FETCH_ASSOC)) {
    $userConfig[$config['config_key']] = $config['config_value'];
}

$difficultyPreference = $userConfig['difficulty_preference'] ?? 'intermediate';

// 1. Recomendaciones basadas en rendimiento
$performanceStmt = $pdo->prepare("
    SELECT 
        t.topic_id, t.title, t.description, t.difficulty_level,
        AVG(up.score) as avg_score,
        COUNT(up.attempt_id) as attempts,
        MAX(up.last_reviewed) as last_attempt
    FROM topics t
    LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
    WHERE up.score < 70 OR up.topic_id IS NULL
    GROUP BY t.topic_id
    ORDER BY 
        CASE WHEN up.topic_id IS NULL THEN 0 ELSE 1 END,
        avg_score ASC,
        attempts ASC
    LIMIT 6
");
$performanceStmt->execute([$userId]);
$performanceRecommendations = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Recomendaciones por nivel de dificultad preferido
$difficultyStmt = $pdo->prepare("
    SELECT 
        t.topic_id, t.title, t.description, t.difficulty_level,
        COALESCE(up.score, 0) as current_score,
        COALESCE(up.mastery_level, 'not_started') as mastery_level
    FROM topics t
    LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
    WHERE t.difficulty_level = ? 
    AND (up.mastery_level IN ('not_started', 'beginner') OR up.mastery_level IS NULL)
    ORDER BY RAND()
    LIMIT 4
");
$difficultyStmt->execute([$userId, $difficultyPreference]);
$difficultyRecommendations = $difficultyStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Recomendaciones basadas en favoritos (temas similares)
$bookmarksStmt = $pdo->prepare("
    SELECT t.difficulty_level, t.category
    FROM user_bookmarks ub
    JOIN topics t ON ub.content_id = t.topic_id
    WHERE ub.user_id = ? AND ub.content_type = 'topic'
    GROUP BY t.difficulty_level, t.category
    ORDER BY COUNT(*) DESC
    LIMIT 3
");
$bookmarksStmt->execute([$userId]);
$favoritePatterns = $bookmarksStmt->fetchAll(PDO::FETCH_ASSOC);

$similarRecommendations = [];
if (!empty($favoritePatterns)) {
    $pattern = $favoritePatterns[0];
    $similarStmt = $pdo->prepare("
        SELECT 
            t.topic_id, t.title, t.description, t.difficulty_level, t.category,
            COALESCE(up.score, 0) as current_score
        FROM topics t
        LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
        LEFT JOIN user_bookmarks ub ON ub.content_id = t.topic_id AND ub.user_id = ? AND ub.content_type = 'topic'
        WHERE (t.difficulty_level = ? OR t.category = ?)
        AND ub.id IS NULL
        AND (up.score < 80 OR up.score IS NULL)
        ORDER BY RAND()
        LIMIT 4
    ");
    $similarStmt->execute([$userId, $userId, $pattern['difficulty_level'], $pattern['category']]);
    $similarRecommendations = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Recomendaciones basadas en tiempo de estudio
$timeBasedStmt = $pdo->prepare("
    SELECT 
        t.topic_id, t.title, t.description, t.difficulty_level,
        up.last_reviewed,
        DATEDIFF(NOW(), up.last_reviewed) as days_since_review
    FROM topics t
    JOIN user_progress up ON t.topic_id = up.topic_id
    WHERE up.user_id = ? 
    AND up.last_reviewed IS NOT NULL
    AND DATEDIFF(NOW(), up.last_reviewed) >= 7
    AND up.score >= 60
    ORDER BY days_since_review DESC, up.score DESC
    LIMIT 4
");
$timeBasedStmt->execute([$userId]);
$timeBasedRecommendations = $timeBasedStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Temas populares que el usuario no ha intentado
$popularStmt = $pdo->prepare("
    SELECT 
        t.topic_id, t.title, t.description, t.difficulty_level, t.views_count,
        COUNT(DISTINCT up_others.user_id) as users_completed
    FROM topics t
    LEFT JOIN user_progress up_user ON t.topic_id = up_user.topic_id AND up_user.user_id = ?
    LEFT JOIN user_progress up_others ON t.topic_id = up_others.topic_id AND up_others.user_id != ?
    WHERE up_user.topic_id IS NULL
    GROUP BY t.topic_id
    ORDER BY users_completed DESC, t.views_count DESC
    LIMIT 4
");
$popularStmt->execute([$userId, $userId]);
$popularRecommendations = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Verbos que necesitan práctica
$verbsStmt = $pdo->prepare("
    SELECT 
        v.verb_id, v.verb_form, v.translation, v.verb_type,
        COUNT(qh.quiz_id) as practice_count,
        AVG(qh.score) as avg_score
    FROM verbs v
    LEFT JOIN quiz_history qh ON qh.user_id = ? 
    GROUP BY v.verb_id
    HAVING practice_count < 3 OR avg_score < 70 OR avg_score IS NULL
    ORDER BY practice_count ASC, avg_score ASC
    LIMIT 8
");
$verbsStmt->execute([$userId]);
$verbRecommendations = $verbsStmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas para personalización
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT up.topic_id) as topics_attempted,
        AVG(up.score) as avg_score,
        COUNT(DISTINCT DATE(qh.attempt_date)) as study_days,
        COUNT(qh.quiz_id) as total_quizzes
    FROM user_progress up
    LEFT JOIN quiz_history qh ON up.user_id = qh.user_id
    WHERE up.user_id = ?
");
$statsStmt->execute([$userId]);
$userStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .recommendations-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .hero-section {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .hero-title {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        font-weight: bold;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }
    
    .user-insights {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        background: rgba(255, 255, 255, 0.1);
        padding: 2rem;
        border-radius: 15px;
        margin-top: 2rem;
    }
    
    .insight-item {
        text-align: center;
    }
    
    .insight-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .insight-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .recommendation-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-icon {
        font-size: 1.8rem;
    }
    
    .recommendation-reason {
        font-size: 0.9rem;
        color: #6c757d;
        font-style: italic;
    }
    
    .recommendations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .recommendation-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        border-left: 4px solid transparent;
    }
    
    .recommendation-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        background: white;
    }
    
    .recommendation-card.performance { border-left-color: #e74c3c; }
    .recommendation-card.difficulty { border-left-color: #3498db; }
    .recommendation-card.similar { border-left-color: #9b59b6; }
    .recommendation-card.review { border-left-color: #f39c12; }
    .recommendation-card.popular { border-left-color: #1abc9c; }
    .recommendation-card.verb { border-left-color: #2ecc71; }
    
    .card-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .card-description {
        color: #6c757d;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    
    .card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .difficulty-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .difficulty-beginner { background: #d4edda; color: #155724; }
    .difficulty-intermediate { background: #fff3cd; color: #856404; }
    .difficulty-advanced { background: #f8d7da; color: #721c24; }
    
    .card-stats {
        display: flex;
        gap: 1rem;
        color: #6c757d;
        font-size: 0.8rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .recommendation-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .action-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }
    
    .btn-outline {
        background: transparent;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }
    
    .btn-outline:hover {
        background: #f8f9fa;
        color: #495057;
    }
    
    .progress-indicator {
        width: 100%;
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    
    .verb-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .verb-card {
        background: #e8f5e8;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .verb-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
    }
    
    .verb-english {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2e7d32;
        margin-bottom: 0.5rem;
    }
    
    .verb-spanish {
        color: #555;
        margin-bottom: 0.5rem;
    }
    
    .verb-type {
        background: #2e7d32;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 15px;
        font-size: 0.8rem;
    }
    
    .empty-section {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .recommendation-priority {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ff6b6b;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .recommendation-priority.high { background: #ff6b6b; }
    .recommendation-priority.medium { background: #ffa726; }
    .recommendation-priority.low { background: #66bb6a; }
    
    .insights-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }
    
    .summary-item {
        text-align: center;
    }
    
    .summary-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .summary-label {
        opacity: 0.9;
    }
    
    @media (max-width: 768px) {
        .recommendations-container {
            padding: 1rem;
        }
        
        .hero-title {
            font-size: 2rem;
        }
        
        .recommendations-grid {
            grid-template-columns: 1fr;
        }
        
        .verb-grid {
            grid-template-columns: 1fr;
        }
        
        .recommendation-actions {
            flex-direction: column;
        }
    }
</style>

<div class="recommendations-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="hero-title">🎯 Recomendaciones Personalizadas</h1>
        <p class="hero-subtitle">Contenido seleccionado especialmente para ti basado en tu progreso y preferencias</p>
        
        <div class="user-insights">
            <div class="insight-item">
                <div class="insight-number"><?= $userData['current_level'] ?? 1 ?></div>
                <div class="insight-label">Nivel Actual</div>
            </div>
            <div class="insight-item">
                <div class="insight-number"><?= $userStats['topics_attempted'] ?? 0 ?></div>
                <div class="insight-label">Temas Intentados</div>
            </div>
            <div class="insight-item">
                <div class="insight-number"><?= round($userStats['avg_score'] ?? 0) ?>%</div>
                <div class="insight-label">Puntuación Promedio</div>
            </div>
            <div class="insight-item">
                <div class="insight-number"><?= $userData['study_streak'] ?? 0 ?></div>
                <div class="insight-label">Racha de Estudio</div>
            </div>
        </div>
    </div>
    
    <!-- Resumen de insights -->
    <div class="insights-summary">
        <h2 style="margin: 0 0 1.5rem 0; text-align: center;">📊 Tu Perfil de Aprendizaje</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value"><?= ucfirst($difficultyPreference) ?></div>
                <div class="summary-label">Nivel Preferido</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $userStats['study_days'] ?? 0 ?></div>
                <div class="summary-label">Días de Estudio</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= number_format($userData['total_points'] ?? 0) ?></div>
                <div class="summary-label">Puntos Totales</div>
            </div>
        </div>
    </div>
    
    <!-- Recomendaciones por rendimiento -->
    <?php if (!empty($performanceRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">📈</span>
                    Mejora tu Rendimiento
                </h2>
                <p class="recommendation-reason">Temas donde puedes mejorar tu puntuación</p>
            </div>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($performanceRecommendations as $rec): ?>
                <div class="recommendation-card performance" data-topic-id="<?= $rec['topic_id'] ?>" onclick="window.location.href='topic_detail.php?id=<?= $rec['topic_id'] ?>'">
                    <?php if ($rec['avg_score'] === null): ?>
                        <span class="recommendation-priority high">NUEVO</span>
                    <?php elseif ($rec['avg_score'] < 50): ?>
                        <span class="recommendation-priority high">PRIORIDAD</span>
                    <?php endif; ?>
                    
                    <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($rec['description']) ?></p>
                    
                    <?php if ($rec['avg_score'] !== null): ?>
                        <div class="progress-indicator">
                            <div class="progress-bar" style="width: <?= $rec['avg_score'] ?>%"></div>
                        </div>
                        <div class="card-stats">
                            <span class="stat-item">📊 <?= round($rec['avg_score']) ?>% promedio</span>
                            <span class="stat-item">🔄 <?= $rec['attempts'] ?> intentos</span>
                        </div>
                    <?php else: ?>
                        <div class="card-stats">
                            <span class="stat-item">✨ Sin intentos previos</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-meta">
                        <span class="difficulty-badge difficulty-<?= $rec['difficulty_level'] ?>">
                            <?= ucfirst($rec['difficulty_level']) ?>
                        </span>
                    </div>
                    
                    <div class="recommendation-actions">
                        <a href="practice.php?topic_id=<?= $rec['topic_id'] ?>" class="action-btn btn-primary">
                            🎯 Practicar
                        </a>
                        <a href="topic_detail.php?id=<?= $rec['topic_id'] ?>" class="action-btn btn-outline">
                            👁️ Ver Detalles
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recomendaciones por dificultad preferida -->
    <?php if (!empty($difficultyRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">🎚️</span>
                    Perfecto para tu Nivel
                </h2>
                <p class="recommendation-reason">Contenido de nivel <?= ucfirst($difficultyPreference) ?> recomendado para ti</p>
            </div>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($difficultyRecommendations as $rec): ?>
                <div class="recommendation-card difficulty" data-topic-id="<?= $rec['topic_id'] ?>" onclick="window.location.href='topic_detail.php?id=<?= $rec['topic_id'] ?>'">
                    <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($rec['description']) ?></p>
                    
                    <div class="card-meta">
                        <span class="difficulty-badge difficulty-<?= $rec['difficulty_level'] ?>">
                            <?= ucfirst($rec['difficulty_level']) ?>
                        </span>
                        <span class="stat-item">
                            <?php if ($rec['mastery_level'] === 'not_started'): ?>
                                ✨ Nuevo tema
                            <?php else: ?>
                                📊 <?= round($rec['current_score']) ?>%
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="recommendation-actions">
                        <a href="practice.php?topic_id=<?= $rec['topic_id'] ?>" class="action-btn btn-primary">
                            🚀 Empezar
                        </a>
                        <a href="topic_detail.php?id=<?= $rec['topic_id'] ?>" class="action-btn btn-outline">
                            📖 Estudiar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recomendaciones similares a favoritos -->
    <?php if (!empty($similarRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">💫</span>
                    Basado en tus Favoritos
                </h2>
                <p class="recommendation-reason">Temas similares a los que tienes guardados</p>
            </div>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($similarRecommendations as $rec): ?>
                <div class="recommendation-card similar" data-topic-id="<?= $rec['topic_id'] ?>" onclick="window.location.href='topic_detail.php?id=<?= $rec['topic_id'] ?>'">
                    <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($rec['description']) ?></p>
                    
                    <div class="card-meta">
                        <span class="difficulty-badge difficulty-<?= $rec['difficulty_level'] ?>">
                            <?= ucfirst($rec['difficulty_level']) ?>
                        </span>
                        <?php if ($rec['category']): ?>
                            <span class="stat-item">🏷️ <?= htmlspecialchars($rec['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="recommendation-actions">
                        <a href="practice.php?topic_id=<?= $rec['topic_id'] ?>" class="action-btn btn-primary">
                            💜 Descubrir
                        </a>
                        <a href="topic_detail.php?id=<?= $rec['topic_id'] ?>" class="action-btn btn-outline">
                            👀 Explorar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recomendaciones de repaso -->
    <?php if (!empty($timeBasedRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">🔄</span>
                    Hora de Repasar
                </h2>
                <p class="recommendation-reason">Temas que no has revisado recientemente</p>
            </div>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($timeBasedRecommendations as $rec): ?>
                <div class="recommendation-card review" data-topic-id="<?= $rec['topic_id'] ?>" onclick="window.location.href='topic_detail.php?id=<?= $rec['topic_id'] ?>'">
                    <span class="recommendation-priority medium">REPASO</span>
                    
                    <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($rec['description']) ?></p>
                    
                    <div class="card-stats">
                        <span class="stat-item">📅 Hace <?= $rec['days_since_review'] ?> días</span>
                        <span class="stat-item">⏰ Última vez: <?= date('d/m/Y', strtotime($rec['last_reviewed'])) ?></span>
                    </div>
                    
                    <div class="card-meta">
                        <span class="difficulty-badge difficulty-<?= $rec['difficulty_level'] ?>">
                            <?= ucfirst($rec['difficulty_level']) ?>
                        </span>
                    </div>
                    
                    <div class="recommendation-actions">
                        <a href="practice.php?topic_id=<?= $rec['topic_id'] ?>" class="action-btn btn-primary">
                            🔄 Repasar
                        </a>
                        <a href="topic_detail.php?id=<?= $rec['topic_id'] ?>" class="action-btn btn-outline">
                            📚 Estudiar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recomendaciones populares -->
    <?php if (!empty($popularRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">🔥</span>
                    Tendencias Populares
                </h2>
                <p class="recommendation-reason">Temas que otros estudiantes están dominando</p>
            </div>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($popularRecommendations as $rec): ?>
                <div class="recommendation-card popular" data-topic-id="<?= $rec['topic_id'] ?>" onclick="window.location.href='topic_detail.php?id=<?= $rec['topic_id'] ?>'">
                    <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($rec['description']) ?></p>
                    
                    <div class="card-stats">
                        <span class="stat-item">👥 <?= $rec['users_completed'] ?> estudiantes</span>
                        <span class="stat-item">👁️ <?= $rec['views_count'] ?? 0 ?> vistas</span>
                    </div>
                    
                    <div class="card-meta">
                        <span class="difficulty-badge difficulty-<?= $rec['difficulty_level'] ?>">
                            <?= ucfirst($rec['difficulty_level']) ?>
                        </span>
                    </div>
                    
                    <div class="recommendation-actions">
                        <a href="practice.php?topic_id=<?= $rec['topic_id'] ?>" class="action-btn btn-primary">
                            🔥 Unirse
                        </a>
                        <a href="topic_detail.php?id=<?= $rec['topic_id'] ?>" class="action-btn btn-outline">
                            🔍 Ver
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recomendaciones de verbos -->
    <?php if (!empty($verbRecommendations)): ?>
    <div class="recommendation-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <span class="section-icon">🔤</span>
                    Verbos para Practicar
                </h2>
                <p class="recommendation-reason">Verbos que necesitan más atención</p>
            </div>
        </div>
        
        <div class="verb-grid">
            <?php foreach ($verbRecommendations as $verb): ?>
                <div class="verb-card" data-verb-id="<?= $verb['verb_id'] ?>" onclick="practiceVerb(<?= $verb['verb_id'] ?>)">
                    <div class="verb-english"><?= htmlspecialchars($verb['verb_form']) ?></div>
                    <div class="verb-spanish"><?= htmlspecialchars($verb['translation']) ?></div>
                    <div class="verb-type"><?= htmlspecialchars($verb['verb_type']) ?></div>
                    
                    <?php if ($verb['practice_count'] > 0): ?>
                        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #666;">
                            <?= $verb['practice_count'] ?> práctica<?= $verb['practice_count'] > 1 ? 's' : '' ?>
                            <?php if ($verb['avg_score']): ?>
                                • <?= round($verb['avg_score']) ?>%
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #28a745; font-weight: bold;">
                            ¡Nuevo para ti!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Mensaje cuando no hay recomendaciones -->
    <?php if (empty($performanceRecommendations) && empty($difficultyRecommendations) && empty($similarRecommendations) && empty($timeBasedRecommendations) && empty($popularRecommendations) && empty($verbRecommendations)): ?>
    <div class="empty-section">
        <div class="empty-icon">🎉</div>
        <h3>¡Excelente trabajo!</h3>
        <p>Has completado todo el contenido disponible. ¡Sigue practicando para mantener tu nivel!</p>
        <a href="pages/topics.php" class="action-btn btn-primary" style="margin-top: 1rem;">
            Explorar Todos los Temas
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function practiceVerb(verbId) {
    window.location.href = `practice.php?verb_id=${verbId}`;
}

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.recommendation-card, .verb-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animar barras de progreso
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500 + (index * 200));
    });
});

// Tracking de interacciones para mejorar recomendaciones
function trackRecommendationClick(type, contentId) {
    fetch('analytics.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=track_recommendation&type=${type}&content_id=${contentId}`
    }).catch(e => console.log('Analytics tracking failed'));
}

// Añadir tracking a todos los enlaces de recomendaciones
document.querySelectorAll('.recommendation-card').forEach(card => {
    card.addEventListener('click', () => {
        const topicId = card.dataset.topicId;
        if (topicId) {
            trackRecommendationClick('topic', topicId);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
