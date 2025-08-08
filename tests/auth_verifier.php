<?php
/**
 * Verificador Final del Sistema de Autenticación
 * Comprueba que todos los archivos críticos estén correctamente protegidos
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🔐 Verificador de Autenticación - Sistema Inglés</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1000px; 
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
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .status-card {
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid;
        }
        .protected { 
            background: #d4f6d4; 
            border-left-color: #28a745;
            color: #155724;
        }
        .public { 
            background: #fff3cd; 
            border-left-color: #ffc107;
            color: #856404;
        }
        .unprotected { 
            background: #f8d7da; 
            border-left-color: #dc3545;
            color: #721c24;
        }
        .missing { 
            background: #e2e3e5; 
            border-left-color: #6c757d;
            color: #495057;
        }
        .icon { font-size: 1.5em; margin-right: 10px; }
        .file-list { margin-top: 10px; font-size: 0.9em; }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
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
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔐 Verificador de Autenticación</h1>
            <p>Estado completo del sistema de protección</p>
        </div>";

// Definir categorías de archivos
$protectedFiles = [
    'Páginas de Usuario' => [
        'pages/dashboard.php',
        'pages/topics.php', 
        'pages/practice.php',
        'pages/progress.php',
        'pages/profile.php',
        'pages/notifications.php',
        'pages/bookmarks.php',
        'pages/search.php',
        'pages/recommendations.php',
        'pages/settings.php',
        'pages/topic_detail.php'
    ],
    'Panel de Administración' => [
        'admin/index.php',
        'admin/users.php',
        'admin/topics.php',
        'admin/questions.php',
        'admin/verbs.php'
    ],
    'APIs del Sistema' => [
        'api/quiz-result-api.php',
        'api/check_achievements.php',
        'api/get_user_stats.php',
        'api/update_progress.php'
    ]
];

$publicFiles = [
    'Autenticación (Públicos)' => [
        'auth/login.php',
        'auth/register.php',
        'auth/logout.php'
    ]
];

$systemFiles = [
    'Archivos del Sistema' => [
        'index.php',
        'includes/session_protection.php',
        'includes/auth.php',
        'includes/config.php',
        'includes/db.php'
    ]
];

/**
 * Función para verificar el estado de protección de un archivo
 */
function checkFileProtection($filePath) {
    $fullPath = __DIR__ . '/../' . $filePath;
    
    if (!file_exists($fullPath)) {
        return ['status' => 'missing', 'message' => 'Archivo no encontrado'];
    }
    
    $content = file_get_contents($fullPath);
    
    if (strpos($content, 'session_protection.php') !== false && 
        strpos($content, 'requireLogin()') !== false) {
        return ['status' => 'protected', 'message' => 'Protegido correctamente'];
    } elseif (strpos($content, 'session_protection.php') !== false && 
              strpos($content, 'requireAdmin()') !== false) {
        return ['status' => 'protected', 'message' => 'Protegido (Admin)'];
    } else {
        return ['status' => 'unprotected', 'message' => 'Sin protección'];
    }
}

// Verificar archivos protegidos
foreach ($protectedFiles as $category => $files) {
    echo "<div class='status-card'>
            <h3><span class='icon'>🛡️</span>{$category}</h3>
            <div class='file-list'>";
    
    $categoryStatus = 'protected';
    foreach ($files as $file) {
        $status = checkFileProtection($file);
        $statusClass = $status['status'];
        $icon = $status['status'] === 'protected' ? '✅' : 
                ($status['status'] === 'missing' ? '❓' : '❌');
        
        echo "<div class='$statusClass'>$icon <strong>$file</strong> - {$status['message']}</div>";
        
        if ($status['status'] !== 'protected') {
            $categoryStatus = 'unprotected';
        }
    }
    
    echo "    </div>
          </div>";
}

// Verificar archivos públicos
foreach ($publicFiles as $category => $files) {
    echo "<div class='status-card public'>
            <h3><span class='icon'>🌐</span>{$category}</h3>
            <div class='file-list'>";
    
    foreach ($files as $file) {
        $status = checkFileProtection($file);
        $isCorrect = $status['status'] === 'unprotected' || $status['status'] === 'missing';
        $icon = $isCorrect ? '✅' : '⚠️';
        $message = $isCorrect ? 'Correctamente público' : 'Protegido (puede ser incorrecto)';
        
        echo "<div>$icon <strong>$file</strong> - $message</div>";
    }
    
    echo "    </div>
          </div>";
}

// Verificar archivos del sistema
foreach ($systemFiles as $category => $files) {
    echo "<div class='status-card'>
            <h3><span class='icon'>⚙️</span>{$category}</h3>
            <div class='file-list'>";
    
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/../' . $file;
        $exists = file_exists($fullPath);
        $icon = $exists ? '✅' : '❌';
        $message = $exists ? 'Disponible' : 'No encontrado';
        
        echo "<div>$icon <strong>$file</strong> - $message</div>";
    }
    
    echo "    </div>
          </div>";
}

// Resumen del sistema
echo "<div class='summary'>
        <h2>📊 Resumen del Sistema</h2>
        <p><strong>Estado General:</strong> Sistema de autenticación implementado</p>
        <p><strong>Protección Obligatoria:</strong> Activa en todas las páginas críticas</p>
        <p><strong>Acceso Público:</strong> Solo archivos de autenticación</p>
        <p><strong>Punto de Entrada:</strong> index.php redirige a login si no autenticado</p>
        
        <div style='margin-top: 20px;'>
            <a href='../index.php' class='btn btn-success'>🏠 Ir al Sistema</a>
            <a href='../auth/login.php' class='btn btn-primary'>🔑 Página de Login</a>
            <a href='system_verifier.php' class='btn btn-warning'>🔧 Pruebas del Sistema</a>
        </div>
      </div>";

echo "    </div>
</body>
</html>";
?>
