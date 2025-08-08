<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';

echo "=== HTML DEL HEADER GENERADO ===\n";
ob_start();
require_once __DIR__ . '/includes/header.php';
$header = ob_get_clean();

// Extraer solo la parte del nav
preg_match('/<nav>.*?<\/nav>/s', $header, $matches);
if ($matches) {
    echo "Navegación extraída:\n";
    echo $matches[0] . "\n";
}

echo "\n=== LINKS ESPECÍFICOS ===\n";
preg_match_all('/href="([^"]+)"/', $header, $linkMatches);
if ($linkMatches[1]) {
    foreach ($linkMatches[1] as $link) {
        echo "- $link\n";
    }
}
?>
