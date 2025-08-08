<?php
/**
 * Script para Limpiar Funciones Duplicadas
 * Elimina funciones obsoletas que causan conflictos
 */

$authFile = __DIR__ . '/../includes/auth.php';

// Leer el contenido actual
$content = file_get_contents($authFile);

// Funciones a eliminar (obsoletas y duplicadas)
$functionsToRemove = [
    // requireAuth function
    '/\/\*\*\s*\n\s*\* Requiere autenticación para acceder a una página\s*\n\s*\*\s*\n\s*\* @param string \$redirect_url URL a la que redirigir si no está autenticado\s*\n\s*\*\/\s*\nfunction requireAuth\(\$redirect_url = \'login\.php\'\) \{[^}]+\}/',
    
    // requireGuest function  
    '/\/\*\*\s*\n\s*\* Requiere que el usuario NO esté autenticado\s*\n\s*\*\s*\n\s*\* @param string \$redirect_url URL a la que redirigir si está autenticado\s*\n\s*\*\/\s*\nfunction requireGuest\(\$redirect_url = \'index\.php\'\) \{[^}]+\}/'
];

$originalContent = $content;

// Eliminar las funciones obsoletas
foreach ($functionsToRemove as $pattern) {
    $content = preg_replace($pattern, '', $content);
}

// Limpiar espacios en blanco extra
$content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

// Guardar el archivo limpio
if (file_put_contents($authFile, $content)) {
    echo "✅ Funciones duplicadas eliminadas de auth.php\n";
    echo "📊 Bytes removidos: " . (strlen($originalContent) - strlen($content)) . "\n";
} else {
    echo "❌ Error al guardar el archivo\n";
}

// Verificar que no queden conflictos
echo "\n🔍 Verificando conflictos restantes...\n";

$sessionProtectionFile = __DIR__ . '/../includes/session_protection.php';
$authContent = file_get_contents($authFile);
$sessionContent = file_get_contents($sessionProtectionFile);

// Buscar funciones en ambos archivos
preg_match_all('/function\s+(\w+)\s*\(/', $authContent, $authFunctions);
preg_match_all('/function\s+(\w+)\s*\(/', $sessionContent, $sessionFunctions);

$authFuncs = $authFunctions[1];
$sessionFuncs = $sessionFunctions[1];

$conflicts = array_intersect($authFuncs, $sessionFuncs);

if (empty($conflicts)) {
    echo "✅ No se encontraron conflictos de funciones\n";
} else {
    echo "⚠️ Conflictos encontrados: " . implode(', ', $conflicts) . "\n";
}

echo "\n📋 Funciones en session_protection.php:\n";
foreach ($sessionFuncs as $func) {
    echo "   - $func()\n";
}

echo "\n📋 Funciones en auth.php:\n";
foreach ($authFuncs as $func) {
    echo "   - $func()\n";
}

echo "\n✅ Limpieza completada!\n";
?>
