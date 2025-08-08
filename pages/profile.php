<?php
/**
 * Página de Perfil - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener información del usuario
$userInfo = getCurrentUserInfo();
$userId = $userInfo['id'];

// Validar sesión (no expirada)
validateSession();

// Log de actividad
logUserActivity('view_profile', 'Usuario accedió a su perfil');

// Obtener datos de gamificación del usuario
$userGameData = $pdo->prepare("
    SELECT * FROM user_gamification WHERE user_id = ?
");
$userGameData->execute([$userId]);
$gameData = $userGameData->fetch(PDO::FETCH_ASSOC);

// Si no existe, crear registro
if (!$gameData) {
    $pdo->prepare("INSERT INTO user_gamification (user_id) VALUES (?)")->execute([$userId]);
    $gameData = [
        'total_points' => 0,
        'current_level' => 1,
        'experience_points' => 0,
        'study_streak' => 0,
        'study_streak_date' => null,
        'longest_streak' => 0,
        'total_study_time' => 0
    ];
} else {
    // Asegurar que todos los campos existan
    $gameData = array_merge([
        'total_points' => 0,
        'current_level' => 1,
        'experience_points' => 0,
        'study_streak' => 0,
        'study_streak_date' => null,
        'longest_streak' => 0,
        'total_study_time' => 0
    ], $gameData);
}

// Obtener logros del usuario
$achievementsStmt = $pdo->prepare("
    SELECT ua.achievement_type, ua.achievement_name, ua.achievement_description, 
           ua.points_earned, ua.earned_at
    FROM user_achievements ua
    WHERE ua.user_id = ?
    ORDER BY ua.earned_at DESC
");
$achievementsStmt->execute([$userId]);
$userAchievements = $achievementsStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener ranking del usuario (si la tabla existe)
try {
    $rankingStmt = $pdo->prepare("
        SELECT ranking FROM user_ranking WHERE user_id = ?
    ");
    $rankingStmt->execute([$userId]);
    $userRank = $rankingStmt->fetchColumn() ?: 'N/A';
} catch (Exception $e) {
    $userRank = 'N/A';
}

// Obtener top 10 del ranking (si la tabla existe)
try {
    $topUsersStmt = $pdo->query("
        SELECT username, total_points, ranking FROM user_ranking LIMIT 10
    ");
    $topUsers = $topUsersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $topUsers = [];
}

// Calcular progreso al siguiente nivel
$currentLevel = $gameData['current_level'];
$experiencePoints = $gameData['experience_points'];
$pointsForCurrentLevel = ($currentLevel - 1) * 100;
$pointsForNextLevel = $currentLevel * 100;
$progressToNext = $pointsForNextLevel > $pointsForCurrentLevel ? 
    (($experiencePoints - $pointsForCurrentLevel) / ($pointsForNextLevel - $pointsForCurrentLevel)) * 100 : 100;

// Obtener logros disponibles (simplificado)
try {
    $availableAchievementsStmt = $pdo->prepare("
        SELECT DISTINCT 'first_quiz' as achievement_type, 'Primer Quiz' as title, 'Completa tu primer quiz' as description
        UNION ALL
        SELECT 'week_streak' as achievement_type, 'Racha Semanal' as title, 'Estudia 7 días consecutivos' as description
        UNION ALL
        SELECT 'month_streak' as achievement_type, 'Racha Mensual' as title, 'Estudia 30 días consecutivos' as description
        UNION ALL
        SELECT 'perfectionist' as achievement_type, 'Perfeccionista' as title, 'Mantén 90% de promedio en 5 quizzes' as description
        UNION ALL
        SELECT 'topic_master' as achievement_type, 'Maestro de Temas' as title, 'Domina 5 temas completamente' as description
    ");
    $availableAchievementsStmt->execute();
    $availableAchievements = $availableAchievementsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $availableAchievements = [];
}
?>

<style>
    .gamification-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .user-profile {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 2rem;
        align-items: center;
    }
    
    .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }
    
    .user-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .level-progress {
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        height: 20px;
        overflow: hidden;
    }
    
    .level-fill {
        background: linear-gradient(90deg, #28a745, #20c997);
        height: 100%;
        border-radius: 20px;
        transition: width 0.3s ease;
    }
    
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .game-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .game-card:hover {
        transform: translateY(-5px);
    }
    
    .card-title {
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .achievement-item {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .achievement-item.earned {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        border-color: #f1c40f;
        transform: scale(1.05);
    }
    
    .achievement-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .achievement-name {
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .achievement-desc {
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .points-badge {
        background: #007bff;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        margin-top: 0.5rem;
        display: inline-block;
    }
    
    .ranking-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .ranking-table th,
    .ranking-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .ranking-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .rank-position {
        font-weight: bold;
        color: #007bff;
    }
    
    .rank-current {
        background: #e3f2fd;
    }
    
    .progress-ring {
        width: 60px;
        height: 60px;
        position: relative;
    }
    
    .progress-ring svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    
    .progress-ring circle {
        fill: none;
        stroke-width: 4;
    }
    
    .streak-calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-top: 1rem;
    }
    
    .calendar-day {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        background: #e9ecef;
    }
    
    .calendar-day.active {
        background: #28a745;
    }
    
    .calendar-day.today {
        border: 2px solid #007bff;
    }
</style>

<div class="gamification-container">
    <h1>🎮 Tu Perfil de Estudiante</h1>
    
    <!-- Perfil del usuario -->
    <div class="user-profile">
        <div class="avatar">
            <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
        </div>
        
        <div>
            <h2><?= htmlspecialchars($_SESSION['username']) ?></h2>
            <p>Nivel <?= $gameData['current_level'] ?> • Ranking #<?= $userRank ?></p>
            <div class="level-progress">
                <div class="level-fill" style="width: <?= $progressToNext ?>%"></div>
            </div>
            <small><?= $experiencePoints ?> / <?= $pointsForNextLevel ?> XP</small>
        </div>
        
        <div class="user-stats">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($gameData['total_points']) ?></div>
                <div class="stat-label">Puntos Totales</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $gameData['study_streak'] ?></div>
                <div class="stat-label">Racha Actual</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $gameData['longest_streak'] ?></div>
                <div class="stat-label">Mejor Racha</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= round($gameData['total_study_time'] / 60, 1) ?>h</div>
                <div class="stat-label">Tiempo Total</div>
            </div>
        </div>
    </div>
    
    <div class="cards-grid">
        <!-- Logros obtenidos -->
        <div class="game-card">
            <h3 class="card-title">🏆 Logros Obtenidos (<?= count($userAchievements) ?>)</h3>
            <?php if (empty($userAchievements)): ?>
                <p>¡Completa tu primer quiz para obtener tu primer logro!</p>
            <?php else: ?>
                <div class="achievements-grid">
                    <?php foreach (array_slice($userAchievements, 0, 6) as $achievement): ?>
                    <div class="achievement-item earned">
                        <div class="achievement-icon">🏆</div>
                        <div class="achievement-name"><?= htmlspecialchars($achievement['achievement_name']) ?></div>
                        <div class="achievement-desc"><?= htmlspecialchars($achievement['achievement_description']) ?></div>
                        <div class="points-badge">+<?= $achievement['points_earned'] ?> pts</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($userAchievements) > 6): ?>
                    <a href="achievements.php" class="btn btn-primary">Ver todos los logros</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Logros disponibles -->
        <div class="game-card">
            <h3 class="card-title">🎯 Próximos Logros</h3>
            <?php if (empty($availableAchievements)): ?>
                <p>¡Sigue estudiando para desbloquear nuevos logros!</p>
            <?php else: ?>
            <div class="achievements-grid">
                <?php foreach (array_slice($availableAchievements, 0, 4) as $achievement): ?>
                <div class="achievement-item">
                    <div class="achievement-icon">🎯</div>
                    <div class="achievement-name"><?= htmlspecialchars($achievement['title']) ?></div>
                    <div class="achievement-desc"><?= htmlspecialchars($achievement['description']) ?></div>
                    <div class="points-badge">+50 pts</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Ranking -->
        <div class="game-card">
            <h3 class="card-title">🏅 Tabla de Clasificación</h3>
            <?php if (empty($topUsers)): ?>
                <p>El ranking estará disponible pronto. ¡Sigue estudiando para aparecer aquí!</p>
            <?php else: ?>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>Nivel</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($topUsers, 0, 10) as $index => $user): ?>
                    <tr class="<?= isset($user['user_id']) && $user['user_id'] == $userId ? 'rank-current' : '' ?>">
                        <td class="rank-position"><?= $index + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($user['username'] ?? 'Usuario') ?>
                            <?= isset($user['user_id']) && $user['user_id'] == $userId ? ' (Tú)' : '' ?>
                        </td>
                        <td><?= $gameData['current_level'] ?></td>
                        <td><?= number_format($user['total_points'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Calendario de racha -->
        <div class="game-card">
            <h3 class="card-title">🔥 Racha de Estudio</h3>
            <p>Mantén tu racha estudiando todos los días</p>
            <div class="streak-calendar">
                <?php for ($i = 0; $i < 28; $i++): ?>
                    <?php 
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $isToday = $date === date('Y-m-d');
                    $hasActivity = $i < $gameData['study_streak']; // Simplificado para demo
                    ?>
                    <div class="calendar-day <?= $hasActivity ? 'active' : '' ?> <?= $isToday ? 'today' : '' ?>"
                         title="<?= date('d/m', strtotime($date)) ?>"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="game-card">
        <h3 class="card-title">⚡ Acciones Rápidas</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="pages/topics.php" class="btn btn-primary">
                📚 Continuar Estudiando
            </a>
            <a href="pages/progress.php" class="btn btn-info">
                📊 Ver Progreso Detallado
            </a>
            <a href="achievements.php" class="btn btn-success">
                🏆 Todos los Logros
            </a>
            <a href="pages/settings.php" class="btn btn-secondary">
                ⚙️ Configuración
            </a>
        </div>
    </div>
</div>

<script>
// Animaciones para los números
document.addEventListener('DOMContentLoaded', () => {
    // Animar barras de progreso
    document.querySelectorAll('.level-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // Animar números
    document.querySelectorAll('.stat-number').forEach(element => {
        const finalValue = parseFloat(element.textContent.replace(/[^0-9.]/g, ''));
        let currentValue = 0;
        const increment = finalValue / 30;
        
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
    });
});

// Efecto de hover para logros
document.querySelectorAll('.achievement-item').forEach(item => {
    item.addEventListener('mouseenter', () => {
        if (item.classList.contains('earned')) {
            item.style.transform = 'scale(1.1) rotate(2deg)';
        }
    });
    
    item.addEventListener('mouseleave', () => {
        item.style.transform = '';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
