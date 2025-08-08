<?php
/**
 * Monitoreo de Salud del Sistema
 * Verifica el estado de todos los componentes del sistema
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar permisos de admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/auth/login.php');
    exit;
}

// Clase para monitoreo del sistema
class SystemHealthMonitor {
    private $pdo;
    private $checks = [];
    private $overallHealth = 'good';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function runAllChecks() {
        $this->checkDatabaseConnection();
        $this->checkDatabaseTables();
        $this->checkDiskSpace();
        $this->checkPHPConfiguration();
        $this->checkFilePermissions();
        $this->checkSystemLoad();
        $this->checkBackupStatus();
        $this->checkUserActivity();
        $this->checkErrorLogs();
        $this->checkSecurityStatus();
        
        $this->calculateOverallHealth();
        return $this->getResults();
    }
    
    private function checkDatabaseConnection() {
        try {
            $start = microtime(true);
            $stmt = $this->pdo->query("SELECT 1");
            $time = (microtime(true) - $start) * 1000;
            
            if ($time < 100) {
                $this->addCheck('database_connection', 'good', 'Conexión a BD', "Excelente ({$time:.2f}ms)");
            } elseif ($time < 500) {
                $this->addCheck('database_connection', 'warning', 'Conexión a BD', "Lenta ({$time:.2f}ms)");
            } else {
                $this->addCheck('database_connection', 'critical', 'Conexión a BD', "Muy lenta ({$time:.2f}ms)");
            }
        } catch (Exception $e) {
            $this->addCheck('database_connection', 'critical', 'Conexión a BD', "Error: " . $e->getMessage());
        }
    }
    
    private function checkDatabaseTables() {
        $requiredTables = [
            'users', 'topics', 'questions', 'user_progress', 'quiz_history',
            'user_gamification', 'user_achievements', 'notifications'
        ];
        
        try {
            $stmt = $this->pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missingTables = array_diff($requiredTables, $existingTables);
            
            if (empty($missingTables)) {
                $this->addCheck('database_tables', 'good', 'Tablas de BD', 'Todas las tablas presentes');
            } else {
                $missing = implode(', ', $missingTables);
                $this->addCheck('database_tables', 'critical', 'Tablas de BD', "Faltan: {$missing}");
            }
            
            // Verificar integridad de datos
            $userCount = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $topicCount = $this->pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
            
            if ($userCount > 0 && $topicCount > 0) {
                $this->addCheck('data_integrity', 'good', 'Integridad de Datos', 
                    "{$userCount} usuarios, {$topicCount} temas");
            } else {
                $this->addCheck('data_integrity', 'warning', 'Integridad de Datos', 'Datos insuficientes');
            }
            
        } catch (Exception $e) {
            $this->addCheck('database_tables', 'critical', 'Tablas de BD', "Error: " . $e->getMessage());
        }
    }
    
    private function checkDiskSpace() {
        $path = __DIR__ . '/..';
        $totalSpace = disk_total_space($path);
        $freeSpace = disk_free_space($path);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;
        
        $freeGB = round($freeSpace / (1024**3), 2);
        $totalGB = round($totalSpace / (1024**3), 2);
        
        if ($usagePercent < 80) {
            $this->addCheck('disk_space', 'good', 'Espacio en Disco', 
                "{$freeGB}GB libres de {$totalGB}GB");
        } elseif ($usagePercent < 90) {
            $this->addCheck('disk_space', 'warning', 'Espacio en Disco', 
                "Solo {$freeGB}GB libres ({$usagePercent:.1f}% usado)");
        } else {
            $this->addCheck('disk_space', 'critical', 'Espacio en Disco', 
                "Crítico: Solo {$freeGB}GB libres ({$usagePercent:.1f}% usado)");
        }
    }
    
    private function checkPHPConfiguration() {
        $phpVersion = PHP_VERSION;
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (version_compare($phpVersion, '7.4', '>=') && empty($missingExtensions)) {
            $this->addCheck('php_config', 'good', 'Configuración PHP', "PHP {$phpVersion} - OK");
        } elseif (version_compare($phpVersion, '7.0', '>=')) {
            $issues = !empty($missingExtensions) ? 'Extensiones faltantes: ' . implode(', ', $missingExtensions) : 'Versión antigua';
            $this->addCheck('php_config', 'warning', 'Configuración PHP', $issues);
        } else {
            $this->addCheck('php_config', 'critical', 'Configuración PHP', "PHP {$phpVersion} muy antiguo");
        }
        
        // Verificar configuraciones importantes
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        
        $this->addCheck('php_limits', 'info', 'Límites PHP', 
            "Memoria: {$memoryLimit}, Tiempo: {$maxExecutionTime}s, Upload: {$uploadMaxFilesize}");
    }
    
    private function checkFilePermissions() {
        $criticalPaths = [
            __DIR__ . '/../backups/' => 'Directorio de backups',
            __DIR__ . '/../temp/' => 'Directorio temporal',
            __DIR__ . '/../logs/' => 'Directorio de logs'
        ];
        
        $issues = [];
        
        foreach ($criticalPaths as $path => $description) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            
            if (!is_writable($path)) {
                $issues[] = $description;
            }
        }
        
        if (empty($issues)) {
            $this->addCheck('file_permissions', 'good', 'Permisos de Archivos', 'Todos los directorios escribibles');
        } else {
            $this->addCheck('file_permissions', 'critical', 'Permisos de Archivos', 
                'Problemas en: ' . implode(', ', $issues));
        }
    }
    
    private function checkSystemLoad() {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convertir memory_limit a bytes
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        $memoryMB = round($memoryUsage / (1024*1024), 2);
        $peakMB = round($memoryPeak / (1024*1024), 2);
        
        if ($memoryPercent < 50) {
            $this->addCheck('memory_usage', 'good', 'Uso de Memoria', 
                "{$memoryMB}MB actual, {$peakMB}MB pico");
        } elseif ($memoryPercent < 80) {
            $this->addCheck('memory_usage', 'warning', 'Uso de Memoria', 
                "{$memoryMB}MB ({$memoryPercent:.1f}% del límite)");
        } else {
            $this->addCheck('memory_usage', 'critical', 'Uso de Memoria', 
                "Alto: {$memoryMB}MB ({$memoryPercent:.1f}% del límite)");
        }
    }
    
    private function checkBackupStatus() {
        $backupDir = __DIR__ . '/../backups/';
        
        if (!is_dir($backupDir)) {
            $this->addCheck('backup_status', 'warning', 'Estado de Backups', 'Directorio de backups no existe');
            return;
        }
        
        $backups = glob($backupDir . "backup_*.sql");
        
        if (empty($backups)) {
            $this->addCheck('backup_status', 'warning', 'Estado de Backups', 'No hay backups disponibles');
            return;
        }
        
        $latestBackup = max(array_map('filemtime', $backups));
        $daysSinceBackup = floor((time() - $latestBackup) / 86400);
        
        if ($daysSinceBackup <= 1) {
            $this->addCheck('backup_status', 'good', 'Estado de Backups', 
                count($backups) . " backups, último hace {$daysSinceBackup} día(s)");
        } elseif ($daysSinceBackup <= 7) {
            $this->addCheck('backup_status', 'warning', 'Estado de Backups', 
                "Último backup hace {$daysSinceBackup} días");
        } else {
            $this->addCheck('backup_status', 'critical', 'Estado de Backups', 
                "Último backup hace {$daysSinceBackup} días - MUY ANTIGUO");
        }
    }
    
    private function checkUserActivity() {
        try {
            $today = $this->pdo->query("
                SELECT COUNT(DISTINCT user_id) FROM user_progress 
                WHERE DATE(last_reviewed) = CURDATE()
            ")->fetchColumn();
            
            $thisWeek = $this->pdo->query("
                SELECT COUNT(DISTINCT user_id) FROM user_progress 
                WHERE last_reviewed >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ")->fetchColumn();
            
            $totalUsers = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
            
            $activityPercent = $totalUsers > 0 ? ($thisWeek / $totalUsers) * 100 : 0;
            
            if ($activityPercent > 30) {
                $this->addCheck('user_activity', 'good', 'Actividad de Usuarios', 
                    "{$today} hoy, {$thisWeek} esta semana ({$activityPercent:.1f}% del total)");
            } elseif ($activityPercent > 10) {
                $this->addCheck('user_activity', 'warning', 'Actividad de Usuarios', 
                    "Baja actividad: {$thisWeek} usuarios activos ({$activityPercent:.1f}%)");
            } else {
                $this->addCheck('user_activity', 'critical', 'Actividad de Usuarios', 
                    "Muy baja actividad: solo {$thisWeek} usuarios activos");
            }
            
        } catch (Exception $e) {
            $this->addCheck('user_activity', 'warning', 'Actividad de Usuarios', 
                'No se pudo verificar actividad');
        }
    }
    
    private function checkErrorLogs() {
        $logFile = __DIR__ . '/../logs/system.log';
        
        if (!file_exists($logFile)) {
            $this->addCheck('error_logs', 'good', 'Logs de Errores', 'Sin errores recientes');
            return;
        }
        
        $logSize = filesize($logFile);
        $recentErrors = 0;
        
        if ($logSize > 0) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $last100Lines = array_slice($lines, -100);
            
            foreach ($last100Lines as $line) {
                if (strpos($line, '[ERROR]') !== false || strpos($line, '[CRITICAL]') !== false) {
                    $recentErrors++;
                }
            }
        }
        
        if ($recentErrors === 0) {
            $this->addCheck('error_logs', 'good', 'Logs de Errores', 'Sin errores recientes');
        } elseif ($recentErrors < 5) {
            $this->addCheck('error_logs', 'warning', 'Logs de Errores', 
                "{$recentErrors} errores en las últimas 100 líneas");
        } else {
            $this->addCheck('error_logs', 'critical', 'Logs de Errores', 
                "{$recentErrors} errores frecuentes - revisar logs");
        }
    }
    
    private function checkSecurityStatus() {
        $securityIssues = [];
        
        // Verificar configuraciones de seguridad de PHP
        if (ini_get('display_errors')) {
            $securityIssues[] = 'display_errors activado';
        }
        
        if (!ini_get('session.cookie_httponly')) {
            $securityIssues[] = 'Cookies de sesión no HTTP-only';
        }
        
        if (!ini_get('session.use_strict_mode')) {
            $securityIssues[] = 'Modo estricto de sesión desactivado';
        }
        
        // Verificar archivos sensibles
        $sensitiveFiles = [
            __DIR__ . '/../.env',
            __DIR__ . '/../config.php',
            __DIR__ . '/../backup.sql'
        ];
        
        foreach ($sensitiveFiles as $file) {
            if (file_exists($file)) {
                $securityIssues[] = basename($file) . ' expuesto';
            }
        }
        
        if (empty($securityIssues)) {
            $this->addCheck('security_status', 'good', 'Estado de Seguridad', 'Configuración segura');
        } else {
            $level = count($securityIssues) > 2 ? 'critical' : 'warning';
            $this->addCheck('security_status', $level, 'Estado de Seguridad', 
                'Problemas: ' . implode(', ', $securityIssues));
        }
    }
    
    private function addCheck($id, $status, $name, $message) {
        $this->checks[] = [
            'id' => $id,
            'status' => $status,
            'name' => $name,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    private function calculateOverallHealth() {
        $statusCounts = ['critical' => 0, 'warning' => 0, 'good' => 0, 'info' => 0];
        
        foreach ($this->checks as $check) {
            if (isset($statusCounts[$check['status']])) {
                $statusCounts[$check['status']]++;
            }
        }
        
        if ($statusCounts['critical'] > 0) {
            $this->overallHealth = 'critical';
        } elseif ($statusCounts['warning'] > 2) {
            $this->overallHealth = 'warning';
        } else {
            $this->overallHealth = 'good';
        }
    }
    
    private function convertToBytes($value) {
        $unit = strtolower(substr($value, -1));
        $number = (int)$value;
        
        switch ($unit) {
            case 'g': return $number * 1024 * 1024 * 1024;
            case 'm': return $number * 1024 * 1024;
            case 'k': return $number * 1024;
            default: return $number;
        }
    }
    
    public function getResults() {
        return [
            'overall_health' => $this->overallHealth,
            'checks' => $this->checks,
            'summary' => [
                'total_checks' => count($this->checks),
                'critical' => count(array_filter($this->checks, fn($c) => $c['status'] === 'critical')),
                'warning' => count(array_filter($this->checks, fn($c) => $c['status'] === 'warning')),
                'good' => count(array_filter($this->checks, fn($c) => $c['status'] === 'good')),
                'info' => count(array_filter($this->checks, fn($c) => $c['status'] === 'info'))
            ]
        ];
    }
}

// Ejecutar monitoreo
$monitor = new SystemHealthMonitor($pdo);
$healthData = $monitor->runAllChecks();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
.health-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.health-overview {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    text-align: center;
}

.health-status {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.health-status.good { color: #28a745; }
.health-status.warning { color: #ffc107; }
.health-status.critical { color: #dc3545; }

.health-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.summary-item {
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}

.summary-item.critical { background: #f8d7da; color: #721c24; }
.summary-item.warning { background: #fff3cd; color: #856404; }
.summary-item.good { background: #d4edda; color: #155724; }
.summary-item.info { background: #d1ecf1; color: #0c5460; }

.checks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1rem;
}

.check-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #ddd;
}

.check-card.critical { border-left-color: #dc3545; }
.check-card.warning { border-left-color: #ffc107; }
.check-card.good { border-left-color: #28a745; }
.check-card.info { border-left-color: #17a2b8; }

.check-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.check-name {
    font-weight: 600;
    color: #333;
}

.check-status {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.check-status.critical { background: #f8d7da; color: #721c24; }
.check-status.warning { background: #fff3cd; color: #856404; }
.check-status.good { background: #d4edda; color: #155724; }
.check-status.info { background: #d1ecf1; color: #0c5460; }

.check-message {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.actions-panel {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 2rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    margin: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-warning { background: #ffc107; color: #212529; }
.btn-info { background: #17a2b8; color: white; }

.btn:hover { opacity: 0.9; transform: translateY(-1px); }

.progress-ring {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
}

.progress-ring-circle {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.refresh-info {
    text-align: center;
    margin-top: 1rem;
    color: #666;
    font-size: 0.9rem;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.live-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}
</style>

<div class="health-container">
    <h1>🔧 Estado de Salud del Sistema</h1>
    
    <!-- Resumen general -->
    <div class="health-overview">
        <div class="health-status <?= $healthData['overall_health'] ?>">
            <?php
            $statusIcons = [
                'good' => '✅',
                'warning' => '⚠️',
                'critical' => '❌'
            ];
            echo $statusIcons[$healthData['overall_health']];
            ?>
        </div>
        
        <h2>
            <?php
            $statusText = [
                'good' => 'Sistema Saludable',
                'warning' => 'Requiere Atención',
                'critical' => 'Estado Crítico'
            ];
            echo $statusText[$healthData['overall_health']];
            ?>
        </h2>
        
        <p>
            Última verificación: <?= date('d/m/Y H:i:s') ?>
            <span class="live-indicator"></span>En vivo
        </p>
        
        <div class="health-summary">
            <div class="summary-item critical">
                <div style="font-size: 1.5rem; font-weight: bold;"><?= $healthData['summary']['critical'] ?></div>
                <div>Críticos</div>
            </div>
            <div class="summary-item warning">
                <div style="font-size: 1.5rem; font-weight: bold;"><?= $healthData['summary']['warning'] ?></div>
                <div>Advertencias</div>
            </div>
            <div class="summary-item good">
                <div style="font-size: 1.5rem; font-weight: bold;"><?= $healthData['summary']['good'] ?></div>
                <div>Buenos</div>
            </div>
            <div class="summary-item info">
                <div style="font-size: 1.5rem; font-weight: bold;"><?= $healthData['summary']['info'] ?></div>
                <div>Informativos</div>
            </div>
        </div>
    </div>
    
    <!-- Verificaciones detalladas -->
    <div class="checks-grid">
        <?php foreach ($healthData['checks'] as $check): ?>
        <div class="check-card <?= $check['status'] ?>">
            <div class="check-header">
                <div class="check-name"><?= htmlspecialchars($check['name']) ?></div>
                <div class="check-status <?= $check['status'] ?>">
                    <?= ucfirst($check['status']) ?>
                </div>
            </div>
            <div class="check-message">
                <?= htmlspecialchars($check['message']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Panel de acciones -->
    <div class="actions-panel">
        <h3>🛠️ Acciones de Mantenimiento</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="?refresh=1" class="btn btn-primary">🔄 Actualizar Estado</a>
            <a href="backup.php" class="btn btn-info">💾 Crear Backup</a>
            <a href="../tests/database_control_center.php" class="btn btn-warning">🏛️ Centro de Control BD</a>
            <a href="../tests/system_verifier.php" class="btn btn-success">🧪 Verificador Sistema</a>
            <a href="send_notifications.php" class="btn btn-info">📧 Enviar Notificaciones</a>
            <a href="analytics.php" class="btn btn-primary">📊 Ver Analytics</a>
        </div>
        
        <?php if ($healthData['overall_health'] === 'critical'): ?>
        <div style="margin-top: 2rem; padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
            <strong>⚠️ ACCIÓN REQUERIDA:</strong>
            Se han detectado problemas críticos que requieren atención inmediata. 
            Revisa los elementos marcados en rojo y contacta al administrador del sistema si es necesario.
        </div>
        <?php elseif ($healthData['overall_health'] === 'warning'): ?>
        <div style="margin-top: 2rem; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">
            <strong>ℹ️ RECOMENDACIÓN:</strong>
            Se han detectado algunas advertencias. Es recomendable revisar y corregir estos elementos 
            para mantener el sistema funcionando óptimamente.
        </div>
        <?php endif; ?>
    </div>
    
    <div class="refresh-info">
        <span class="live-indicator"></span>
        La página se actualiza automáticamente cada 60 segundos para mostrar el estado más reciente.
    </div>
</div>

<script>
// Auto-refresh cada 60 segundos
let refreshInterval = setInterval(() => {
    window.location.reload();
}, 60000);

// Countdown visual
let countdown = 60;
const updateCountdown = () => {
    countdown--;
    if (countdown <= 0) {
        countdown = 60;
    }
    
    const refreshInfo = document.querySelector('.refresh-info');
    if (refreshInfo) {
        refreshInfo.innerHTML = `
            <span class="live-indicator"></span>
            Próxima actualización en ${countdown} segundos
        `;
    }
};

setInterval(updateCountdown, 1000);

// Pausar auto-refresh cuando el usuario interactúa
document.addEventListener('click', () => {
    clearInterval(refreshInterval);
    // Reactivar después de 5 minutos de inactividad
    setTimeout(() => {
        refreshInterval = setInterval(() => {
            window.location.reload();
        }, 60000);
    }, 300000);
});

// Notificaciones del sistema
<?php if ($healthData['summary']['critical'] > 0): ?>
console.warn('⚠️ Se detectaron problemas críticos en el sistema');
<?php endif; ?>

// Animaciones para las tarjetas
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.check-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
