<?php
/**
 * Panel de Administración - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Obtener información del usuario admin
$userInfo = getCurrentUserInfo();

// Validar sesión
validateSession();

// Log de actividad admin
logUserActivity('admin_access', 'Administrador accedió al panel');

try {
    // Obtener estadísticas con manejo de errores
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM topics");
    $stats['topics'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM questions");
    $stats['questions'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM irregular_verbs");
    $stats['verbs'] = $stmt->fetchColumn();
    
    // Obtener actividad reciente
    $stmt = $pdo->query("
        SELECT u.username, t.title, up.score, up.last_reviewed 
        FROM user_progress up
        JOIN users u ON up.user_id = u.user_id
        JOIN topics t ON up.topic_id = t.topic_id
        ORDER BY up.last_reviewed DESC LIMIT 5
    ");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error en el panel de administración: " . $e->getMessage());
    $error = "Ocurrió un error al cargar los datos. Por favor, inténtalo de nuevo más tarde.";
}

require_once __DIR__ . '/../includes/admin_header.php';
?>
<style>
    
    :root {
        --primary-color: #4361ee;
        --primary-light: #4895ef;
        --secondary-color: #3a0ca3;
        --secondary-light: #4cc9f0;
        --success-color: #4ade80;
        --warning-color: #fbbf24;
        --danger-color: #f87171;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --gray-color: #6c757d;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        color: var(--dark-color);
        background-color: #f5f7fa;
        min-height: 100vh;
    }
    
    .admin-container h1 {
        color: var(--secondary-color);
        text-align: center;
        margin-bottom: 2.5rem;
        font-size: 2.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        position: relative;
        padding-bottom: 1rem;
    }
    
    .admin-container h1::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(to right, var(--primary-light), var(--secondary-light));
        border-radius: 2px;
    }
    
    .admin-container h2 {
        color: var(--secondary-color);
        margin: 2rem 0 1.5rem;
        font-size: 1.75rem;
        font-weight: 600;
        padding-bottom: 0.75rem;
        position: relative;
    }
    
    .admin-container h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-light));
        border-radius: 3px;
    }
    
    .admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.75rem;
        box-shadow: var(--shadow-md);
        text-align: center;
        transition: var(--transition);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(to right, var(--primary-light), var(--secondary-light));
    }
    
    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    
    .stat-card h3 {
        color: var(--gray-color);
        margin-top: 0;
        margin-bottom: 1rem;
        font-size: 1.1rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .stat-card p {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 1.5rem 0;
        position: relative;
        display: inline-block;
    }
    
    .stat-card p::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: linear-gradient(to right, var(--primary-light), var(--secondary-light));
        border-radius: 3px;
    }
    
    .stat-card a {
        display: inline-block;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        border: none;
        cursor: pointer;
    }
    
    .stat-card a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        background: linear-gradient(to right, var(--primary-light), var(--secondary-light));
    }
    
    .recent-activity {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .recent-activity:hover {
        box-shadow: var(--shadow-lg);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
        font-size: 0.95rem;
    }
    
    th, td {
        padding: 1rem 1.25rem;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    th {
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    tr:hover {
        background-color: #f1f3f5;
    }
    
    .alert {
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        border-radius: 8px;
        font-weight: 500;
        box-shadow: var(--shadow-sm);
        border-left: 4px solid transparent;
    }
    
    .alert-danger {
        background-color: #fff5f5;
        color: #dc2626;
        border-left-color: #dc2626;
    }
    
    /* Efecto para las celdas de puntuación */
    .score-cell {
        font-weight: 600;
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .admin-container {
            padding: 1.5rem;
        }
        
        .admin-stats {
            grid-template-columns: 1fr;
        }
        
        .stat-card p {
            font-size: 2.5rem;
        }
    }
</style>

<div class="admin-container">
    <h1>Panel de Administración</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="admin-stats">
        <div class="stat-card">
            <h3>Usuarios</h3>
            <p><?= isset($stats['users']) ? $stats['users'] : '0' ?></p>
            <a href="users.php">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Temas</h3>
            <p><?= isset($stats['topics']) ? $stats['topics'] : '0' ?></p>
            <a href="topics.php">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Preguntas</h3>
            <p><?= isset($stats['questions']) ? $stats['questions'] : '0' ?></p>
            <a href="questions.php">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Verbos</h3>
            <p><?= isset($stats['verbs']) ? $stats['verbs'] : '0' ?></p>
            <a href="verbs.php">Gestionar</a>
        </div>
    </div>
    
    <div class="recent-activity">
        <h2>Actividad Reciente</h2>
        <?php if (empty($activities)): ?>
            <p>No hay actividad reciente para mostrar.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Tema</th>
                        <th>Puntuación</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['username']) ?></td>
                        <td><?= htmlspecialchars($activity['title']) ?></td>
                        <td>
                            <span style="color: <?= $activity['score'] >= 70 ? 'var(--success-color)' : ($activity['score'] >= 50 ? 'orange' : 'var(--danger-color)') ?>">
                                <?= htmlspecialchars($activity['score']) ?>%
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($activity['last_reviewed'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>