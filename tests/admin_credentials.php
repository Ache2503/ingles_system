<?php
/**
 * Verificador de Credenciales de Administrador
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🔑 Credenciales de Administrador</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #28a745;
            margin: 20px 0;
        }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .value {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 8px;
            border-radius: 4px;
            color: #2c3e50;
            font-size: 1.1em;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .success { background: #d4f6d4; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔑 Credenciales de Administrador</h1>
            <p>Información de acceso al panel administrativo</p>
        </div>";

try {
    // Conexión a la base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=ingles_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar usuario administrador
    $stmt = $pdo->prepare('SELECT username, email, role, created_at FROM users WHERE role = ?');
    $stmt->execute(['admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<div class='status success'>
                <strong>✅ Usuario administrador encontrado en la base de datos</strong>
              </div>";
        
        echo "<div class='credentials'>
                <h3>📋 Credenciales de Acceso</h3>
                
                <div class='credential-item'>
                    <div class='label'>👤 Usuario:</div>
                    <div class='value'>{$admin['username']}</div>
                </div>
                
                <div class='credential-item'>
                    <div class='label'>📧 Email:</div>
                    <div class='value'>{$admin['email']}</div>
                </div>
                
                <div class='credential-item'>
                    <div class='label'>🔐 Contraseña:</div>
                    <div class='value'>password</div>
                </div>
                
                <div class='credential-item'>
                    <div class='label'>👑 Rol:</div>
                    <div class='value'>{$admin['role']}</div>
                </div>
                
                <div class='credential-item'>
                    <div class='label'>📅 Creado:</div>
                    <div class='value'>{$admin['created_at']}</div>
                </div>
              </div>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 20px 0;'>
                <strong>⚠️ Importante:</strong><br>
                La contraseña predeterminada es '<strong>password</strong>'. Se recomienda cambiarla por seguridad.
              </div>";
        
    } else {
        echo "<div class='status error'>
                <strong>❌ No se encontró usuario administrador</strong><br>
                Ejecuta el script de instalación para crear el usuario admin.
              </div>";
    }
    
    // Verificar total de usuarios
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
            <strong>📊 Estadísticas:</strong><br>
            Total de usuarios en el sistema: <strong>$totalUsers</strong>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='status error'>
            <strong>❌ Error de conexión:</strong><br>
            {$e->getMessage()}<br><br>
            Asegúrate de que XAMPP esté ejecutándose y la base de datos esté configurada.
          </div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🚀 Accesos Rápidos</h3>
        <a href='../auth/login.php' class='btn btn-primary'>🔑 Ir al Login</a>
        <a href='../admin/index.php' class='btn btn-success'>👑 Panel Admin</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
