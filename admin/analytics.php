<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Obtener estadísticas avanzadas
try {
    // Estadísticas de usuarios
    $userStats = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_month
        FROM users
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Estadísticas de actividad
    $activityStats = $pdo->query("
        SELECT 
            COUNT(*) as total_attempts,
            COUNT(CASE WHEN attempt_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as attempts_week,
            ROUND(AVG(score), 2) as avg_score,
            COUNT(DISTINCT user_id) as active_users
        FROM quiz_history
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Top 5 usuarios más activos
    $topUsers = $pdo->query("
        SELECT u.username, COUNT(qh.history_id) as attempts, ROUND(AVG(qh.score), 2) as avg_score
        FROM users u
        JOIN quiz_history qh ON u.user_id = qh.user_id
        GROUP BY u.user_id
        ORDER BY attempts DESC, avg_score DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Temas más populares
    $popularTopics = $pdo->query("
        SELECT t.title, COUNT(qh.history_id) as attempts, ROUND(AVG(qh.score), 2) as avg_score
        FROM topics t
        JOIN quiz_history qh ON t.topic_id = qh.topic_id
        GROUP BY t.topic_id
        ORDER BY attempts DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas por día de la semana
    $weeklyStats = $pdo->query("
        SELECT 
            DAYNAME(attempt_date) as day_name,
            COUNT(*) as attempts,
            ROUND(AVG(score), 2) as avg_score
        FROM quiz_history
        WHERE attempt_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DAYOFWEEK(attempt_date), DAYNAME(attempt_date)
        ORDER BY DAYOFWEEK(attempt_date)
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en analytics: " . $e->getMessage());
    $error = "Error al cargar estadísticas";
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
    .analytics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .stat-change {
        font-size: 0.8rem;
        margin-top: 0.5rem;
        padding: 0.25rem 0.5rem;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        display: inline-block;
    }
    
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .chart-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .chart-title {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #2c3e50;
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-container th {
        background: #f8f9fa;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .table-container td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    .progress-bar {
        width: 100%;
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        border-radius: 3px;
        transition: width 0.3s ease;
    }
    
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
</style>

<div class="analytics-container">
    <h1>📊 Analytics y Reportes</h1>
    
    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $userStats['total_users'] ?? 0 ?></div>
            <div class="stat-label">Total de Usuarios</div>
            <div class="stat-change">+<?= $userStats['new_users_week'] ?? 0 ?> esta semana</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $activityStats['total_attempts'] ?? 0 ?></div>
            <div class="stat-label">Intentos de Quiz</div>
            <div class="stat-change">+<?= $activityStats['attempts_week'] ?? 0 ?> esta semana</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $activityStats['avg_score'] ?? 0 ?>%</div>
            <div class="stat-label">Puntuación Promedio</div>
            <div class="stat-change">Todos los tiempos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $activityStats['active_users'] ?? 0 ?></div>
            <div class="stat-label">Usuarios Activos</div>
            <div class="stat-change">Con actividad reciente</div>
        </div>
    </div>
    
    <!-- Gráficos y tablas -->
    <div class="charts-grid">
        <!-- Top usuarios -->
        <div class="chart-card">
            <h3 class="chart-title">🏆 Top Usuarios Más Activos</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Intentos</th>
                            <th>Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= $user['attempts'] ?></span>
                            </td>
                            <td>
                                <?= $user['avg_score'] ?>%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $user['avg_score'] ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Temas populares -->
        <div class="chart-card">
            <h3 class="chart-title">📚 Temas Más Populares</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tema</th>
                            <th>Intentos</th>
                            <th>Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popularTopics as $topic): ?>
                        <tr>
                            <td><?= htmlspecialchars($topic['title']) ?></td>
                            <td>
                                <span class="badge badge-success"><?= $topic['attempts'] ?></span>
                            </td>
                            <td>
                                <?= $topic['avg_score'] ?>%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $topic['avg_score'] ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas semanales -->
    <div class="chart-card">
        <h3 class="chart-title">📅 Actividad por Día de la Semana</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Intentos</th>
                        <th>Puntuación Promedio</th>
                        <th>Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeklyStats as $day): ?>
                    <tr>
                        <td><?= $day['day_name'] ?></td>
                        <td><?= $day['attempts'] ?></td>
                        <td><?= $day['avg_score'] ?>%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= min(100, ($day['attempts'] / max(array_column($weeklyStats, 'attempts'))) * 100) ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="chart-card">
        <h3 class="chart-title">⚡ Acciones Rápidas</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="export_data.php" class="btn btn-primary">
                📊 Exportar Reportes
            </a>
            <a href="backup.php" class="btn btn-secondary">
                💾 Backup Base de Datos
            </a>
            <a href="send_notifications.php" class="btn btn-info">
                📧 Enviar Notificaciones
            </a>
            <a href="system_health.php" class="btn btn-warning">
                🔧 Estado del Sistema
            </a>
        </div>
    </div>
</div>

<script>
// Actualizar estadísticas cada 30 segundos
setInterval(() => {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            // Actualizar solo las estadísticas numéricas
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');
            
            document.querySelectorAll('.stat-number').forEach((element, index) => {
                const newValue = newDoc.querySelectorAll('.stat-number')[index]?.textContent;
                if (newValue && element.textContent !== newValue) {
                    element.style.animation = 'pulse 0.5s ease-in-out';
                    element.textContent = newValue;
                }
            });
        })
        .catch(error => console.log('Error updating stats:', error));
}, 30000);

// Animación para números
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-number').forEach(element => {
        const finalValue = parseInt(element.textContent);
        let currentValue = 0;
        const increment = finalValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                element.textContent = finalValue;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(currentValue);
            }
        }, 30);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
