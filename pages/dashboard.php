<?php
/**
 * pages/dashboard.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('dashboard', 'Usuario accedió a dashboard.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener datos de gamificación
$gameDataStmt = $pdo->prepare("SELECT * FROM user_gamification WHERE user_id = ?");
$gameDataStmt->execute([$userId]);
$gameData = $gameDataStmt->fetch(PDO::FETCH_ASSOC);

if (!$gameData) {
    // Crear registro si no existe
    $pdo->prepare("INSERT INTO user_gamification (user_id) VALUES (?)")->execute([$userId]);
    $gameData = [
        'total_points' => 0,
        'current_level' => 1,
        'experience_points' => 0,
        'study_streak' => 0,
        'longest_streak' => 0,
        'total_study_time' => 0
    ];
}

// Obtener progreso reciente
$recentProgressStmt = $pdo->prepare("
    SELECT t.title, up.score, up.mastery_level, up.last_reviewed
    FROM user_progress up
    JOIN topics t ON up.topic_id = t.topic_id
    WHERE up.user_id = ?
    ORDER BY up.last_reviewed DESC
    LIMIT 5
");
$recentProgressStmt->execute([$userId]);
$recentProgress = $recentProgressStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener logros recientes
$recentAchievementsStmt = $pdo->prepare("
    SELECT ua.*, ac.icon 
    FROM user_achievements ua
    LEFT JOIN achievement_config ac ON ua.achievement_type = ac.achievement_type
    WHERE ua.user_id = ?
    ORDER BY ua.earned_at DESC
    LIMIT 3
");
$recentAchievementsStmt->execute([$userId]);
$recentAchievements = $recentAchievementsStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener temas recomendados
$recommendedTopicsStmt = $pdo->prepare("
    SELECT t.*, 
           COALESCE(up.score, 0) as current_score,
           COALESCE(up.mastery_level, 'not_started') as mastery_level
    FROM topics t
    LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
    WHERE COALESCE(up.mastery_level, 'not_started') IN ('not_started', 'beginner')
    ORDER BY t.difficulty_level, t.topic_id
    LIMIT 4
");
$recommendedTopicsStmt->execute([$userId]);
$recommendedTopics = $recommendedTopicsStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas del día
$todayStatsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as quizzes_today,
        AVG(score) as avg_score_today,
        SUM(duration) as time_today
    FROM quiz_history 
    WHERE user_id = ? AND DATE(attempt_date) = CURDATE()
");
$todayStatsStmt->execute([$userId]);
$todayStats = $todayStatsStmt->fetch(PDO::FETCH_ASSOC);

// Obtener datos para gráfico de progreso semanal
$weeklyProgressStmt = $pdo->prepare("
    SELECT 
        DATE(attempt_date) as date,
        COUNT(*) as quizzes,
        AVG(score) as avg_score
    FROM quiz_history 
    WHERE user_id = ? AND attempt_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(attempt_date)
    ORDER BY date
");
$weeklyProgressStmt->execute([$userId]);
$weeklyProgress = $weeklyProgressStmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular progreso al siguiente nivel
$currentLevel = $gameData['current_level'];
$experiencePoints = $gameData['experience_points'];
$pointsForCurrentLevel = ($currentLevel - 1) * 100;
$pointsForNextLevel = $currentLevel * 100;
$progressToNext = $pointsForNextLevel > $pointsForCurrentLevel ? 
    (($experiencePoints - $pointsForCurrentLevel) / ($pointsForNextLevel - $pointsForCurrentLevel)) * 100 : 100;

// Obtener ranking del usuario
$rankingStmt = $pdo->prepare("
    SELECT ranking FROM user_ranking WHERE user_id = ?
");
$rankingStmt->execute([$userId]);
$userRank = $rankingStmt->fetchColumn() ?: 'N/A';
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 2rem;
        align-items: center;
    }
    
    .welcome-content h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }
    
    .welcome-subtitle {
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .level-info {
        text-align: center;
        background: rgba(255,255,255,0.1);
        padding: 1rem;
        border-radius: 10px;
    }
    
    .level-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .main-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .dashboard-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .card-title {
        font-size: 1.3rem;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
    }
    
    .progress-ring {
        width: 80px;
        height: 80px;
        position: relative;
        margin: 0 auto;
    }
    
    .progress-ring svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    
    .progress-ring circle {
        fill: none;
        stroke-width: 8;
    }
    
    .progress-background {
        stroke: #e9ecef;
    }
    
    .progress-bar {
        stroke: #007bff;
        stroke-linecap: round;
        transition: stroke-dasharray 0.5s ease;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
        color: #2c3e50;
    }
    
    .topic-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .topic-card:hover {
        background: #e3f2fd;
        border-color: #007bff;
        transform: translateY(-2px);
    }
    
    .topic-title {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .topic-progress {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .mastery-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .mastery-not_started { background: #e9ecef; color: #6c757d; }
    .mastery-beginner { background: #fff3cd; color: #856404; }
    .mastery-intermediate { background: #d1ecf1; color: #0c5460; }
    .mastery-advanced { background: #d4edda; color: #155724; }
    .mastery-mastered { background: #f8d7da; color: #721c24; }
    
    .achievement-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
    }
    
    .achievement-icon {
        font-size: 2rem;
        width: 50px;
        text-align: center;
    }
    
    .achievement-content {
        flex: 1;
    }
    
    .achievement-name {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    
    .achievement-desc {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .achievement-points {
        background: #007bff;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .action-button {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    
    .action-button:hover {
        transform: translateY(-3px);
        text-decoration: none;
        color: white;
    }
    
    .action-icon {
        font-size: 1.5rem;
    }
    
    .chart-container {
        height: 200px;
        position: relative;
    }
    
    .chart-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6c757d;
        font-style: italic;
    }
    
    .streak-calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-top: 1rem;
    }
    
    .calendar-day {
        aspect-ratio: 1;
        background: #e9ecef;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .calendar-day.active {
        background: #28a745;
        color: white;
    }
    
    .calendar-day.today {
        border: 2px solid #007bff;
    }
</style>

<div class="dashboard-container">
    <!-- Sección de bienvenida -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1>¡Bienvenido de vuelta, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h1>
            <p class="welcome-subtitle">
                <?php
                $hour = date('H');
                if ($hour < 12) {
                    echo "Buenos días. Es un gran momento para continuar aprendiendo.";
                } elseif ($hour < 18) {
                    echo "Buenas tardes. ¿Listo para una sesión de estudio?";
                } else {
                    echo "Buenas noches. Un poco de práctica antes de dormir nunca está de más.";
                }
                ?>
            </p>
        </div>
        <div class="level-info">
            <div class="level-number">Nivel <?= $gameData['current_level'] ?></div>
            <div>Ranking #<?= $userRank ?></div>
            <div class="progress-ring">
                <svg>
                    <circle class="progress-background" cx="40" cy="40" r="32" stroke-width="6"></circle>
                    <circle class="progress-bar" cx="40" cy="40" r="32" stroke-width="6"
                            stroke-dasharray="<?= $progressToNext * 2.01 ?> 201.06"></circle>
                </svg>
                <div class="progress-text"><?= round($progressToNext) ?>%</div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas generales -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-value"><?= $todayStats['quizzes_today'] ?: 0 ?></div>
            <div class="stat-label">Quizzes Hoy</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-value"><?= round(($todayStats['time_today'] ?: 0) / 60, 1) ?>h</div>
            <div class="stat-label">Tiempo Hoy</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔥</div>
            <div class="stat-value"><?= $gameData['study_streak'] ?></div>
            <div class="stat-label">Racha Actual</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-value"><?= number_format($gameData['total_points']) ?></div>
            <div class="stat-label">Puntos Totales</div>
        </div>
    </div>
    
    <div class="main-grid">
        <!-- Contenido principal -->
        <div>
            <!-- Progreso reciente -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">📈 Progreso Reciente</h3>
                    <a href="pages/progress.php" class="btn btn-sm btn-outline-primary">Ver Todo</a>
                </div>
                
                <?php if (empty($recentProgress)): ?>
                    <div class="chart-placeholder">
                        <p>Completa tu primer quiz para ver tu progreso aquí</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentProgress as $progress): ?>
                        <div class="topic-card">
                            <div class="topic-title"><?= htmlspecialchars($progress['title']) ?></div>
                            <div class="topic-progress">
                                <span>Puntuación: <?= round($progress['score']) ?>%</span>
                                <span class="mastery-badge mastery-<?= $progress['mastery_level'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $progress['mastery_level'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Temas recomendados -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">💡 Recomendado para Ti</h3>
                    <a href="pages/topics.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
                
                <?php foreach ($recommendedTopics as $topic): ?>
                    <div class="topic-card" onclick="window.location.href='practice.php?topic_id=<?= $topic['topic_id'] ?>'">
                        <div class="topic-title"><?= htmlspecialchars($topic['title']) ?></div>
                        <div class="topic-progress">
                            <span><?= htmlspecialchars($topic['description']) ?></span>
                            <span class="mastery-badge mastery-<?= $topic['mastery_level'] ?>">
                                <?php if ($topic['mastery_level'] === 'not_started'): ?>
                                    Nuevo
                                <?php else: ?>
                                    <?= round($topic['current_score']) ?>%
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Barra lateral -->
        <div>
            <!-- Logros recientes -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">🏆 Logros Recientes</h3>
                    <a href="pages/profile.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
                
                <?php if (empty($recentAchievements)): ?>
                    <div class="chart-placeholder">
                        <p>¡Completa actividades para ganar logros!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentAchievements as $achievement): ?>
                        <div class="achievement-item">
                            <div class="achievement-icon"><?= $achievement['icon'] ?: '🏆' ?></div>
                            <div class="achievement-content">
                                <div class="achievement-name"><?= htmlspecialchars($achievement['achievement_name']) ?></div>
                                <div class="achievement-desc"><?= htmlspecialchars($achievement['achievement_description']) ?></div>
                            </div>
                            <div class="achievement-points">+<?= $achievement['points_earned'] ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Calendario de racha -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">📅 Racha de Estudio</h3>
                </div>
                
                <div class="streak-calendar">
                    <?php
                    // Generar calendario de los últimos 14 días
                    for ($i = 13; $i >= 0; $i--):
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $dayNumber = date('j', strtotime($date));
                        $isToday = $date === date('Y-m-d');
                        $hasActivity = $i < $gameData['study_streak']; // Simplificado para demo
                    ?>
                        <div class="calendar-day <?= $hasActivity ? 'active' : '' ?> <?= $isToday ? 'today' : '' ?>"
                             title="<?= date('d/m', strtotime($date)) ?>">
                            <?= $dayNumber ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <?php if ($gameData['study_streak'] > 0): ?>
                    <p style="text-align: center; margin-top: 1rem; color: #28a745;">
                        ¡Vas <?= $gameData['study_streak'] ?> día<?= $gameData['study_streak'] > 1 ? 's' : '' ?> consecutivo<?= $gameData['study_streak'] > 1 ? 's' : '' ?>!
                    </p>
                <?php else: ?>
                    <p style="text-align: center; margin-top: 1rem; color: #6c757d;">
                        Completa un quiz hoy para iniciar tu racha
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">⚡ Acciones Rápidas</h3>
        </div>
        
        <div class="quick-actions">
            <a href="pages/topics.php" class="action-button">
                <span class="action-icon">📚</span>
                <span>Explorar Temas</span>
            </a>
            <a href="practice.php?mode=quick" class="action-button">
                <span class="action-icon">⚡</span>
                <span>Práctica Rápida</span>
            </a>
            <a href="practice.php?mode=exam" class="action-button">
                <span class="action-icon">🎯</span>
                <span>Modo Examen</span>
            </a>
            <a href="pages/progress.php" class="action-button">
                <span class="action-icon">📊</span>
                <span>Ver Progreso</span>
            </a>
        </div>
    </div>
</div>

<script>
// Animaciones al cargar
document.addEventListener('DOMContentLoaded', () => {
    // Animar estadísticas
    document.querySelectorAll('.stat-value').forEach((element, index) => {
        const finalValue = parseFloat(element.textContent.replace(/[^0-9.]/g, '')) || 0;
        let currentValue = 0;
        const increment = finalValue / 30;
        
        setTimeout(() => {
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    element.textContent = element.textContent.replace(/[0-9.]+/, finalValue.toString());
                    clearInterval(timer);
                } else {
                    const displayValue = Math.floor(currentValue);
                    element.textContent = element.textContent.replace(/[0-9.]+/, displayValue.toString());
                }
            }, 50);
        }, index * 200);
    });
    
    // Animar progreso circular
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.strokeDasharray = '0 201.06';
        setTimeout(() => {
            progressBar.style.strokeDasharray = `<?= $progressToNext * 2.01 ?> 201.06`;
        }, 500);
    }
    
    // Animar entrada de tarjetas
    document.querySelectorAll('.dashboard-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Actualizar la hora cada minuto
setInterval(() => {
    const now = new Date();
    const hour = now.getHours();
    let greeting;
    
    if (hour < 12) {
        greeting = "Buenos días. Es un gran momento para continuar aprendiendo.";
    } else if (hour < 18) {
        greeting = "Buenas tardes. ¿Listo para una sesión de estudio?";
    } else {
        greeting = "Buenas noches. Un poco de práctica antes de dormir nunca está de más.";
    }
    
    const subtitle = document.querySelector('.welcome-subtitle');
    if (subtitle) {
        subtitle.textContent = greeting;
    }
}, 60000);
</script>

<?php include '../includes/footer.php'; ?>
