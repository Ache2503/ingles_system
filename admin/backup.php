<?php
/**
 * Sistema de Backup de Base de Datos
 * Permite crear respaldos completos de la base de datos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar permisos de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/auth/login.php');
    exit;
}

$message = '';
$error = '';

// Crear directorio de backups si no existe
$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Procesar formulario de backup
if ($_POST['action'] ?? '' === 'create_backup') {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_ingles_{$timestamp}.sql";
        $filepath = $backupDir . $filename;
        
        // Comando mysqldump
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;
        
        $command = "mysqldump --host={$host} --user={$user} --password={$pass} --single-transaction --routines --triggers {$dbname} > {$filepath}";
        
        // Ejecutar backup
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($filepath)) {
            $size = filesize($filepath);
            $sizeFormatted = formatBytes($size);
            $message = "✅ Backup creado exitosamente: {$filename} ({$sizeFormatted})";
            
            // Registrar en log
            logBackupAction($_SESSION['user_id'], 'create', $filename, $size);
        } else {
            $error = "❌ Error al crear el backup. Verificar configuración de MySQL.";
        }
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Procesar eliminación de backup
if ($_POST['action'] ?? '' === 'delete_backup') {
    $filename = $_POST['filename'] ?? '';
    if ($filename && file_exists($backupDir . $filename)) {
        if (unlink($backupDir . $filename)) {
            $message = "✅ Backup eliminado: {$filename}";
            logBackupAction($_SESSION['user_id'], 'delete', $filename, 0);
        } else {
            $error = "❌ Error al eliminar el backup";
        }
    }
}

// Procesar descarga de backup
if ($_GET['action'] ?? '' === 'download' && isset($_GET['file'])) {
    $filename = $_GET['file'];
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $error = "❌ Archivo no encontrado";
    }
}

// Obtener lista de backups existentes
$backups = [];
if (is_dir($backupDir)) {
    $files = glob($backupDir . "backup_*.sql");
    foreach ($files as $file) {
        $filename = basename($file);
        $backups[] = [
            'filename' => $filename,
            'size' => filesize($file),
            'date' => filemtime($file),
            'age_days' => floor((time() - filemtime($file)) / 86400)
        ];
    }
    // Ordenar por fecha (más reciente primero)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Obtener estadísticas de backup
$backupStats = [
    'total_backups' => count($backups),
    'total_size' => array_sum(array_column($backups, 'size')),
    'oldest_backup' => $backups ? min(array_column($backups, 'date')) : null,
    'newest_backup' => $backups ? max(array_column($backups, 'date')) : null
];

// Funciones auxiliares
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function logBackupAction($userId, $action, $filename, $size) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO backup_log (user_id, action, filename, file_size, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $filename, $size]);
    } catch (Exception $e) {
        // Si la tabla no existe, la creamos
        createBackupLogTable();
        $stmt->execute([$userId, $action, $filename, $size]);
    }
}

function createBackupLogTable() {
    global $pdo;
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size BIGINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_date (user_id, created_at)
        )
    ");
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
.backup-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.backup-actions {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.backups-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.backups-table table {
    width: 100%;
    border-collapse: collapse;
}

.backups-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

.backups-table td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    margin: 0.25rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-info { background: #17a2b8; color: white; }
.btn-warning { background: #ffc107; color: #212529; }

.btn:hover { opacity: 0.9; transform: translateY(-1px); }

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}

.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }

.backup-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: end;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 3px;
    transition: width 0.3s ease;
}
</style>

<div class="backup-container">
    <h1>💾 Sistema de Backup</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <!-- Estadísticas de Backup -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $backupStats['total_backups'] ?></div>
            <div>Backups Totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= formatBytes($backupStats['total_size']) ?></div>
            <div>Espacio Utilizado</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?= $backupStats['newest_backup'] ? date('d/m/Y', $backupStats['newest_backup']) : 'N/A' ?>
            </div>
            <div>Último Backup</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?= $backupStats['oldest_backup'] ? floor((time() - $backupStats['oldest_backup']) / 86400) : 0 ?> días
            </div>
            <div>Backup Más Antiguo</div>
        </div>
    </div>
    
    <!-- Crear Nuevo Backup -->
    <div class="backup-actions">
        <h2>🚀 Crear Nuevo Backup</h2>
        <form method="POST" class="backup-form">
            <input type="hidden" name="action" value="create_backup">
            <div class="form-group">
                <label for="backup_type">Tipo de Backup:</label>
                <select name="backup_type" id="backup_type" class="form-control">
                    <option value="full">Backup Completo (Estructura + Datos)</option>
                    <option value="data">Solo Datos</option>
                    <option value="structure">Solo Estructura</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">💾 Crear Backup</button>
        </form>
        
        <div style="margin-top: 1rem;">
            <small>
                <strong>Nota:</strong> Los backups se crean automáticamente cada día a las 2:00 AM. 
                Este backup manual se puede usar para crear respaldos adicionales antes de cambios importantes.
            </small>
        </div>
    </div>
    
    <!-- Lista de Backups -->
    <div class="backups-table">
        <h2 style="padding: 1rem;">📋 Backups Existentes</h2>
        
        <?php if (empty($backups)): ?>
        <div style="padding: 2rem; text-align: center; color: #666;">
            No hay backups disponibles. Crea tu primer backup usando el formulario anterior.
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>📁 Archivo</th>
                    <th>📊 Tamaño</th>
                    <th>📅 Fecha de Creación</th>
                    <th>⏰ Antigüedad</th>
                    <th>🔧 Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($backup['filename']) ?></strong>
                    </td>
                    <td><?= formatBytes($backup['size']) ?></td>
                    <td><?= date('d/m/Y H:i', $backup['date']) ?></td>
                    <td>
                        <span class="badge <?= $backup['age_days'] > 30 ? 'badge-warning' : ($backup['age_days'] > 7 ? 'badge-info' : 'badge-success') ?>">
                            <?= $backup['age_days'] ?> día<?= $backup['age_days'] !== 1 ? 's' : '' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?action=download&file=<?= urlencode($backup['filename']) ?>" 
                           class="btn btn-info btn-sm">⬇️ Descargar</a>
                        
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar este backup?')">
                            <input type="hidden" name="action" value="delete_backup">
                            <input type="hidden" name="filename" value="<?= htmlspecialchars($backup['filename']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️ Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Información Adicional -->
    <div class="backup-actions" style="margin-top: 2rem;">
        <h3>ℹ️ Información Importante</h3>
        <ul>
            <li><strong>Backup Automático:</strong> El sistema crea backups automáticos diariamente</li>
            <li><strong>Retención:</strong> Los backups se mantienen por 30 días por defecto</li>
            <li><strong>Ubicación:</strong> Los backups se almacenan en <code>/backups/</code></li>
            <li><strong>Restauración:</strong> Para restaurar, contacta al administrador del sistema</li>
            <li><strong>Seguridad:</strong> Los backups contienen datos sensibles, manéjalos con cuidado</li>
        </ul>
        
        <div style="margin-top: 1rem;">
            <a href="analytics.php" class="btn btn-info">📊 Volver a Analytics</a>
            <a href="index.php" class="btn btn-primary">🏠 Panel de Admin</a>
        </div>
    </div>
</div>

<script>
// Auto-refresh cada 30 segundos
setInterval(() => {
    // Solo actualizar si no hay formularios siendo enviados
    if (!document.querySelector('form[data-submitting]')) {
        window.location.reload();
    }
}, 30000);

// Prevenir múltiples envíos
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        this.setAttribute('data-submitting', 'true');
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = '🔄 Procesando...';
        }
    });
});

// Mostrar progreso de backup (simulado)
document.querySelector('form[action=""]')?.addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    if (button) {
        button.innerHTML = '🔄 Creando backup...';
        
        // Simular progreso
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress >= 100) {
                clearInterval(interval);
                button.innerHTML = '✅ Completado';
            } else {
                button.innerHTML = `🔄 Progreso: ${Math.floor(progress)}%`;
            }
        }, 200);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
