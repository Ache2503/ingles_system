<?php
/**
 * pages/settings.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('settings', 'Usuario accedió a settings.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener configuración actual del usuario
$settingsStmt = $pdo->prepare("
    SELECT * FROM user_settings WHERE user_id = ?
");
$settingsStmt->execute([$userId]);
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Si no existe configuración, crear una por defecto
if (!$settings) {
    $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)")->execute([$userId]);
    $settings = [
        'notifications_enabled' => 1,
        'email_reminders' => 1,
        'study_reminder_time' => '19:00:00',
        'preferred_language' => 'es',
        'theme' => 'light',
        'difficulty_preference' => 'adaptive'
    ];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $updateData = [
            'notifications_enabled' => isset($_POST['notifications_enabled']) ? 1 : 0,
            'email_reminders' => isset($_POST['email_reminders']) ? 1 : 0,
            'study_reminder_time' => $_POST['study_reminder_time'],
            'preferred_language' => $_POST['preferred_language'],
            'theme' => $_POST['theme'],
            'difficulty_preference' => $_POST['difficulty_preference']
        ];
        
        $updateStmt = $pdo->prepare("
            UPDATE user_settings SET 
                notifications_enabled = ?, 
                email_reminders = ?, 
                study_reminder_time = ?, 
                preferred_language = ?, 
                theme = ?, 
                difficulty_preference = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $updateStmt->execute([
            $updateData['notifications_enabled'],
            $updateData['email_reminders'],
            $updateData['study_reminder_time'],
            $updateData['preferred_language'],
            $updateData['theme'],
            $updateData['difficulty_preference'],
            $userId
        ]);
        
        $success = "Configuración actualizada correctamente";
        $settings = $updateData; // Actualizar array local
    }
    
    if (isset($_POST['update_profile'])) {
        $userData = [
            'bio' => trim($_POST['bio']),
            'timezone' => $_POST['timezone']
        ];
        
        $profileStmt = $pdo->prepare("
            UPDATE users SET bio = ?, timezone = ? WHERE user_id = ?
        ");
        $profileStmt->execute([$userData['bio'], $userData['timezone'], $userId]);
        
        $success = "Perfil actualizado correctamente";
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verificar contraseña actual
        $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (password_verify($currentPassword, $user['password_hash'])) {
            if ($newPassword === $confirmPassword && strlen($newPassword) >= 6) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $passwordStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $passwordStmt->execute([$newHash, $userId]);
                $success = "Contraseña cambiada correctamente";
            } else {
                $error = "Las contraseñas no coinciden o son muy cortas";
            }
        } else {
            $error = "Contraseña actual incorrecta";
        }
    }
}

// Obtener datos del usuario
$userStmt = $pdo->prepare("SELECT username, email, bio, timezone, created_at FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .settings-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .settings-tabs {
        display: flex;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 2rem;
    }
    
    .tab-button {
        padding: 1rem 2rem;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 1rem;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    
    .tab-button.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }
    
    .tab-content {
        display: none;
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .tab-content.active {
        display: block;
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-section h3 {
        color: #2c3e50;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #495057;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    
    .setting-card {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #007bff;
    }
    
    .setting-card h4 {
        margin: 0 0 1rem 0;
        color: #2c3e50;
    }
    
    .theme-preview {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .theme-option {
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .theme-option.selected {
        border-color: #007bff;
        background: #f0f8ff;
    }
    
    .theme-light {
        background: linear-gradient(45deg, #fff, #f8f9fa);
        color: #333;
    }
    
    .theme-dark {
        background: linear-gradient(45deg, #2c3e50, #34495e);
        color: #fff;
    }
    
    .theme-auto {
        background: linear-gradient(45deg, #fff 50%, #2c3e50 50%);
        color: #333;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-item {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 1rem;
        border-radius: 10px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .danger-zone {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .danger-zone h3 {
        color: #e53e3e;
        margin-bottom: 1rem;
    }
    
    .btn-danger {
        background: #e53e3e;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }
    
    .btn-danger:hover {
        background: #c53030;
    }
</style>

<div class="settings-container">
    <h1>⚙️ Configuración</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="tab-button active" onclick="showTab('profile')">👤 Perfil</button>
        <button class="tab-button" onclick="showTab('preferences')">🎛️ Preferencias</button>
        <button class="tab-button" onclick="showTab('notifications')">🔔 Notificaciones</button>
        <button class="tab-button" onclick="showTab('security')">🔒 Seguridad</button>
        <button class="tab-button" onclick="showTab('stats')">📊 Estadísticas</button>
    </div>
    
    <!-- Tab: Perfil -->
    <div id="profile-tab" class="tab-content active">
        <div class="form-section">
            <h3>📝 Información Personal</h3>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="stat-label">Usuario</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= htmlspecialchars($user['email']) ?></div>
                    <div class="stat-label">Email</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                    <div class="stat-label">Miembro desde</div>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Biografía</label>
                    <textarea name="bio" rows="4" placeholder="Cuéntanos un poco sobre ti..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Zona Horaria</label>
                    <select name="timezone">
                        <option value="America/Mexico_City" <?= ($user['timezone'] ?? 'America/Mexico_City') === 'America/Mexico_City' ? 'selected' : '' ?>>Ciudad de México (GMT-6)</option>
                        <option value="America/New_York" <?= ($user['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>Nueva York (GMT-5)</option>
                        <option value="Europe/Madrid" <?= ($user['timezone'] ?? '') === 'Europe/Madrid' ? 'selected' : '' ?>>Madrid (GMT+1)</option>
                        <option value="America/Los_Angeles" <?= ($user['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' ?>>Los Ángeles (GMT-8)</option>
                    </select>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    Actualizar Perfil
                </button>
            </form>
        </div>
    </div>
    
    <!-- Tab: Preferencias -->
    <div id="preferences-tab" class="tab-content">
        <form method="POST">
            <div class="settings-grid">
                <div class="setting-card">
                    <h4>🌍 Idioma Preferido</h4>
                    <select name="preferred_language">
                        <option value="es" <?= $settings['preferred_language'] === 'es' ? 'selected' : '' ?>>Español</option>
                        <option value="en" <?= $settings['preferred_language'] === 'en' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                
                <div class="setting-card">
                    <h4>🎯 Dificultad Preferida</h4>
                    <select name="difficulty_preference">
                        <option value="adaptive" <?= $settings['difficulty_preference'] === 'adaptive' ? 'selected' : '' ?>>Adaptativa</option>
                        <option value="easy" <?= $settings['difficulty_preference'] === 'easy' ? 'selected' : '' ?>>Fácil</option>
                        <option value="medium" <?= $settings['difficulty_preference'] === 'medium' ? 'selected' : '' ?>>Medio</option>
                        <option value="hard" <?= $settings['difficulty_preference'] === 'hard' ? 'selected' : '' ?>>Difícil</option>
                    </select>
                </div>
                
                <div class="setting-card">
                    <h4>⏰ Recordatorio de Estudio</h4>
                    <input type="time" name="study_reminder_time" value="<?= $settings['study_reminder_time'] ?>">
                    <small>Hora diaria para recordarte estudiar</small>
                </div>
            </div>
            
            <div class="form-section">
                <h3>🎨 Tema de la Interfaz</h3>
                <div class="theme-preview">
                    <div class="theme-option theme-light <?= $settings['theme'] === 'light' ? 'selected' : '' ?>" 
                         onclick="selectTheme('light')">
                        <input type="radio" name="theme" value="light" <?= $settings['theme'] === 'light' ? 'checked' : '' ?> style="display:none;">
                        ☀️ Claro
                    </div>
                    <div class="theme-option theme-dark <?= $settings['theme'] === 'dark' ? 'selected' : '' ?>" 
                         onclick="selectTheme('dark')">
                        <input type="radio" name="theme" value="dark" <?= $settings['theme'] === 'dark' ? 'checked' : '' ?> style="display:none;">
                        🌙 Oscuro
                    </div>
                    <div class="theme-option theme-auto <?= $settings['theme'] === 'auto' ? 'selected' : '' ?>" 
                         onclick="selectTheme('auto')">
                        <input type="radio" name="theme" value="auto" <?= $settings['theme'] === 'auto' ? 'checked' : '' ?> style="display:none;">
                        🔄 Auto
                    </div>
                </div>
            </div>
            
            <button type="submit" name="update_settings" class="btn btn-primary">
                Guardar Preferencias
            </button>
        </form>
    </div>
    
    <!-- Tab: Notificaciones -->
    <div id="notifications-tab" class="tab-content">
        <form method="POST">
            <div class="form-section">
                <h3>🔔 Configuración de Notificaciones</h3>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="notifications_enabled" name="notifications_enabled" 
                           <?= $settings['notifications_enabled'] ? 'checked' : '' ?>>
                    <label for="notifications_enabled">Habilitar notificaciones en el sistema</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="email_reminders" name="email_reminders" 
                           <?= $settings['email_reminders'] ? 'checked' : '' ?>>
                    <label for="email_reminders">Recibir recordatorios por email</label>
                </div>
                
                <p class="text-muted">
                    Las notificaciones incluyen logros desbloqueados, recordatorios de estudio, 
                    y actualizaciones importantes del sistema.
                </p>
            </div>
            
            <button type="submit" name="update_settings" class="btn btn-primary">
                Guardar Configuración
            </button>
        </form>
    </div>
    
    <!-- Tab: Seguridad -->
    <div id="security-tab" class="tab-content">
        <div class="form-section">
            <h3>🔒 Cambiar Contraseña</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Contraseña Actual</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    Cambiar Contraseña
                </button>
            </form>
        </div>
        
        <div class="danger-zone">
            <h3>⚠️ Zona de Peligro</h3>
            <p>Las siguientes acciones son irreversibles. Procede con precaución.</p>
            <button type="button" class="btn-danger" onclick="confirmDeleteAccount()">
                Eliminar Cuenta
            </button>
        </div>
    </div>
    
    <!-- Tab: Estadísticas -->
    <div id="stats-tab" class="tab-content">
        <?php
        // Obtener estadísticas del usuario
        $statsQuery = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT qh.topic_id) as topics_attempted,
                COUNT(qh.history_id) as total_quizzes,
                AVG(qh.score) as avg_score,
                MAX(qh.score) as best_score,
                SUM(qh.duration) as total_time,
                COUNT(DISTINCT DATE(qh.attempt_date)) as study_days
            FROM quiz_history qh 
            WHERE qh.user_id = ?
        ");
        $statsQuery->execute([$userId]);
        $userStats = $statsQuery->fetch(PDO::FETCH_ASSOC);
        
        $achievementsCount = $pdo->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = ?")->execute([$userId]);
        $achievementsCount = $pdo->fetchColumn();
        ?>
        
        <div class="form-section">
            <h3>📊 Tus Estadísticas</h3>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $userStats['topics_attempted'] ?: 0 ?></div>
                    <div class="stat-label">Temas Intentados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $userStats['total_quizzes'] ?: 0 ?></div>
                    <div class="stat-label">Quizzes Completados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= round($userStats['avg_score'] ?: 0, 1) ?>%</div>
                    <div class="stat-label">Puntuación Promedio</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= round($userStats['best_score'] ?: 0, 1) ?>%</div>
                    <div class="stat-label">Mejor Puntuación</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= round(($userStats['total_time'] ?: 0) / 60, 1) ?>h</div>
                    <div class="stat-label">Tiempo Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $userStats['study_days'] ?: 0 ?></div>
                    <div class="stat-label">Días de Estudio</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $achievementsCount ?: 0 ?></div>
                    <div class="stat-label">Logros Obtenidos</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Mostrar tab seleccionado
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function selectTheme(theme) {
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('input').checked = true;
    
    // Aplicar tema inmediatamente (opcional)
    applyTheme(theme);
}

function applyTheme(theme) {
    document.body.className = theme === 'dark' ? 'dark-theme' : 'light-theme';
    localStorage.setItem('preferred-theme', theme);
}

function confirmDeleteAccount() {
    if (confirm('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.')) {
        if (confirm('Esta acción eliminará todos tus datos, progreso y logros. ¿Continuar?')) {
            // Implementar eliminación de cuenta
            window.location.href = 'delete_account.php';
        }
    }
}

// Aplicar tema guardado al cargar
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('preferred-theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
