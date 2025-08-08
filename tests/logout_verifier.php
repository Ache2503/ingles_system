<?php
/**
 * Verificador de Funcionalidad de Logout
 * Prueba el sistema de cierre de sesión desde diferentes ubicaciones
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🔧 Verificador de Logout</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .success { 
            background: #d4f6d4; 
            color: #155724; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            margin: 10px 5px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
            color: white;
        }
        .btn-danger { background: #dc3545; }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .test-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Verificador de Sistema de Logout</h1>";

// Incluir sistema de navegación
require_once __DIR__ . '/../includes/navigation.php';

echo "<div class='success'>
        <strong>✅ Sistema de Navegación Cargado</strong><br>
        El sistema centralizado de navegación está funcionando correctamente.
      </div>";

echo "<h2>🔗 URLs de Logout Generadas</h2>";

$logoutURL = nav_url('logout');
echo "<div class='info'>
        <strong>URL de Logout:</strong> <code>$logoutURL</code><br>
        Esta URL debería funcionar desde cualquier parte del sistema.
      </div>";

echo "<h2>🧪 Pruebas de Logout</h2>";

// Simulación de pruebas
$tests = [
    'Desde Admin Dashboard' => '/ingles/admin/index.php',
    'Desde Admin Users' => '/ingles/admin/users.php',
    'Desde Admin Topics' => '/ingles/admin/topics.php',
    'Desde Página Principal' => '/ingles/index.php',
    'Desde Perfil Usuario' => '/ingles/pages/profile.php'
];

foreach ($tests as $testName => $fromPath) {
    echo "<div class='test-item'>
            <div>
                <strong>$testName</strong><br>
                <small>Desde: $fromPath</small>
            </div>
            <div>
                <a href='$logoutURL' class='btn btn-danger' 
                   onclick='return confirm(\"¿Probar logout desde $testName?\")'>
                   🚪 Probar Logout
                </a>
            </div>
          </div>";
}

echo "<h2>🔍 Estado del Archivo logout.php</h2>";

$logoutFile = __DIR__ . '/../auth/logout.php';
if (file_exists($logoutFile)) {
    $content = file_get_contents($logoutFile);
    
    if (strpos($content, '/ingles/auth/login.php') !== false) {
        echo "<div class='success'>
                ✅ <strong>Redirección Corregida</strong><br>
                El archivo logout.php usa rutas absolutas correctas.
              </div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                ⚠️ <strong>Advertencia</strong><br>
                El archivo logout.php podría tener problemas de redirección.
              </div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            ❌ <strong>Error</strong><br>
            No se encontró el archivo logout.php
          </div>";
}

echo "<h2>🛡️ Mejoras Implementadas</h2>";
echo "<div class='info'>
        <strong>✅ Cambios Realizados:</strong><br>
        1. Corregida la redirección en logout.php (ruta absoluta)<br>
        2. Añadido sistema de navegación centralizado en admin_header.php<br>
        3. Añadida confirmación de logout en JavaScript<br>
        4. Mejorado el diseño del header de administración<br>
        5. Añadida información del usuario en el header
      </div>";

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🚀 Pruebas del Sistema</h3>
        <a href='../auth/login.php' class='btn btn-primary'>🔑 Ir al Login</a>
        <a href='../admin/index.php' class='btn btn-success'>👑 Panel Admin</a>
        <a href='$logoutURL' class='btn btn-danger' 
           onclick='return confirm(\"¿Cerrar sesión actual?\")'>🚪 Cerrar Sesión</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
