<?php
/**
 * Página de Progreso - Solo usuarios autenticados
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
logUserActivity('view_progress', 'Usuario revisó su progreso');

// Obtener progreso del usuario con más detalles
$progressStmt = $pdo->prepare("
    SELECT t.topic_id, t.title, t.category, 
           COALESCE(up.score, 0) as score, 
           COALESCE(up.mastery_level, 'not_started') as mastery_level,
           (SELECT COUNT(*) FROM user_progress up2 WHERE up2.topic_id = t.topic_id AND up2.user_id = ?) as attempts,
           up.last_reviewed,
           (SELECT COUNT(*) FROM questions WHERE topic_id = t.topic_id) as total_questions
    FROM topics t
    LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
    ORDER BY t.category, t.title
");
$progressStmt->execute([$userId, $userId]);
$progressData = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de gamificación
$gamificationStmt = $pdo->prepare("
    SELECT total_points, experience_points, study_streak, 
           COALESCE(study_streak_date, '1970-01-01') as streak_date
    FROM user_gamification 
    WHERE user_id = ?
");
$gamificationStmt->execute([$userId]);
$gamificationData = $gamificationStmt->fetch(PDO::FETCH_ASSOC);

if (!$gamificationData) {
    $gamificationData = [
        'total_points' => 0,
        'experience_points' => 0,
        'study_streak' => 0,
        'streak_date' => '1970-01-01'
    ];
}

// Obtener logros recientes
$achievementsStmt = $pdo->prepare("
    SELECT achievement_name, achievement_description, points_earned, earned_at
    FROM user_achievements 
    WHERE user_id = ?
    ORDER BY earned_at DESC
    LIMIT 5
");
$achievementsStmt->execute([$userId]);
$recentAchievements = $achievementsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas generales
$totalTopics = count($progressData);
$masteredTopics = 0;
$totalScore = 0;
$inProgressTopics = 0;

foreach ($progressData as $topic) {
    $totalScore += $topic['score'];
    if ($topic['mastery_level'] === 'mastered') {
        $masteredTopics++;
    } elseif ($topic['mastery_level'] !== 'not_started') {
        $inProgressTopics++;
    }
}

$averageScore = $totalTopics > 0 ? round($totalScore / $totalTopics) : 0;
$completionPercentage = $totalTopics > 0 ? round(($masteredTopics / $totalTopics) * 100) : 0;
?>

<style>
.progress-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    opacity: 0.9;
}

.stat-card p {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
}

.stat-card.achievements {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.points {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.streak {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.progress-details {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.progress-details h2 {
    color: #333;
    margin-bottom: 20px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.progress-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.progress-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.progress-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.progress-table tr:hover {
    background-color: #f8f9fa;
}

.progress-bar {
    position: relative;
    width: 100px;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
    position: relative;
}

.progress-fill.low { background: linear-gradient(90deg, #ff6b6b, #ee5a52); }
.progress-fill.medium { background: linear-gradient(90deg, #feca57, #ff9f43); }
.progress-fill.high { background: linear-gradient(90deg, #48dbfb, #0abde3); }
.progress-fill.perfect { background: linear-gradient(90deg, #1dd1a1, #10ac84); }

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: bold;
    color: #333;
    z-index: 1;
}

.mastery-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.mastery-badge.not_started { background: #6c757d; color: white; }
.mastery-badge.beginner { background: #17a2b8; color: white; }
.mastery-badge.intermediate { background: #ffc107; color: #212529; }
.mastery-badge.advanced { background: #28a745; color: white; }
.mastery-badge.mastered { background: #6f42c1; color: white; }

.btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn.primary { background: #007bff; color: white; }
.btn.primary:hover { background: #0056b3; }

.btn.success { background: #28a745; color: white; }
.btn.success:hover { background: #218838; }

.category-header {
    background: #343a40;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.achievements-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.achievement-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-left: 4px solid #ffd700;
    background: #fffbf0;
    margin-bottom: 10px;
    border-radius: 0 5px 5px 0;
}

.achievement-icon {
    font-size: 24px;
    margin-right: 15px;
}

.achievement-content h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.achievement-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.achievement-points {
    margin-left: auto;
    background: #ffd700;
    color: #333;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    font-size: 12px;
}

@media (max-width: 768px) {
    .progress-container {
        padding: 10px;
    }
    
    .stats-summary {
        grid-template-columns: 1fr;
    }
    
    .progress-table {
        font-size: 14px;
    }
    
    .progress-table th,
    .progress-table td {
        padding: 8px;
    }
}
</style>

<div class="progress-container">
    <h1>📊 Mi Progreso de Aprendizaje</h1>
    
    <div class="stats-summary">
        <div class="stat-card">
            <h3>📚 Temas Completados</h3>
            <p><?php echo "$masteredTopics / $totalTopics"; ?></p>
            <small><?php echo $completionPercentage; ?>% del total</small>
        </div>
        
        <div class="stat-card">
            <h3>📈 Puntuación Promedio</h3>
            <p><?php echo "$averageScore%"; ?></p>
            <small><?php echo $inProgressTopics; ?> en progreso</small>
        </div>
        
        <div class="stat-card points">
            <h3>⭐ Puntos Totales</h3>
            <p><?php echo number_format($gamificationData['total_points']); ?></p>
            <small>XP: <?php echo number_format($gamificationData['experience_points']); ?></small>
        </div>
        
        <div class="stat-card streak">
            <h3>🔥 Racha de Estudio</h3>
            <p><?php echo $gamificationData['study_streak']; ?> días</p>
            <small>
                <?php 
                $lastStreak = $gamificationData['streak_date'];
                if ($lastStreak && $lastStreak !== '1970-01-01') {
                    echo 'Último: ' . date('d/m/Y', strtotime($lastStreak));
                } else {
                    echo 'Comienza estudiando hoy';
                }
                ?>
            </small>
        </div>
    </div>

    <?php if (!empty($recentAchievements)): ?>
    <div class="achievements-section">
        <h2>🏆 Logros Recientes</h2>
        <?php foreach ($recentAchievements as $achievement): ?>
            <div class="achievement-item">
                <div class="achievement-icon">🏆</div>
                <div class="achievement-content">
                    <h4><?php echo htmlspecialchars($achievement['achievement_name']); ?></h4>
                    <p><?php echo htmlspecialchars($achievement['achievement_description']); ?></p>
                    <small>Obtenido: <?php echo date('d/m/Y H:i', strtotime($achievement['earned_at'])); ?></small>
                </div>
                <div class="achievement-points">+<?php echo $achievement['points_earned']; ?> pts</div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="progress-details">
        <h2>📋 Detalle por Tema</h2>
        
        <table class="progress-table">
            <thead>
                <tr>
                    <th>📚 Tema</th>
                    <th>🏷️ Categoría</th>
                    <th>📊 Progreso</th>
                    <th>🎯 Nivel</th>
                    <th>📅 Última Práctica</th>
                    <th>🎮 Intentos</th>
                    <th>⚡ Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $currentCategory = '';
                foreach ($progressData as $topic): 
                    if ($currentCategory !== $topic['category']):
                        $currentCategory = $topic['category'];
                ?>
                    <tr class="category-header">
                        <td colspan="7"><?php echo strtoupper(htmlspecialchars($topic['category'])); ?></td>
                    </tr>
                <?php endif; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($topic['title']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($topic['category'])); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill <?php 
                                    if ($topic['score'] >= 90) echo 'perfect';
                                    elseif ($topic['score'] >= 70) echo 'high';
                                    elseif ($topic['score'] >= 50) echo 'medium';
                                    else echo 'low';
                                ?>" style="width: <?php echo $topic['score']; ?>%"></div>
                                <div class="progress-text"><?php echo $topic['score']; ?>%</div>
                            </div>
                        </td>
                        <td>
                            <span class="mastery-badge <?php echo $topic['mastery_level']; ?>">
                                <?php 
                                $levels = [
                                    'not_started' => '🚀 No iniciado',
                                    'beginner' => '🌱 Principiante',
                                    'intermediate' => '📈 Intermedio',
                                    'advanced' => '⭐ Avanzado',
                                    'mastered' => '👑 Dominado'
                                ];
                                echo $levels[$topic['mastery_level']];
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($topic['last_reviewed']) {
                                echo date('d/m/Y', strtotime($topic['last_reviewed']));
                            } else {
                                echo '<span style="color: #6c757d;">Nunca</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <span style="font-weight: bold; color: #007bff;">
                                <?php echo $topic['attempts']; ?> vez<?php echo $topic['attempts'] !== 1 ? 'es' : ''; ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo nav_url('practice', ['topic_id' => $topic['topic_id']]); ?>" 
                               class="btn <?php echo $topic['mastery_level'] === 'mastered' ? 'success' : 'primary'; ?>">
                                <?php echo $topic['mastery_level'] === 'mastered' ? '🔄 Repasar' : '📖 Practicar'; ?>
                            </a>
                            <?php if ($topic['total_questions'] > 0): ?>
                                <small style="display: block; margin-top: 5px; color: #6c757d;">
                                    <?php echo $topic['total_questions']; ?> pregunta<?php echo $topic['total_questions'] !== 1 ? 's' : ''; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Animaciones para las estadísticas
document.addEventListener('DOMContentLoaded', function() {
    // Animar contadores
    const animateCounter = (element, target) => {
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 20);
    };
    
    // Animar barras de progreso
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100 + (index * 50));
    });
    
    // Animar tarjetas de estadísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
    });
    
    // Filtros para la tabla
    const categoryFilter = document.createElement('select');
    categoryFilter.innerHTML = '<option value="">Todas las categorías</option>';
    categoryFilter.style.cssText = 'margin: 10px 0; padding: 8px; border-radius: 5px; border: 1px solid #ddd;';
    
    // Obtener categorías únicas
    const categories = [...new Set(Array.from(document.querySelectorAll('tbody tr:not(.category-header)')).map(row => {
        const categoryCell = row.cells[1];
        return categoryCell ? categoryCell.textContent.trim() : '';
    }).filter(cat => cat))];
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.toLowerCase();
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
    
    // Insertar filtro antes de la tabla
    const progressDetails = document.querySelector('.progress-details h2');
    progressDetails.parentNode.insertBefore(categoryFilter, progressDetails.nextSibling);
    
    // Event listener para filtro
    categoryFilter.addEventListener('change', function() {
        const selectedCategory = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr:not(.category-header)');
        const categoryHeaders = document.querySelectorAll('.category-header');
        
        if (selectedCategory === '') {
            // Mostrar todas las filas
            rows.forEach(row => row.style.display = '');
            categoryHeaders.forEach(header => header.style.display = '');
        } else {
            // Filtrar por categoría
            rows.forEach(row => {
                const categoryCell = row.cells[1];
                if (categoryCell && categoryCell.textContent.toLowerCase().includes(selectedCategory)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar solo headers relevantes
            categoryHeaders.forEach(header => {
                if (header.textContent.toLowerCase().includes(selectedCategory)) {
                    header.style.display = '';
                } else {
                    header.style.display = 'none';
                }
            });
        }
    });
    
    // Tooltips para los badges de maestría
    const masteryBadges = document.querySelectorAll('.mastery-badge');
    masteryBadges.forEach(badge => {
        const tooltipTexts = {
            'not_started': 'Aún no has comenzado este tema',
            'beginner': 'Conocimiento básico del tema',
            'intermediate': 'Comprensión moderada del tema',
            'advanced': 'Buen dominio del tema',
            'mastered': '¡Has dominado completamente este tema!'
        };
        
        const className = Array.from(badge.classList).find(c => tooltipTexts[c]);
        if (className) {
            badge.title = tooltipTexts[className];
        }
    });
});

// Función para actualizar el progreso automáticamente
async function refreshProgress() {
    try {
        const response = await fetch('<?php echo nav_url('user_stats'); ?>');
        if (response.ok) {
            const data = await response.json();
            // Actualizar estadísticas sin recargar la página
            console.log('Progress updated:', data);
        }
    } catch (error) {
        console.log('Error refreshing progress:', error);
    }
}

// Actualizar progreso cada 30 segundos
setInterval(refreshProgress, 30000);
</script>

<?php include '../includes/footer.php'; ?>