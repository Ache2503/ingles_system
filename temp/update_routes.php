<?php
/**
 * Script para actualizar todas las rutas después de la reorganización
 * Actualiza automáticamente todos los archivos con las nuevas rutas
 */

echo "🔄 Iniciando actualización masiva de rutas...\n\n";

// Definir mapeo de rutas viejas a nuevas
$routeMapping = [
    // Archivos de autenticación
    'login.php' => 'auth/login.php',
    'register.php' => 'auth/register.php', 
    'logout.php' => 'auth/logout.php',
    
    // Páginas principales
    'topics.php' => 'pages/topics.php',
    'topic_detail.php' => 'pages/topic_detail.php',
    'practice.php' => 'pages/practice.php',
    'progress.php' => 'pages/progress.php',
    'profile.php' => 'pages/profile.php',
    'dashboard.php' => 'pages/dashboard.php',
    'bookmarks.php' => 'pages/bookmarks.php',
    'notifications.php' => 'pages/notifications.php',
    'recommendations.php' => 'pages/recommendations.php',
    'search.php' => 'pages/search.php',
    'settings.php' => 'pages/settings.php',
    
    // APIs
    'quiz-result-api.php' => 'api/quiz-result-api.php',
    'quiz-result.php' => 'api/quiz-result.php'
];

// Directorios a procesar
$directories = [
    'auth',
    'pages', 
    'api',
    'admin',
    '.' // directorio raíz
];

$totalUpdates = 0;

foreach ($directories as $dir) {
    echo "📁 Procesando directorio: $dir\n";
    
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        echo "  📄 Procesando: $file\n";
        
        $content = file_get_contents($file);
        $originalContent = $content;
        $fileUpdates = 0;
        
        // Actualizar includes/requires
        foreach ($routeMapping as $oldPath => $newPath) {
            // Patrones para includes/requires
            $patterns = [
                // require_once 'file.php'
                "/require_once\s+['\"]$oldPath['\"]/",
                "/require_once\s+__DIR__\s*\.\s*['\"][\/]?$oldPath['\"]/",
                "/require\s+['\"]$oldPath['\"]/",
                "/require\s+__DIR__\s*\.\s*['\"][\/]?$oldPath['\"]/",
                "/include_once\s+['\"]$oldPath['\"]/",
                "/include_once\s+__DIR__\s*\.\s*['\"][\/]?$oldPath['\"]/",
                "/include\s+['\"]$oldPath['\"]/",
                "/include\s+__DIR__\s*\.\s*['\"][\/]?$oldPath['\"]/",
                
                // Enlaces href
                "/href=['\"]$oldPath['\"]/",
                "/href=['\"][\/]?ingles[\/]$oldPath['\"]/",
                
                // Redirecciones Location
                "/Location:\s*['\"][\/]?ingles[\/]$oldPath['\"]/",
                "/Location:\s*['\"]$oldPath['\"]/",
                
                // Action en formularios
                "/action=['\"]$oldPath['\"]/",
                "/action=['\"][\/]?ingles[\/]$oldPath['\"]/",
            ];
            
            foreach ($patterns as $pattern) {
                $replacement = '';
                
                if (strpos($pattern, 'require') !== false || strpos($pattern, 'include') !== false) {
                    // Para includes, calcular ruta relativa
                    $relativePath = $this->calculateRelativePath($file, $newPath);
                    if (strpos($pattern, '__DIR__') !== false) {
                        $replacement = str_replace($oldPath, $relativePath, $pattern);
                        $replacement = str_replace(['/', '\\'], ['\\/', '\\/'], $replacement);
                        $replacement = "/require_once __DIR__ . '$relativePath'/";
                    } else {
                        $replacement = "require_once '$relativePath'";
                    }
                } elseif (strpos($pattern, 'href') !== false) {
                    $replacement = "href=\"$newPath\"";
                } elseif (strpos($pattern, 'Location') !== false) {
                    $replacement = "Location: /ingles/$newPath\"";
                } elseif (strpos($pattern, 'action') !== false) {
                    $replacement = "action=\"$newPath\"";
                }
                
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== $content) {
                    $content = $newContent;
                    $fileUpdates++;
                }
            }
        }
        
        // Actualizar rutas específicas para includes
        $includeUpdates = $this->updateIncludePaths($file, $content);
        $content = $includeUpdates['content'];
        $fileUpdates += $includeUpdates['count'];
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "    ✅ Actualizado ($fileUpdates cambios)\n";
            $totalUpdates += $fileUpdates;
        } else {
            echo "    ⚪ Sin cambios\n";
        }
    }
    
    echo "\n";
}

echo "🎉 Actualización completada!\n";
echo "📊 Total de actualizaciones: $totalUpdates\n";

// Métodos auxiliares
function calculateRelativePath($fromFile, $toFile) {
    $fromDir = dirname($fromFile);
    $levels = substr_count($fromDir, '/');
    
    if ($levels === 0) {
        // Estamos en la raíz
        return $toFile;
    } else {
        // Necesitamos subir niveles
        return str_repeat('../', $levels) . $toFile;
    }
}

function updateIncludePaths($file, $content) {
    $updates = 0;
    
    // Determinar la profundidad del archivo actual
    $depth = substr_count($file, '/');
    $prefix = str_repeat('../', $depth);
    
    // Patrones específicos para includes
    $includePatterns = [
        "/include\s+['\"]includes\/([^'\"]+)['\"]/",
        "/require_once\s+__DIR__\s*\.\s*['\"]\/includes\/([^'\"]+)['\"]/",
        "/require_once\s+['\"]includes\/([^'\"]+)['\"]/",
    ];
    
    foreach ($includePatterns as $pattern) {
        $content = preg_replace_callback($pattern, function($matches) use ($prefix, &$updates) {
            $updates++;
            $includeFile = $matches[1];
            
            if (strpos($matches[0], '__DIR__') !== false) {
                return "require_once __DIR__ . '/{$prefix}includes/$includeFile'";
            } else {
                return "include '{$prefix}includes/$includeFile'";
            }
        }, $content);
    }
    
    return ['content' => $content, 'count' => $updates];
}
?>
