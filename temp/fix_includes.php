<?php
/**
 * Script simplificado para actualizar rutas críticas
 */

echo "🔄 Actualizando rutas principales...\n\n";

// Lista de archivos a procesar con sus rutas de includes
$filesToUpdate = [
    // Archivos en auth/
    'auth/register.php' => '../',
    'auth/logout.php' => '../',
    
    // Archivos en pages/
    'pages/topics.php' => '../',
    'pages/topic_detail.php' => '../',
    'pages/practice.php' => '../',
    'pages/progress.php' => '../',
    'pages/profile.php' => '../',
    'pages/dashboard.php' => '../',
    'pages/bookmarks.php' => '../',
    'pages/notifications.php' => '../',
    'pages/recommendations.php' => '../',
    'pages/search.php' => '../',
    'pages/settings.php' => '../',
];

foreach ($filesToUpdate as $file => $relativePrefix) {
    if (file_exists($file)) {
        echo "📄 Actualizando: $file\n";
        
        $content = file_get_contents($file);
        
        // Actualizar includes básicos
        $content = str_replace(
            "require_once __DIR__ . '/includes/",
            "require_once __DIR__ . '/{$relativePrefix}includes/",
            $content
        );
        
        $content = str_replace(
            "include 'includes/",
            "include '{$relativePrefix}includes/",
            $content
        );
        
        $content = str_replace(
            "include_once 'includes/",
            "include_once '{$relativePrefix}includes/",
            $content
        );
        
        file_put_contents($file, $content);
        echo "  ✅ Includes actualizados\n";
    } else {
        echo "  ❌ Archivo no encontrado: $file\n";
    }
}

echo "\n🎉 Actualización de includes completada!\n";
?>
