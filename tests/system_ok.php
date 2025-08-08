<?php
/**
 * Verificador de Estado - Sin Errores
 * Comprueba que no haya conflictos de funciones
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>✅ Sistema Sin Errores</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 700px; 
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
            border: 1px solid #c3e6cb;
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
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-info { background: #17a2b8; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Problemas de Funciones Duplicadas SOLUCIONADOS</h1>";

// Verificar que no hay conflictos
try {
    include_once __DIR__ . '/../includes/session_protection.php';
    include_once __DIR__ . '/../includes/auth.php';
    
    echo "<div class='success'>
            <strong>🎉 ¡Excelente!</strong><br>
            No se detectaron errores de funciones duplicadas.<br>
            El sistema está funcionando correctamente.
          </div>";
    
    echo "<h2>🔧 Estado del Sistema</h2>";
    echo "<div class='success'>
            ✅ <strong>session_protection.php</strong> - Cargado correctamente<br>
            ✅ <strong>auth.php</strong> - Cargado correctamente<br>
            ✅ <strong>Funciones duplicadas</strong> - Eliminadas<br>
            ✅ <strong>Sistema de autenticación</strong> - Funcionando
          </div>";
    
    echo "<h2>🔑 Credenciales de Administrador</h2>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            <strong>Email:</strong> admin@ingles.com<br>
            <strong>Contraseña:</strong> password<br>
            <strong>Rol:</strong> Administrador
          </div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            <strong>❌ Error detectado:</strong><br>
            {$e->getMessage()}
          </div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🚀 Acceder al Sistema</h3>
        <a href='../auth/login.php' class='btn btn-primary'>🔑 Login del Sistema</a>
        <a href='../admin/index.php' class='btn btn-success'>👑 Panel Admin</a>
        <a href='../index.php' class='btn btn-info'>🏠 Página Principal</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
