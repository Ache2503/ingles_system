<?php
/**
 * Centro de Control de Base de Datos
 * Punto central para análisis, actualizaciones y reportes
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🏛️ Centro de Control de Base de Datos</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .tool-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .tool-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 15px;
        }
        .tool-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .tool-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background 0.3s;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .status-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #007bff;
        }
        .reports-section {
            background: #fff3cd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #ffc107;
        }
        h1, h2, h3 { color: #2c3e50; }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏛️ Centro de Control de Base de Datos</h1>
            <p>Sistema de Gestión Completa para la Base de Datos del Sistema de Inglés</p>
            <p>Fecha: " . date('Y-m-d H:i:s') . "</p>
        </div>";

// Obtener estadísticas rápidas si es posible
try {
    require_once __DIR__ . '/../includes/db.php';
    
    $tablesCount = $pdo->query("SHOW TABLES")->rowCount();
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $topicsCount = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
    $questionsCount = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    
    echo "<div class='status-section'>
            <h2>📊 Estado Actual del Sistema</h2>
            <div class='quick-stats'>
                <div class='stat-item'>
                    <span class='stat-number'>$tablesCount</span>
                    <span class='stat-label'>Tablas</span>
                </div>
                <div class='stat-item'>
                    <span class='stat-number'>$usersCount</span>
                    <span class='stat-label'>Usuarios</span>
                </div>
                <div class='stat-item'>
                    <span class='stat-number'>$topicsCount</span>
                    <span class='stat-label'>Temas</span>
                </div>
                <div class='stat-item'>
                    <span class='stat-number'>$questionsCount</span>
                    <span class='stat-label'>Preguntas</span>
                </div>
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='status-section'>
            <h2>⚠️ Estado de Conexión</h2>
            <p>No se pudo conectar a la base de datos: " . $e->getMessage() . "</p>
          </div>";
}

echo "<div class='tools-grid'>";

// Herramientas principales
$tools = [
    [
        'icon' => '🔍',
        'title' => 'Analizador de Base de Datos',
        'description' => 'Identifica tablas y columnas faltantes. Analiza la estructura completa del sistema y detecta inconsistencias.',
        'file' => 'database_analyzer.php',
        'btn_class' => 'btn-primary',
        'btn_text' => 'Analizar BD'
    ],
    [
        'icon' => '🚀',
        'title' => 'Actualizador de Base de Datos',
        'description' => 'Aplica todas las mejoras detectadas. Crea tablas faltantes, añade columnas y actualiza la estructura.',
        'file' => 'database_updater.php',
        'btn_class' => 'btn-success',
        'btn_text' => 'Actualizar BD'
    ],
    [
        'icon' => '💾',
        'title' => 'Generador de Backups',
        'description' => 'Crea respaldos completos de la base de datos antes de realizar cambios importantes.',
        'file' => 'backup_database.php',
        'btn_class' => 'btn-warning',
        'btn_text' => 'Crear Backup'
    ],
    [
        'icon' => '📝',
        'title' => 'Generador de Migraciones',
        'description' => 'Genera scripts SQL para aplicar cambios de forma controlada y versionada.',
        'file' => 'generate_migration.php',
        'btn_class' => 'btn-info',
        'btn_text' => 'Generar SQL'
    ],
    [
        'icon' => '🔑',
        'title' => 'Credenciales de Admin',
        'description' => 'Verifica y muestra las credenciales del administrador del sistema.',
        'file' => 'admin_credentials.php',
        'btn_class' => 'btn-danger',
        'btn_text' => 'Ver Credenciales'
    ],
    [
        'icon' => '🧪',
        'title' => 'Verificador del Sistema',
        'description' => 'Ejecuta pruebas completas del sistema y genera reportes detallados.',
        'file' => 'system_verifier.php',
        'btn_class' => 'btn-success',
        'btn_text' => 'Verificar Sistema'
    ],
    [
        'icon' => '🔐',
        'title' => 'Verificador de Autenticación',
        'description' => 'Comprueba el estado del sistema de autenticación y protección de sesiones.',
        'file' => 'auth_verifier.php',
        'btn_class' => 'btn-primary',
        'btn_text' => 'Verificar Auth'
    ],
    [
        'icon' => '🚪',
        'title' => 'Verificador de Logout',
        'description' => 'Prueba la funcionalidad de cierre de sesión desde diferentes ubicaciones.',
        'file' => 'logout_verifier.php',
        'btn_class' => 'btn-info',
        'btn_text' => 'Verificar Logout'
    ]
];

foreach ($tools as $tool) {
    echo "<div class='tool-card'>
            <div class='tool-icon'>{$tool['icon']}</div>
            <div class='tool-title'>{$tool['title']}</div>
            <div class='tool-description'>{$tool['description']}</div>
            <div style='text-align: center;'>
                <a href='{$tool['file']}' class='btn {$tool['btn_class']}'>{$tool['btn_text']}</a>
            </div>
          </div>";
}

echo "</div>";

// Sección de reportes
echo "<div class='reports-section'>
        <h2>📋 Gestión de Reportes</h2>
        <p>Todos los reportes se guardan automáticamente en las siguientes ubicaciones:</p>
        <ul>
            <li><strong>Reportes de BD:</strong> <code>tests/reports/</code></li>
            <li><strong>Backups:</strong> <code>tests/backups/</code></li>
            <li><strong>Migraciones:</strong> <code>tests/migrations/</code></li>
        </ul>";

// Verificar si existen reportes
$reportsDir = __DIR__ . '/reports';
$backupsDir = __DIR__ . '/backups';
$migrationsDir = __DIR__ . '/migrations';

$reportCount = is_dir($reportsDir) ? count(glob($reportsDir . '/*')) : 0;
$backupCount = is_dir($backupsDir) ? count(glob($backupsDir . '/*')) : 0;
$migrationCount = is_dir($migrationsDir) ? count(glob($migrationsDir . '/*')) : 0;

echo "<div class='quick-stats'>
        <div class='stat-item'>
            <span class='stat-number'>$reportCount</span>
            <span class='stat-label'>Reportes</span>
        </div>
        <div class='stat-item'>
            <span class='stat-number'>$backupCount</span>
            <span class='stat-label'>Backups</span>
        </div>
        <div class='stat-item'>
            <span class='stat-number'>$migrationCount</span>
            <span class='stat-label'>Migraciones</span>
        </div>
      </div>";

echo "</div>";

// Enlaces del sistema
echo "<div style='text-align: center; margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;'>
        <h3>🔗 Enlaces del Sistema</h3>
        <a href='../index.php' class='btn btn-primary'>🏠 Sistema Principal</a>
        <a href='../auth/login.php' class='btn btn-success'>🔑 Login</a>
        <a href='../admin/index.php' class='btn btn-warning'>👑 Panel Admin</a>
        <a href='../docs/' class='btn btn-info'>📚 Documentación</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
