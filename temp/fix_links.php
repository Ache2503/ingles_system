<?php
/**
 * Script para actualizar enlaces y redirecciones
 */

echo "🔗 Actualizando enlaces y redirecciones...\n\n";

// Archivos a procesar
$files = array_merge(
    glob('auth/*.php'),
    glob('pages/*.php'),
    glob('api/*.php'),
    ['index.php']
);

$replacements = [
    // Enlaces básicos
    'href="login.php"' => 'href="auth/login.php"',
    'href="register.php"' => 'href="auth/register.php"',
    'href="logout.php"' => 'href="auth/logout.php"',
    'href="topics.php"' => 'href="pages/topics.php"',
    'href="topic_detail.php"' => 'href="pages/topic_detail.php"',
    'href="practice.php"' => 'href="pages/practice.php"',
    'href="progress.php"' => 'href="pages/progress.php"',
    'href="profile.php"' => 'href="pages/profile.php"',
    'href="dashboard.php"' => 'href="pages/dashboard.php"',
    
    // Redirecciones de header
    "Location: topics.php" => "Location: pages/topics.php",
    "Location: login.php" => "Location: auth/login.php",
    "Location: register.php" => "Location: auth/register.php",
    "Location: profile.php" => "Location: pages/profile.php",
    
    // Action en formularios
    'action="login.php"' => 'action="auth/login.php"',
    'action="register.php"' => 'action="auth/register.php"',
    
    // URLs con ingles/
    'href="/ingles/topics.php"' => 'href="/ingles/pages/topics.php"',
    'href="/ingles/login.php"' => 'href="/ingles/auth/login.php"',
    'href="/ingles/register.php"' => 'href="/ingles/auth/register.php"',
    '"Location: /ingles/topics.php"' => '"Location: /ingles/pages/topics.php"',
    '"Location: /ingles/login.php"' => '"Location: /ingles/auth/login.php"',
];

$totalChanges = 0;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    echo "📄 Procesando: $file\n";
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileChanges = 0;
    
    foreach ($replacements as $search => $replace) {
        $newContent = str_replace($search, $replace, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fileChanges++;
        }
    }
    
    // Actualizar rutas relativas específicas para archivos en subdirectorios
    if (strpos($file, 'auth/') === 0 || strpos($file, 'pages/') === 0) {
        // Para archivos en subdirectorios, actualizar rutas relativas
        $content = str_replace('href="topics.php"', 'href="../pages/topics.php"', $content);
        $content = str_replace('href="login.php"', 'href="../auth/login.php"', $content);
        $content = str_replace('href="register.php"', 'href="../auth/register.php"', $content);
        $content = str_replace('href="index.php"', 'href="../index.php"', $content);
        
        // Actualizar fetch URLs para APIs
        $content = str_replace("fetch('quiz-result-api.php'", "fetch('../api/quiz-result-api.php'", $content);
        
        if ($content !== $originalContent) {
            $fileChanges++;
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "  ✅ $fileChanges cambios aplicados\n";
        $totalChanges += $fileChanges;
    } else {
        echo "  ⚪ Sin cambios\n";
    }
}

echo "\n🎉 Actualización de enlaces completada!\n";
echo "📊 Total de cambios: $totalChanges\n";
?>
