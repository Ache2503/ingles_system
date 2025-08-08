<?php
/**
 * Prueba Rápida de Conectividad
 * Verifica que las funciones básicas funcionen correctamente
 */

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Prueba Rápida del Sistema</title>
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
        <h1>🧪 Prueba Rápida del Sistema</h1>";

// 1. Verificar archivos de configuración
echo "<h2>📁 Verificando Archivos</h2>";

$requiredFiles = [
    'includes/config.php',
    'includes/db.php',
    'includes/auth.php'
];

foreach ($requiredFiles as $file) {
    $filePath = __DIR__ . '/../' . $file;
    if (file_exists($filePath)) {
        echo "<div class='success'>✅ {$file} - Existe</div>";
        
        // Incluir el archivo
        try {
            require_once $filePath;
            echo "<div class='success'>✅ {$file} - Incluido correctamente</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ {$file} - Error al incluir: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ {$file} - No encontrado</div>";
    }
}

// 2. Verificar función getDBConnection
echo "<h2>🔌 Verificando Conectividad</h2>";

if (function_exists('getDBConnection')) {
    echo "<div class='success'>✅ Función getDBConnection() - Disponible</div>";
    
    try {
        $pdo = getDBConnection();
        echo "<div class='success'>✅ Conexión a BD - Exitosa</div>";
        
        // Probar una consulta simple
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "<div class='success'>✅ Consulta de prueba - Exitosa</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Función getDBConnection() - No encontrada</div>";
}

// 3. Verificar funciones de autenticación
echo "<h2>🔐 Verificando Funciones de Autenticación</h2>";

$authFunctions = ['loginUser', 'registerUser', 'isUserLoggedIn', 'getCurrentUser'];

foreach ($authFunctions as $function) {
    if (function_exists($function)) {
        echo "<div class='success'>✅ {$function}() - Disponible</div>";
    } else {
        echo "<div class='error'>❌ {$function}() - No encontrada</div>";
    }
}

// 4. Verificar sesiones
echo "<h2>🎯 Verificando Sesiones</h2>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<div class='success'>✅ Sesión iniciada correctamente</div>";
} else {
    echo "<div class='info'>ℹ️ Sesión ya activa</div>";
}

echo "<h2>🎉 Prueba Completada</h2>";
echo "<div class='info'>
    <strong>Estado del Sistema:</strong><br>
    Si todas las pruebas muestran ✅, el sistema está funcionando correctamente.<br><br>
    <a href='system_verifier.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Ejecutar Verificador Completo</a>
    <a href='comprehensive_test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🧪 Ejecutar Todas las Pruebas</a>
</div>";

echo "    </div>
</body>
</html>";
?>
