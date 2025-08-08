<?php
/**
 * Script para Aplicar Protección de Sesión a Todos los Archivos
 * Añade protección automáticamente a archivos PHP que la necesiten
 */

$baseDir = __DIR__ . '/../';

// Archivos que requieren protección de usuario autenticado
$userProtectedFiles = [
    'pages/dashboard.php',
    'pages/notifications.php',
    'pages/bookmarks.php',
    'pages/search.php',
    'pages/recommendations.php',
    'pages/settings.php',
    'pages/topic_detail.php'
];

// Archivos que requieren protección de administrador
$adminProtectedFiles = [
    'admin/users.php',
    'admin/topics.php',
    'admin/questions.php',
    'admin/verbs.php'
];

// APIs que requieren protección
$apiProtectedFiles = [
    'api/check_achievements.php',
    'api/get_user_stats.php',
    'api/update_progress.php',
    'api/test.php'
];

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Aplicando Protección de Sesión</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Aplicando Protección de Sesión</h1>";

/**
 * Función para añadir protección a un archivo
 */
function addProtectionToFile($filePath, $protectionType = 'user') {
    global $baseDir;
    
    $fullPath = $baseDir . $filePath;
    
    if (!file_exists($fullPath)) {
        echo "<div class='error'>❌ Archivo no encontrado: {$filePath}</div>";
        return false;
    }
    
    $content = file_get_contents($fullPath);
    
    // Verificar si ya tiene protección
    if (strpos($content, 'session_protection.php') !== false) {
        echo "<div class='info'>ℹ️ Ya protegido: {$filePath}</div>";
        return true;
    }
    
    // Determinar el tipo de protección a añadir
    if ($protectionType === 'admin') {
        $protectionCode = "<?php
/**
 * {$filePath} - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('admin_" . basename($filePath, '.php') . "', 'Admin accedió a " . basename($filePath) . "');

";
    } elseif ($protectionType === 'api') {
        $protectionCode = "<?php
/**
 * API {$filePath} - Solo usuarios autenticados
 */

// Protección de sesión para API
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Headers para API
header('Content-Type: application/json');

// Log de actividad API
logUserActivity('api_" . basename($filePath, '.php') . "', 'Usuario accedió a API " . basename($filePath) . "');

";
    } else {
        $protectionCode = "<?php
/**
 * {$filePath} - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('" . basename($filePath, '.php') . "', 'Usuario accedió a " . basename($filePath) . "');

";
    }
    
    // Encontrar donde insertar el código de protección
    if (strpos($content, '<?php') === 0) {
        // Reemplazar el <?php inicial
        $content = $protectionCode . substr($content, 5);
    } else {
        // Añadir al principio
        $content = $protectionCode . $content;
    }
    
    // Guardar el archivo
    if (file_put_contents($fullPath, $content)) {
        echo "<div class='success'>✅ Protegido: {$filePath}</div>";
        return true;
    } else {
        echo "<div class='error'>❌ Error al proteger: {$filePath}</div>";
        return false;
    }
}

// Aplicar protección a archivos de usuario
echo "<h2>👤 Protegiendo Archivos de Usuario</h2>";
foreach ($userProtectedFiles as $file) {
    addProtectionToFile($file, 'user');
}

// Aplicar protección a archivos de administrador
echo "<h2>👑 Protegiendo Archivos de Administrador</h2>";
foreach ($adminProtectedFiles as $file) {
    addProtectionToFile($file, 'admin');
}

// Aplicar protección a APIs
echo "<h2>🌐 Protegiendo APIs</h2>";
foreach ($apiProtectedFiles as $file) {
    addProtectionToFile($file, 'api');
}

echo "<h2>✅ Proceso Completado</h2>";
echo "<div class='success'>
    <strong>Protección aplicada exitosamente!</strong><br>
    Todos los archivos ahora requieren autenticación apropiada.<br><br>
    <a href='../tests/system_verifier.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Verificar Sistema</a>
    <a href='../index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🏠 Ir al Sistema</a>
</div>";

echo "    </div>
</body>
</html>";
?>
